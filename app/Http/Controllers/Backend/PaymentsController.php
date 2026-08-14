<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Services\RazorpayService;
use App\Services\SectorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\DataTables;

/**
 * Payment tracking and reporting.
 *
 * Every payment is listed with the sector it belongs to, so a Franchise Owner
 * sees only their own takings. An administrator can correct a status by hand
 * after checking the bank, and that correction is attributed.
 */
class PaymentsController extends Controller
{
    use Authorizable;

    public $module_title;

    public $module_name;

    public $module_path;

    public $module_icon;

    public function __construct()
    {
        $this->module_title = 'Payments';
        $this->module_name = 'payments';
        $this->module_path = 'payments';
        $this->module_icon = 'fa-solid fa-indian-rupee-sign';
    }

    /**
     * Base query, already limited to the sectors this user may see.
     *
     * @param  array<int, int>|null  $sectorIds
     * @return \Illuminate\Database\Query\Builder
     */
    private function query(?array $sectorIds)
    {
        $payments = DB::table('payment_history')
            ->leftJoin('orders', 'orders.id', '=', 'payment_history.order_id')
            ->leftJoin('users', 'users.id', '=', 'payment_history.user_id')
            ->leftJoin('sectors', 'sectors.id', '=', 'payment_history.sector_id')
            ->select(
                'payment_history.*',
                'orders.car_number',
                'users.name as customer_name',
                'users.mobile as customer_mobile',
                'sectors.name as sector_name'
            );

        if ($sectorIds !== null) {
            $payments->whereIn('payment_history.sector_id', $sectorIds);
        }

        return $payments;
    }

    /**
     * Apply the screen's filters to a query.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array<string, mixed>  $request
     */
    private function applyFilters($query, array $request)
    {
        if (! empty($request['filter_status']) && $request['filter_status'] !== '*') {
            $query->where('payment_history.payment_status', $request['filter_status']);
        }

        if (! empty($request['filter_payment_for']) && $request['filter_payment_for'] !== '*') {
            $query->where('payment_history.payment_for', $request['filter_payment_for']);
        }

        if (! empty($request['filter_date_start'])) {
            $query->whereDate('payment_history.payment_date_time', '>=', $request['filter_date_start']);
        }

        if (! empty($request['filter_date_end'])) {
            $query->whereDate('payment_history.payment_date_time', '<=', $request['filter_date_end']);
        }

        return $query;
    }

    public function index()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'List';

        $sectorOptions = SectorService::sectorOptions();
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();
        $statusLabels = RazorpayService::statusLabels();
        $canOverride = auth()->user()->can('edit_payments');

        logUserAccess($module_title.' '.$module_action);

