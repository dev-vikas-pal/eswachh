<?php

namespace App\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Services\SectorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laracasts\Flash\Flash;
use Modules\Order\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use App\Services\SMSService;

class CleanerController extends Controller
{
    use Authorizable;

    public $module_title;

    public $module_name;

    public $module_path;

    public $module_icon;

    public $module_model;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Cleaner Order';

        // module name
        $this->module_name = 'cleaners';

        // directory path of the module
        $this->module_path = 'cleaners';

        // module icon
        $this->module_icon = 'fa-solid fa-user-shield';

        // module model name, path
        $this->module_model = "Modules\Order\Models\Order";
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $sectorIds = SectorService::selectedSectorIds($request->filter_sector_id);
        $sectorOptions = SectorService::sectorOptions();
        $canSeeAllSectors = ! SectorService::isFranchiseOwner();

        $cleanerDatas = SectorService::scopeByUserSector(
            \App\Models\User::whereHas('roles', function ($query) {
                $query->where('name', 'cleaner');
            }),
            $sectorIds,
            'users.id'
        )->get();

        $module_action = 'List';

        $user = auth()->user();

        $data = \Modules\Order\Models\Order::leftjoin('packages', 'orders.package_id', '=', 'packages.id')
            ->leftjoin('cars', 'orders.car_id', '=', 'cars.id')
            ->leftjoin('internaltypes', 'orders.cleaning_type', '=', 'internaltypes.id')
            ->leftjoin('durations', 'orders.pakage_type', '=', 'durations.id')
            ->leftjoin('users', 'orders.assigned_user_id', '=', 'users.id')
            ->leftjoin('users as users2', 'orders.user_id', '=', 'users2.id')
            ->leftjoin('userprofiles as up', 'orders.user_id', '=', 'up.user_id')
            ->leftjoin('cleaner_activities as ca', function ($join) use ($request) {
                $join->on('orders.id', '=', 'ca.order_id');
                $filter_date = $request->filter_date ?? Carbon::now()->toDateString();
                $join->where('ca.date', $filter_date);
            })
            ->leftJoin('cities', 'up.city_id', '=', 'cities.id')
            ->leftJoin('areas', 'up.area_id', '=', 'areas.id')
            ->leftJoin('sectors', 'up.sector_id', '=', 'sectors.id')
            ->leftJoin('societies', 'up.society_id', '=', 'societies.id')
            ->select(
                'orders.*',
                'orders.status as order_status',
                'packages.name as package_name',
                'cars.name as car_name',
                'users.name as assigned_user',
                'users2.name as user_name',
                'users2.mobile as user_mobile',
                'up.house_no',
                'up.office_time',
                'users.mobile as cleaner_mobile',
                'internaltypes.name as internaltype_name',
                'durations.name as duration_name',
                'ca.status as status',
               // DB::raw("CONCAT(cities.name, ', ', areas.name, ', ', sectors.name, ', ', societies.name, ', ', up.house_no) as house_no")
            );

        if ($user) {
            $roles = $user->roles()->pluck('name')->first();
            if ($roles == 'customer') {
                $data = $data->where('orders.user_id', $user->id);
            } elseif ($roles == 'cleaner') {
                $data = $data->where('orders.assigned_user_id', $user->id)->where('orders.status', 2);
            }
            if (!empty($request->filter_assigned_user_id)) {
                $data = $data->where('orders.assigned_user_id', $request->filter_assigned_user_id);
            }
        }
        $data = $data->forSectors($sectorIds);

        $data = $data->where(function($query) {
            $query->where('orders.status', 2)
                  ->orWhere('orders.status', 4);
        })->orderBy('orders.status', 'ASC');

        $filter['filter_assigned_user_id'] = $request->filter_assigned_user_id ?? '';
        $filter['filter_date'] = $request->filter_date ?? '';
        $filter['filter_sector_id'] = $request->filter_sector_id ?? '*';

        $$module_name = $data->paginate()->withQueryString();

        //   echo "<pre>";print_r($$module_name);die;

        Log::info(label_case($module_title . ' ' . $module_action) . ' | User:' . auth()->user()->name . '(ID:' . auth()->user()->id . ')');

        return view(
            "backend.$module_path.index",
            compact('module_title', 'module_name', "$module_name", 'module_icon', 'module_name_singular', 'module_action', 'cleanerDatas', 'filter', 'sectorOptions', 'canSeeAllSectors')
        );
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Create';

