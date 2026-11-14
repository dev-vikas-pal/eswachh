<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\User;
use App\Services\SectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Order\Models\Order;
use Carbon\Carbon;

class BackendController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $customerRoleIds = [5];
        $cleanerRoleIds = [4];

        // A super admin picks a franchise. That franchise may own several
        // sectors, and the figures below cover all of them. A franchise owner
        // still narrows by their own sectors.
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();
        $selectedSector = $request->get('filter_sector_id', '*');
        $selectedFranchise = $request->get('filter_franchise_id', '*');
        $franchiseOptions = $canSeeAllSectors ? SectorService::franchiseOptions() : [];

        if ($canSeeAllSectors) {
            $sectorIds = SectorService::sectorIdsForDashboard($selectedFranchise);
        } else {
            $sectorIds = SectorService::selectedSectorIds($selectedSector);
        }

        $sectorOptions = SectorService::sectorOptions();

        $totalCustomers = SectorService::scopeByUserSector(
            User::whereHas('roles', function ($query) use ($customerRoleIds) {
                $query->whereIn('id', $customerRoleIds);
            }),
            $sectorIds,
            'users.id'
        )->count();

        $totalCleaner = SectorService::scopeByUserSector(
            User::whereHas('roles', function ($query) use ($cleanerRoleIds) {
                $query->whereIn('id', $cleanerRoleIds);
            }),
            $sectorIds,
            'users.id'
        )->count();

        $totalOrders = Order::forSectors($sectorIds)->where('status', 2)->count();

        $totalActiveOrders = Order::forSectors($sectorIds)
        ->where('status', 2)
        ->where('renew_date', '>', now())
        ->whereNotNull('assigned_user_id')
        ->count();

        $totalNewOrders = Order::forSectors($sectorIds)
        ->where('status', 2)
        ->where('renew_date', '>', Carbon::today())
        ->whereNull('assigned_user_id')
        ->count();

        $totalExpiredOrders = Order::forSectors($sectorIds)
        ->whereNotNull('renew_date')
        ->where('status', 2)
        ->where('renew_date', '<', Carbon::today())
        ->count();

        $totalHoldOrders = Order::forSectors($sectorIds)
        ->whereNotNull('renew_date')
        ->where('status', 4)
        ->count();

        $expiredClothcount = Order::forSectors($sectorIds)
        ->where('status', 2)
        ->where('cloth_service', 1)
        ->where('cloth_count', '<', 5)
        ->count();

        [$totalRevenue, $monthRevenue] = $this->revenue($sectorIds);

        $user = auth()->user();
        $currentUserId = $user->id;
        $isCleaner = $user->hasRole(SectorService::CLEANER_ROLE);
        $isCustomer = $user->hasRole('customer') && ! $isCleaner;

        if ($isCustomer) {
            $orders = Order::where('user_id', $currentUserId)
            ->select('id', 'car_number','cloth_count','cloth_service', 'start_date', 'renew_date')
            ->get();
        } elseif ($isCleaner) {
            // Same cars as Today Task: the ones assigned to this cleaner.
            $orders = Order::query()
                ->leftJoin('users as customers', 'orders.user_id', '=', 'customers.id')
                ->leftJoin('userprofiles as up', 'orders.user_id', '=', 'up.user_id')
                ->leftJoin('internaltypes', 'orders.cleaning_type', '=', 'internaltypes.id')
                ->where('orders.assigned_user_id', $currentUserId)
                ->whereIn('orders.status', [2, 4])
                ->select(
                    'orders.id',
                    'orders.car_number',
                    'orders.status',
                    'orders.start_date',
                    'orders.renew_date',
                    'customers.name as user_name',
                    'customers.mobile as user_mobile',
                    'up.house_no',
                    'up.office_time',
                    'internaltypes.name as internaltype_name'
                )
                ->orderBy('orders.status')
                ->get();
        } else {
            $orders = Order::forSectors($sectorIds)
            ->select('id', 'car_number','cloth_count','cloth_service', 'start_date', 'renew_date')
            ->get();
        }

        return view('backend.index', compact('totalCustomers','orders', 'totalCleaner', 'totalOrders', 'totalActiveOrders', 'totalNewOrders', 'totalExpiredOrders', 'totalHoldOrders', 'expiredClothcount', 'totalRevenue', 'monthRevenue', 'sectorOptions', 'selectedSector', 'canSeeAllSectors', 'franchiseOptions', 'selectedFranchise', 'isCleaner', 'isCustomer'));
    }

    /**
     * Captured revenue for the given sectors, all time and current month.
     * Payments are the source of truth for revenue, not the order amount.
     *
     * @param  array<int, int>|null  $sectorIds
     * @return array<int, float>
     */
    private function revenue(?array $sectorIds)
    {
        $payments = DB::table('payment_history')->where('payment_status', 'captured');

        if ($sectorIds !== null) {
            $payments->whereIn('sector_id', $sectorIds);
        }

        $totalRevenue = (clone $payments)->sum('payment_amount');

        $monthRevenue = (clone $payments)
            ->whereBetween('payment_date_time', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ])
            ->sum('payment_amount');

        return [$totalRevenue, $monthRevenue];
    }
}
