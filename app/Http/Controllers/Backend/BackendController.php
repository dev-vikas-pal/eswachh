<?php

namespace App\Http\Controllers\Backend;

use App\Http\Controllers\Controller;

use App\Models\Role;
use App\Models\User;
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
    public function index()
    {
        $customerRoleIds = [5];
        $cleanerRoleIds = [4];

        $totalCustomers = User::whereHas('roles', function ($query) use ($customerRoleIds) {
            $query->whereIn('id', $customerRoleIds);
        })->count();

        $totalCleaner = User::whereHas('roles', function ($query) use ($cleanerRoleIds) {
            $query->whereIn('id', $cleanerRoleIds);
        })->count();

        $totalOrders = Order::where('status', 2)->count();

        $totalActiveOrders = Order::where('status', 2)
        ->where('renew_date', '>', now())
        ->whereNotNull('assigned_user_id')
        ->count();

        $totalNewOrders = Order::where('status', 2)
        ->where('renew_date', '>', Carbon::today())
        ->whereNull('assigned_user_id')
        ->count();

        $totalExpiredOrders = Order::whereNotNull('renew_date')
        ->where('status', 2)
        ->where('renew_date', '<', Carbon::today())
        ->count();
        
        $totalHoldOrders = Order::whereNotNull('renew_date')
        ->where('status', 4)
        ->count();

        $expiredClothcount = Order::where('status', 2)->where('cloth_service', 1)
        ->where('cloth_count', '<', 5)
        ->count();


        $currentUserId = auth()->user()->id;

        $user = auth()->user();
        $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
        if ($roles == 'customer') {
            $orders = Order::where('user_id', $currentUserId)
            ->select('id', 'car_number','cloth_count','cloth_service', 'start_date', 'renew_date')
            ->get();
        }else if ($roles == 'cleaner'){
            $orders = Order::where('assigned_user_id', $currentUserId)
            ->select('id', 'car_number','cloth_count','cloth_service', 'start_date', 'renew_date')
            ->get();
        }else{
            $orders = Order::select('id', 'car_number','cloth_count','cloth_service', 'start_date', 'renew_date')
            ->get();
        }

        return view('backend.index', compact('totalCustomers','orders', 'totalCleaner', 'totalOrders', 'totalActiveOrders', 'totalNewOrders', 'totalExpiredOrders', 'totalHoldOrders', 'expiredClothcount'));
    }
}