        $roles = Role::get();
        $permissions = Permission::where('deleted_at', null)->select('name', 'id')->get();

        Log::info(label_case($module_title . ' ' . $module_action) . ' | User:' . auth()->user()->name . '(ID:' . auth()->user()->id . ')');

        return view("backend.$module_name.create", compact('module_title', 'module_name', 'module_icon', 'module_action', 'roles', 'permissions'));
    }
    public function clothPickup(Request $request)

    {
        $user = auth()->user();
        $filter_car_number = $request->filter_car_number ?? '';
        $order = Order::where('car_number', $filter_car_number)->select('id', 'car_number', 'cloth_id', 'cloth_count')->first();
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);
        if (!empty($request->pick_up_count)) {
            $order = Order::where('id', $request->order_id)->select('id', 'car_number', 'user_id')->firstOrFail();
            $data = [
                'status' => 'Picked Up',
                'type' => 'debit',
                'created_by' => $user->id,
                'cloth_count' => $request->pick_up_count,
                'updated_by' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'date' => Carbon::now()->toDateString(),
            ];
            $conditions = [
                'status' => 'Picked Up',
                'order_id' => $order->id,
                'car_number' => $order->car_number,
                'date' => Carbon::now()->toDateString(),
            ];

            DB::table('cloth_services')->updateOrInsert($conditions, $data);
            \Session::put('success', 'Cloth picked up successfully.');
            SMSService::sendWhatsAppMsg($order->user->mobile, 'cloth_pickup_done_customer', [$data['date'], $request->pick_up_count]);
            $order = '';
        }
        return view("backend.cleaners.pick_cloth", compact('module_title', 'module_name', 'module_icon', 'order'));
    }
    public function clothDelivery(Request $request)
    {
        $user = auth()->user();
        $filter_car_number = $request->filter_car_number ?? '';
        $order = DB::table('cloth_services')->where('car_number', $filter_car_number)->where('status', 'Picked Up')->where('date', Carbon::now()->toDateString())->select('order_id', 'car_number', 'cloth_count')->first();
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);
        if (!empty($request->delivery_count) && $request->delivery_count <= $request->pickup_count) {
            $order = DB::table('cloth_services')->where('order_id', $request->order_id)->select('id', 'order_id', 'car_number', 'car_number')->first();
            $data = [
                'status' => 'Delivered',
                'type' => 'debit',
                'created_by' => $user->id,
                'cloth_count' => $request->delivery_count,
                'updated_by' => $user->id,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
                'date' => Carbon::now()->toDateString(),
            ];
            $conditions = [
                'status' => 'Delivered',
                'order_id' => $order->order_id,
                'car_number' => $order->car_number,
                'date' => Carbon::now()->toDateString(),
            ];

            DB::table('cloth_services')->updateOrInsert($conditions, $data);
            \Session::put('success', 'Cloth Delivered successfully.');
            $order = Order::find($order->order_id);
            if ($order) {
                $order->cloth_count -= $request->input('delivery_count');
                $order->save();
                SMSService::sendWhatsAppMsg($order->user->mobile, 'cloth_delivery_customer', [$data['date'], $request->delivery_count, $order->cloth_count]);
            }
            $order = '';
        }
        return view("backend.cleaners.delivery_cloth", compact('module_title', 'module_name', 'module_icon', 'order'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $module_name = $this->module_name;
        $module_name_singular = Str::singular($module_name);

        $user = auth()->user();

        if (!empty($request->post_data)) {
            foreach ($request->post_data as $key => $value) {
                $data = [
                    'status' => $value['status'] ?? 0,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ];

                $conditions = [
                    'order_id' => $key,
                    'date' => Carbon::now()->toDateString(),
                ];

                $order = \Modules\Order\Models\Order::with(['user' => function ($query) {
                    $query->select('id', 'mobile');
                }])
                    ->where('id', $key)
                    ->first();

                if ($order) {
                    if (!empty($data['status'])) {
                        SMSService::sendWhatsAppMsg($order->user->mobile, 'cleaning_done_customer', [$order->car_number]);
                    }
                }

                DB::table('cleaner_activities')->updateOrInsert($conditions, $data);
            }
        }
        return redirect("admin/$module_name")->with('flash_success', "$module_name added!");
    }
}
