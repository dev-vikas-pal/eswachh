<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\Complaint;
use App\Models\User;
use App\Services\SectorService;
use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;
use Yajra\DataTables\DataTables;

/**
 * Customer complaints, from being raised through to being closed.
 *
 * One screen serves four audiences, each seeing a different slice:
 *   customer         their own complaints
 *   cleaner          the ones assigned to them, which they can close
 *   franchise owner  everything in their sectors
 *   super admin      everything
 */
class ComplaintsController extends Controller
{
    use Authorizable;

    /** Roughly 200 words, as the proposal asks for. */
    private const MAX_WORDS = 200;

    public $module_title;

    public $module_name;

    public $module_icon;

    public function __construct()
    {
        $this->module_title = 'Complaints';
        $this->module_name = 'complaints';
        $this->module_icon = 'fa-solid fa-comment-dots';
    }

    /**
     * The complaints the logged in user is entitled to see.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    private function visibleComplaints()
    {
        $user = auth()->user();
        $query = Complaint::query();

        if ($user->hasRole('customer')) {
            return $query->where('complaints.user_id', $user->id);
        }

        if ($user->hasRole(SectorService::CLEANER_ROLE)) {
            return $query->where('complaints.assigned_user_id', $user->id);
        }

        return $query->forSectors(SectorService::allowedSectorIds());
    }

    public function index()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'List';

        $user = auth()->user();
        $isCustomer = $user->hasRole('customer');
        $isCleaner = $user->hasRole(SectorService::CLEANER_ROLE);
        $canResolve = $isCleaner || $user->can('edit_complaints');
        $canRaise = $user->can('add_complaints');

        $statusLabels = Complaint::statusLabels();
        $resolutionLabels = Complaint::resolutionLabels();

        $sectorOptions = SectorService::sectorOptions();
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();
        $showSectorFilter = ! $isCustomer && ! $isCleaner;

        logUserAccess($module_title.' '.$module_action);

        return view(
            "backend.$module_name.index",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'statusLabels', 'resolutionLabels', 'canResolve', 'canRaise', 'sectorOptions', 'canSeeAllSectors', 'showSectorFilter')
        );
    }

    public function index_data()
    {
        $request = request()->all();

        $complaints = $this->visibleComplaints()
            ->leftJoin('orders', 'orders.id', '=', 'complaints.order_id')
            ->leftJoin('users as customers', 'customers.id', '=', 'complaints.user_id')
            ->leftJoin('users as cleaners', 'cleaners.id', '=', 'complaints.assigned_user_id')
            ->leftJoin('sectors', 'sectors.id', '=', 'complaints.sector_id')
            ->select(
                'complaints.*',
                'orders.car_number',
                'customers.name as customer_name',
                'customers.mobile as customer_mobile',
                'cleaners.name as cleaner_name',
                'sectors.name as sector_name'
            );

        if (! empty($request['filter_status']) && $request['filter_status'] !== '*') {
            $complaints->where('complaints.status', $request['filter_status']);
        }

        if (! empty($request['filter_sector_id']) && $request['filter_sector_id'] !== '*') {
            $complaints->forSectors(SectorService::selectedSectorIds($request['filter_sector_id']));
        }

        $statusLabels = Complaint::statusLabels();
        $resolutionLabels = Complaint::resolutionLabels();

        $user = auth()->user();
        $canResolve = $user->hasRole(SectorService::CLEANER_ROLE) || $user->can('edit_complaints');

        return DataTables::of($complaints)
            ->editColumn('created_at', function ($data) {
                return Carbon::parse($data->created_at)->format('d-m-Y H:i');
            })
            ->editColumn('message', function ($data) {
                return nl2br(e(\Illuminate\Support\Str::limit($data->message, 160)));
            })
            ->editColumn('status', function ($data) use ($statusLabels) {
                $class = $data->status === Complaint::STATUS_OPEN ? 'bg-warning text-dark' : 'bg-success';

                return '<span class="badge '.$class.'">'.e($statusLabels[$data->status] ?? $data->status).'</span>';
            })
            ->addColumn('resolution_label', function ($data) use ($resolutionLabels) {
                return $data->resolution ? ($resolutionLabels[$data->resolution] ?? $data->resolution) : '-';
            })
            ->addColumn('sector_name', function ($data) {
                return $data->sector_name ?? 'NA';
            })
            ->addColumn('cleaner_name', function ($data) {
                return $data->cleaner_name ?? 'Not assigned';
            })
            ->addColumn('action', function ($data) use ($canResolve) {
                if (! $canResolve || $data->status !== Complaint::STATUS_OPEN) {
                    return '';
                }

                return '<button type="button" class="btn btn-sm btn-outline-primary close-complaint" data-id="'.$data->id.'">'
                    .'<i class="fas fa-check"></i> Close</button>';
            })
            ->rawColumns(['status', 'message', 'action'])
            ->make(true);
    }

    /**
     * Form for a customer to raise a complaint about one of their cars.
     */
    public function create()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'Create';