        return view(
            "backend.$module_name.index",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'sectorOptions', 'canSeeAllSectors', 'statusLabels', 'canOverride')
        );
    }

    public function index_data()
    {
        $request = request()->all();

        $sectorIds = SectorService::selectedSectorIds($request['filter_sector_id'] ?? null);
        $payments = $this->applyFilters($this->query($sectorIds), $request);

        $statusLabels = RazorpayService::statusLabels();
        $canOverride = auth()->user()->can('edit_payments');

        return DataTables::of($payments)
            ->editColumn('payment_date_time', function ($data) {
                return Carbon::parse($data->payment_date_time)->format('d-m-Y H:i');
            })
            ->editColumn('payment_amount', function ($data) {
                return number_format($data->payment_amount, 2);
            })
            ->editColumn('payment_status', function ($data) use ($statusLabels) {
                $label = $statusLabels[$data->payment_status] ?? ucfirst($data->payment_status);
                $class = [
                    RazorpayService::STATUS_CAPTURED => 'bg-success',
                    RazorpayService::STATUS_INITIATED => 'bg-warning text-dark',
                    RazorpayService::STATUS_FAILED => 'bg-danger',
                ][$data->payment_status] ?? 'bg-secondary';

                $badge = '<span class="badge '.$class.'">'.e($label).'</span>';

                if ($data->verified_at) {
                    $badge .= ' <i class="fas fa-user-check text-muted" title="Set by hand"></i>';
                }

                return $badge;
            })
            ->addColumn('sector_name', function ($data) {
                return $data->sector_name ?? 'NA';
            })
            ->addColumn('action', function ($data) use ($canOverride) {
                if (! $canOverride) {
                    return '';
                }

                return '<button type="button" class="btn btn-sm btn-outline-primary override-status"'
                    .' data-id="'.$data->id.'" data-status="'.e($data->payment_status).'">'
                    .'<i class="fas fa-pen"></i></button>';
            })
            ->rawColumns(['payment_status', 'action'])
            ->make(true);
    }

    /**
     * Totals for the current filter, shown above the table.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function summary()
    {
        // Not one of Authorizable's known method names, so it is checked here.
        $this->authorize('view_payments');

        $request = request()->all();

        $sectorIds = SectorService::selectedSectorIds($request['filter_sector_id'] ?? null);
        $payments = $this->applyFilters($this->query($sectorIds), $request);

        $rows = (clone $payments)
            ->select('payment_history.payment_status', DB::raw('COUNT(*) as count'), DB::raw('SUM(payment_history.payment_amount) as total'))
            ->groupBy('payment_history.payment_status')
            ->get();

        $labels = RazorpayService::statusLabels();
        $summary = [];

        foreach ($rows as $row) {
            $summary[] = [
                'status' => $labels[$row->payment_status] ?? ucfirst($row->payment_status),
                'count' => (int) $row->count,
                'total' => number_format($row->total, 2),
            ];
        }

        return response()->json(['summary' => $summary]);
    }

    /**
     * Correct a payment status by hand, after the bank has been checked.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function updateStatus(Request $request, $id)
    {
        $this->authorize('edit_payments');

        $request->validate([
            'payment_status' => 'required|in:'.implode(',', array_keys(RazorpayService::statusLabels())),
            'note' => 'nullable|string|max:500',
        ]);

        $sectorIds = SectorService::allowedSectorIds();

        $payment = $this->query($sectorIds)->where('payment_history.id', $id)->first();

        if (! $payment) {
            return response()->json(['message' => 'That payment is not one you can change.'], 404);
        }

        $note = trim(($payment->additional_notes ? $payment->additional_notes."\n" : '')
            .now()->format('Y-m-d H:i').' - status set to '.$request->payment_status
            .' by '.auth()->user()->name
            .($request->note ? ': '.$request->note : ''));

        DB::table('payment_history')->where('id', $id)->update([
            'payment_status' => $request->payment_status,
            'additional_notes' => $note,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
            // Assigned explicitly so the recorded payment date is preserved.
            'payment_date_time' => $payment->payment_date_time,
        ]);

        Log::info('Payment status set by hand.', [
            'payment_history_id' => $id,
            'from' => $payment->payment_status,
            'to' => $request->payment_status,
            'by' => auth()->id(),
        ]);

        return response()->json(['message' => 'Payment status updated.']);
    }

    /**
     * A printable invoice for one payment.
     *
     * Not covered by Authorizable's method map, so access is decided here: the
     * customer who paid can always see their own invoice even though they hold
     * no payment permissions, while everybody else needs view_payments and is
     * held to their sectors.
     *
     * @param  int  $id
     */
    public function invoice($id)
    {
        $user = auth()->user();

        $payment = DB::table('payment_history')
            ->leftJoin('orders', 'orders.id', '=', 'payment_history.order_id')
            ->leftJoin('users', 'users.id', '=', 'payment_history.user_id')
            ->leftJoin('sectors', 'sectors.id', '=', 'payment_history.sector_id')
            ->leftJoin('packages', 'packages.id', '=', 'orders.package_id')
            ->leftJoin('durations', 'durations.id', '=', 'orders.pakage_type')
            ->select(
                'payment_history.*',
                'orders.car_number',
                'orders.start_date',
                'orders.renew_date',
                'users.name as customer_name',
                'users.email as customer_email',
                'users.mobile as customer_mobile',
                'sectors.name as sector_name',
                'packages.name as package_name',
                'durations.name as duration_name'
            )
            ->where('payment_history.id', $id)
            ->first();

        if (! $payment) {
            abort(404);
        }

        $isOwnPayment = (int) $payment->user_id === (int) $user->id;

        if (! $isOwnPayment) {
            if (! $user->can('view_payments')) {
                abort(403);
            }

            if (! SectorService::canAccessSector($payment->sector_id)) {
                abort(404);
            }
        }

        $statusLabels = RazorpayService::statusLabels();

        return view('backend.payments.invoice', compact('payment', 'statusLabels'));
    }

    /**
     * Daily and monthly takings, broken down by franchise sector.
     */
    public function reports(Request $request)
    {
        $this->authorize('view_payments');

        $module_title = 'Payment Reports';
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'Reports';

        $sectorIds = SectorService::selectedSectorIds($request->get('filter_sector_id'));
        $sectorOptions = SectorService::sectorOptions();
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();
        $selectedSector = $request->get('filter_sector_id', '*');

        $days = (int) $request->get('days', 30);
        $months = (int) $request->get('months', 12);

        $daily = $this->takings($sectorIds, '%Y-%m-%d', Carbon::today()->subDays($days));
        $monthly = $this->takings($sectorIds, '%Y-%m', Carbon::today()->subMonths($months)->startOfMonth());

        logUserAccess($module_title.' '.$module_action);

        return view(
            "backend.$module_name.reports",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'daily', 'monthly', 'sectorOptions', 'canSeeAllSectors', 'selectedSector', 'days', 'months')
        );
    }

    /**
     * Completed takings grouped by period and sector.
     *
     * @param  array<int, int>|null  $sectorIds
     * @return \Illuminate\Support\Collection
     */
    private function takings(?array $sectorIds, string $format, Carbon $since)
    {
        $query = DB::table('payment_history')
            ->leftJoin('sectors', 'sectors.id', '=', 'payment_history.sector_id')
            ->where('payment_history.payment_status', RazorpayService::STATUS_CAPTURED)
            ->where('payment_history.payment_date_time', '>=', $since)
            ->select(
                DB::raw("DATE_FORMAT(payment_history.payment_date_time, '$format') as period"),
                DB::raw('COALESCE(sectors.name, "Unassigned") as sector_name'),
                DB::raw('COUNT(*) as payments'),
                DB::raw('SUM(payment_history.payment_amount) as total')
            )
            ->groupBy('period', 'sector_name')
            ->orderByDesc('period');

        if ($sectorIds !== null) {
            $query->whereIn('payment_history.sector_id', $sectorIds);
        }

        return $query->get()->groupBy('period');
    }
}
