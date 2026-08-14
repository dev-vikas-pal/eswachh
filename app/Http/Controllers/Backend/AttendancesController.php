<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\CleanerAttendance;
use App\Services\SectorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;
use Yajra\DataTables\DataTables;

/**
 * Cleaner attendance.
 *
 * A cleaner reports how many of their cars they serviced today, chosen from a
 * list running up to the number of active cars on their round. Anything above
 * zero is a day present. Administrators and Franchise Owners read the same
 * records back, the latter only for their own sectors.
 */
class AttendancesController extends Controller
{
    use Authorizable;

    public $module_title;

    public $module_name;

    public $module_icon;

    public function __construct()
    {
        $this->module_title = 'Attendance';
        $this->module_name = 'attendances';
        $this->module_icon = 'fa-solid fa-clipboard-check';
    }

    /**
     * How many active cars this cleaner is responsible for.
     */
    private function activeCarCount(int $cleanerId): int
    {
        return Order::where('assigned_user_id', $cleanerId)
            ->whereIn('status', [2, 4])
            ->count();
    }

    /**
     * A cleaner's own sector, used to stamp the attendance record so franchise
     * reporting works.
     */
    private function sectorIdFor(int $cleanerId): ?int
    {
        $sectorId = DB::table('userprofiles')->where('user_id', $cleanerId)->value('sector_id');

        return $sectorId > 0 ? (int) $sectorId : null;
    }

    public function index(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_icon = $this->module_icon;
        $module_action = 'List';

        $user = auth()->user();
        $isCleaner = $user->hasRole(SectorService::CLEANER_ROLE);

        $sectorOptions = SectorService::sectorOptions();
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();

        // What the cleaner needs to file today.
        $today = Carbon::today();
        $todaysEntry = null;
        $totalCars = 0;

        if ($isCleaner) {
            $todaysEntry = CleanerAttendance::where('user_id', $user->id)
                ->whereDate('date', $today)
                ->first();

            $totalCars = $this->activeCarCount($user->id);
        }

        logUserAccess($module_title.' '.$module_action);

        return view(
            "backend.$module_name.index",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'isCleaner', 'todaysEntry', 'totalCars', 'today', 'sectorOptions', 'canSeeAllSectors')
        );
    }

    public function index_data()
    {
        $request = request()->all();
        $user = auth()->user();

        $attendances = CleanerAttendance::query()
            ->leftJoin('users', 'users.id', '=', 'cleaner_attendances.user_id')
            ->leftJoin('sectors', 'sectors.id', '=', 'cleaner_attendances.sector_id')
            ->select(
                'cleaner_attendances.*',
                'users.name as cleaner_name',
                'users.mobile as cleaner_mobile',
                'sectors.name as sector_name'
            );

        if ($user->hasRole(SectorService::CLEANER_ROLE)) {
            $attendances->where('cleaner_attendances.user_id', $user->id);
        } else {
            $attendances->forSectors(SectorService::selectedSectorIds($request['filter_sector_id'] ?? null));
        }

        if (! empty($request['filter_date'])) {
            $attendances->whereDate('cleaner_attendances.date', $request['filter_date']);
        }

        if (! empty($request['filter_status']) && $request['filter_status'] !== '*') {
            $attendances->where('cleaner_attendances.status', $request['filter_status']);
        }

        return DataTables::of($attendances)
            ->editColumn('date', function ($data) {
                return Carbon::parse($data->date)->format('d-m-Y');
            })
            ->editColumn('status', function ($data) {
                $class = $data->status === CleanerAttendance::STATUS_PRESENT ? 'bg-success' : 'bg-danger';

                return '<span class="badge '.$class.'">'.e(ucfirst($data->status)).'</span>';
            })
            ->addColumn('cleaner_name', function ($data) {
                return $data->cleaner_name ?? 'NA';
            })
            ->addColumn('sector_name', function ($data) {
                return $data->sector_name ?? 'NA';
            })
            ->addColumn('serviced', function ($data) {
                return $data->cars_serviced.' / '.$data->total_cars;
            })
            ->rawColumns(['status'])
            ->order(function ($query) {
                $query->orderBy('cleaner_attendances.date', 'desc');
            })
            ->make(true);
    }

    /**
     * A cleaner files today's attendance.
     */
    public function store(Request $request)
    {
        $user = auth()->user();

        if (! $user->hasRole(SectorService::CLEANER_ROLE)) {
            abort(403, 'Only a cleaner records their own attendance.');
        }

        $totalCars = $this->activeCarCount($user->id);

        $request->validate([
            'cars_serviced' => 'required|integer|min:0|max:'.max($totalCars, 0),
            'note' => 'nullable|string|max:255',
        ], [
            'cars_serviced.max' => 'You have '.$totalCars.' active car(s) on your round.',
        ]);

        $carsServiced = (int) $request->cars_serviced;
        $today = Carbon::today();

        // updateOrCreate against the unique key, so filing twice corrects the
        // day rather than creating a second record.
        CleanerAttendance::updateOrCreate(
            ['user_id' => $user->id, 'date' => $today],
            [
                'sector_id' => $this->sectorIdFor($user->id),
                'cars_serviced' => $carsServiced,
                'total_cars' => $totalCars,
                'status' => CleanerAttendance::statusFor($carsServiced),
                'note' => $request->note,
            ]
        );

        Log::info('Attendance recorded.', [
            'cleaner_id' => $user->id,
            'date' => $today->toDateString(),
            'cars_serviced' => $carsServiced,
            'total_cars' => $totalCars,
        ]);

        flash(icon().' Attendance recorded for '.$today->format('d-m-Y').'.')->success()->important();

        return redirect()->route('backend.attendances.index');
    }
}