        $orders = Order::where('user_id', auth()->id())
            ->whereIn('status', [2, 4])
            ->select('id', 'car_number')
            ->get();

        $maxWords = self::MAX_WORDS;

        return view(
            "backend.$module_name.create",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'orders', 'maxWords')
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'order_id' => 'required|integer',
            'message' => ['required', 'string', function ($attribute, $value, $fail) {
                if (str_word_count($value) > self::MAX_WORDS) {
                    $fail('Please keep the complaint to '.self::MAX_WORDS.' words or fewer.');
                }
            }],
        ]);

        // A customer may only complain about a car that is theirs.
        $order = Order::where('id', $request->order_id)
            ->where('user_id', auth()->id())
            ->firstOrFail();

        $complaint = Complaint::create([
            'order_id' => $order->id,
            'user_id' => auth()->id(),
            'sector_id' => $order->sector_id,
            'assigned_user_id' => $order->assigned_user_id,
            'message' => $request->message,
            'status' => Complaint::STATUS_OPEN,
        ]);

        $this->notifyOfNewComplaint($complaint, $order);

        flash(icon().' Your complaint has been logged. We will get back to you shortly.')->success()->important();

        return redirect()->route('backend.complaints.index');
    }

    /**
     * Tell the franchise owners for the sector and the assigned cleaner.
     */
    private function notifyOfNewComplaint(Complaint $complaint, Order $order): void
    {
        $customer = auth()->user();
        $arguments = [$order->car_number, $customer->name, \Illuminate\Support\Str::limit($complaint->message, 120)];

        if ($order->assigned_user_id) {
            $cleaner = User::find($order->assigned_user_id);

            if ($cleaner && $cleaner->mobile) {
                SMSService::sendWhatsAppMsg($cleaner->mobile, 'complaint_notification_cleaner', $arguments);
            }
        }

        if (! $complaint->sector_id) {
            return;
        }

        // Every franchise owner mapped to this sector.
        $owners = User::whereHas('sectors', function ($query) use ($complaint) {
            $query->where('sectors.id', $complaint->sector_id);
        })->get();

        foreach ($owners as $owner) {
            if ($owner->mobile) {
                SMSService::sendWhatsAppMsg($owner->mobile, 'complaint_notification_franchise', $arguments);
            }
        }

        Log::info('Complaint raised.', [
            'complaint_id' => $complaint->id,
            'order_id' => $order->id,
            'sector_id' => $complaint->sector_id,
            'franchise_owners_notified' => $owners->count(),
        ]);
    }

    /**
     * Full history of one complaint.
     *
     * @param  int  $id
     */
    public function show($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'Show';

        $complaint = $this->visibleComplaints()
            ->with(['order', 'customer', 'cleaner', 'sector'])
            ->where('complaints.id', $id)
            ->firstOrFail();

        $user = auth()->user();
        $canResolve = ($user->hasRole(SectorService::CLEANER_ROLE) || $user->can('edit_complaints'))
            && $complaint->isOpen();

        $resolutionLabels = Complaint::resolutionLabels();
        $statusLabels = Complaint::statusLabels();

        return view(
            "backend.$module_name.show",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'complaint', 'canResolve', 'resolutionLabels', 'statusLabels')
        );
    }

    /**
     * Close a complaint. The cleaner says whether they managed to speak to the
     * customer; either way the complaint ends up Closed.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function resolve(Request $request, $id)
    {
        $user = auth()->user();

        if (! $user->hasRole(SectorService::CLEANER_ROLE) && ! $user->can('edit_complaints')) {
            abort(403);
        }

        $request->validate([
            'resolution' => 'required|in:'.implode(',', array_keys(Complaint::resolutionLabels())),
            'resolution_note' => 'nullable|string|max:500',
        ]);

        // visibleComplaints keeps a cleaner to their own complaints and a
        // franchise owner to their sectors.
        $complaint = $this->visibleComplaints()->where('complaints.id', $id)->first();

        if (! $complaint) {
            return response()->json(['message' => 'That complaint is not one you can close.'], 404);
        }

        if (! $complaint->isOpen()) {
            return response()->json(['message' => 'That complaint is already closed.'], 422);
        }

        $complaint->status = Complaint::STATUS_CLOSED;
        $complaint->resolution = $request->resolution;
        $complaint->resolution_note = $request->resolution_note;
        $complaint->closed_by = $user->id;
        $complaint->closed_at = now();
        $complaint->save();

        Log::info('Complaint closed.', [
            'complaint_id' => $complaint->id,
            'resolution' => $complaint->resolution,
            'by' => $user->id,
        ]);

        return response()->json(['message' => 'Complaint closed.']);
    }
}
