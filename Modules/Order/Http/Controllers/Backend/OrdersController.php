<?php

namespace Modules\Order\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Yajra\DataTables\DataTables;
use Modules\Car\Models\Car;
use App\Models\User;
use Modules\Package\Models\Package;
use Modules\Internaltype\Models\Internaltype;
use Modules\Duration\Models\Duration;
use Modules\Order\Models\Order;
use Modules\Cloth\Models\Cloth;
use Razorpay\Api\Api;
use App\Services\SMSService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Log;

class OrdersController extends BackendBaseController
{
    use Authorizable;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Orders';

        // module name
        $this->module_name = 'orders';

        // directory path of the module
        $this->module_path = 'order::backend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Order\Models\Order";
    }
    public function topUp(Request $request, $orderId)
    {
        $user = auth()->user();
        $order_info = Order::where('user_id', $user->id)
            ->where('id', $orderId)
            ->select(['id', 'cloth_count', 'cloth_id'])
            ->firstOrFail();

        $clothList = Cloth::latest()->select('id', 'name', 'count', 'price')->get();
        return view(
            "$this->module_path.$this->module_name.add_top_up",
            compact('order_info', 'clothList', 'orderId')
        );
    }
    public function addTopUp(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'order_id' => 'required',
                'cloth_id' => 'required',
            ],
            [
                'order_id.required' => 'Order Id is required.',
                'cloth_id.required' => 'Cloth Id is required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $user = auth()->user();
        $order = Order::where('user_id', $user->id)->where('id', $request->order_id)->firstOrFail();
        $cloth = Cloth::where('id', $request->cloth_id)->firstOrFail();
        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $razorPayorder = $api->order->create([
            'amount' => $cloth->price * 100,
            'currency' => 'INR',
            'receipt' => Str::uuid(),
            'payment_capture' => 1,
            'notes' => [
                'order_type' => 'online',
                'customer_id' => $user->id,
                'local_order_id' => $order->id,
                'cloth_id' => $cloth->id ?? 0,
            ],
        ]);
        Log::info("Sending request to Razorpay for cloth topup");
        Log::info("Request => " . print_r($order, true));
        return response()->json(['success' => true, 'razorpay_order_id' => $razorPayorder->id]);
    }
    public function addTopUpComplete(Request $request)
    {
        $input = $request->all();
        Log::info("Receiving response from Razorpay for cloth topup");
        Log::info("Response => " . print_r($input, true));
        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        $local_order_id = $payment['notes']['local_order_id'] ?? '';
        $cloth_id = $payment['notes']['cloth_id'] ?? '';

        if (count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $acquirerData = $payment['acquirer_data'];
                $order = Order::find($local_order_id);
                $cloth = Cloth::find($cloth_id);
                if ($order) {
                    $user = auth()->user();
                    $oldOrder = $order->replicate();
                    DB::table('order_history')->insert([
                        'order_id' => $order->id,
                        'order_data' => json_encode($oldOrder->toArray()),
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $order->cloth_id = $cloth_id;
                    $order->cloth_count = $order->cloth_count + $cloth->count;
                    $order->save();
                    SMSService::sendWhatsAppMsg($order->user->mobile, 'cloth_topup_notification_customer', [$cloth->name ?? '', $order->cloth_count]);
                }
            } catch (\Exception $e) {
                return  $e->getMessage();
                \Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }
        $paymentData = [
            'user_id' => $user->id ?? 0,
            'order_id' => $order->id ?? 0,
            'payment_amount' => $payment['amount'] / 100,
            'currency' => 'INR',
            'payment_status' => $payment['status'] ?? 'Pending',
            'payment_method' => $payment['method'] ?? '',
            'payment_date_time' => now(),
            'transaction_id' => $acquirerData->upi_transaction_id ?? '',
            'payment_gateway' => 'Razorpay',
            'payment_for' => 'Cloth',
        ];
        DB::table('payment_history')->insert($paymentData);
        $message = 'Payment successful, your cloth count has been increased successfully.';
        \Session::put('success', $message);
        return redirect()->route('backend.orders.show', ['order' => $order]);
    }
    public function renew(Request $request)
    {

        $module_name = $this->module_name;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        if (empty($request->cloth_service)) {
            $request->merge(['cloth_service' => 0]);
        }

        $isNewRecord = true;
        if (!empty($request->order_id)) {
            $isNewRecord = false;
        }

        $validator = Validator::make(
            $request->all(),
            [
                'car_id' => 'required',
                'package_id' => 'required',
                'cleaning_type' => 'required',
                'pakage_type' => 'required',
                //'car_number' => 'required|unique:orders,car_number',
                'car_number' => [
                    'required',
                    Rule::unique('orders', 'car_number')->where(function ($query) use ($isNewRecord, $request) {
                        if (!$isNewRecord) {
                            $query->where('id', '!=', $request->order_id);
                        }
                        $query->whereNull('deleted_at');
                    }),
                    'regex:/^[^\s]+$/',
                ],
            ],
            [
                'car_id.required' => 'Car Name is required.',
                'package_id.required' => 'Package is required.',
                'cleaning_type.required' => 'Cleaning Type is required.',
                'pakage_type.required' => 'Duration is required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        $user = auth()->user();
        if (empty($request->order_id)) {
            $request->merge(['user_id' => $user->id]);
            $$module_name_singular = $module_model::create($request->all());
            flash(icon() . "New '" . Str::singular('Order') . "' Added")->success()->important();
            $new_order = true;
        } else {
            $$module_name_singular = $module_model::where('user_id', $user->id)->where('id', $request->order_id)->firstOrFail();
            $request->merge(['car_id' => $$module_name_singular->car_id, 'package_id' => $$module_name_singular->package_id]);
            $new_order = false;
        }

        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $local_order_id = $$module_name_singular->id;
        $order = new Order();
        $response = $order->getPrice($request);
        $pdata =  json_decode($response->getContent(), true);
        $final_price =  $pdata['final_price'] ?? 0;
        $order = $api->order->create([
            'amount' => $final_price * 100,
            'currency' => 'INR',
            'receipt' => Str::uuid(),
            'payment_capture' => 1,
            'notes' => [
                'order_type' => 'online',
                'customer_id' => $user->id,
                'local_order_id' => $local_order_id,
                'new_order' => $new_order,
                'pakage_type' => $request->pakage_type,
                'cleaning_type' => $request->cleaning_type,
                'cloth_service' => $request->cloth_service ?? 0,
                'cloth_id' => $request->cloth_id ?? 0,
            ],
        ]);
        Log::info("Sending request to Razorpay for payment capture");
        Log::info("Request => " . print_r($order, true));

        return response()->json(['success' => true, 'razorpay_order_id' => $order->id]);
    }
    public function renewLoginFree(Request $request)
    {

        $module_name = $this->module_name;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        if (empty($request->cloth_service)) {
            $request->merge(['cloth_service' => 0]);
        }

        $isNewRecord = true;
        if (!empty($request->order_id)) {
            $isNewRecord = false;
        }

        $validator = Validator::make(
            $request->all(),
            [
                'car_id' => 'required',
                'package_id' => 'required',
                'cleaning_type' => 'required',
                'pakage_type' => 'required',
                //'car_number' => 'required|unique:orders,car_number',
                'car_number' => [
                    'required',
                    Rule::unique('orders', 'car_number')->where(function ($query) use ($isNewRecord, $request) {
                        if (!$isNewRecord) {
                            $query->where('id', '!=', $request->order_id);
                        }
                        $query->whereNull('deleted_at');
                    }),
                    'regex:/^[^\s]+$/',
                ],
            ],
            [
                'car_id.required' => 'Car Name is required.',
                'package_id.required' => 'Package is required.',
                'cleaning_type.required' => 'Cleaning Type is required.',
                'pakage_type.required' => 'Duration is required.',
            ]
        );

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }
        if (!$request->has('token')) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        $payload = decrypt($request->token);
        if (now()->timestamp > $payload['expires_at']) {
            return response()->json(['error' => 'Token expired'], 403);
        }
        $user = User::find($payload['user_id']);

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if (empty($request->order_id)) {
            $request->merge(['user_id' => $user->id]);
            $$module_name_singular = $module_model::create($request->all());
            flash(icon() . "New '" . Str::singular('Order') . "' Added")->success()->important();
            $new_order = true;
        } else {
            $$module_name_singular = $module_model::where('user_id', $user->id)->where('id', $request->order_id)->firstOrFail();
            $request->merge(['car_id' => $$module_name_singular->car_id, 'package_id' => $$module_name_singular->package_id]);
            $new_order = false;
        }

        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $local_order_id = $$module_name_singular->id;
        $order = new Order();
        $response = $order->getPrice($request);
        $pdata =  json_decode($response->getContent(), true);
        $final_price =  $pdata['final_price'] ?? 0;
        $order = $api->order->create([
            'amount' => $final_price * 100,
            'currency' => 'INR',
            'receipt' => Str::uuid(),
            'payment_capture' => 1,
            'notes' => [
                'order_type' => 'online',
                'customer_id' => $user->id,
                'local_order_id' => $local_order_id,
                'new_order' => $new_order,
                'pakage_type' => $request->pakage_type,
                'cleaning_type' => $request->cleaning_type,
                'cloth_service' => $request->cloth_service ?? 0,
                'cloth_id' => $request->cloth_id ?? 0,
            ],
        ]);
        Log::info("Sending request to Razorpay for payment capture");
        Log::info("Request => " . print_r($order, true));

        return response()->json(['success' => true, 'name' => $user->name, 'mobile' => $user->mobile, 'email' => $user->email, 'razorpay_order_id' => $order->id]);
    }

    public function loginFreerenewComplete(Request $request)
    {
        $input = $request->all();
        Log::info("Receiving response from Razorpay for payment capture");
        Log::info("Response => " . print_r($input, true));
        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        if (!empty($input['razorpay_payment_id'])) {
            $payment = $api->payment->fetch($input['razorpay_payment_id']);
            $local_order_id = $payment['notes']['local_order_id'] ?? '';
            $pakage_type = $payment['notes']['pakage_type'] ?? '';
            $cleaning_type = $payment['notes']['cleaning_type'] ?? '';
            $cloth_service = $payment['notes']['cloth_service'] ?? 0;
            $new_order = $payment['notes']['new_order'] ?? false;
            $cloth_id = $payment['notes']['cloth_id'] ?? '';

            if (count($input)) {
                try {
                    $acquirerData = $payment['acquirer_data'];
                    $order = Order::find($local_order_id);
                    $cloth = Cloth::find($cloth_id);
                    $clothCount = $cloth->count ?? 0;
                    if ($order) {
                        //  $user = auth()->user();
                        $oldOrder = $order->replicate();
                        DB::table('order_history')->insert([
                            'order_id' => $order->id,
                            'order_data' => json_encode($oldOrder->toArray()),
                            'created_by' => $order->user_id,
                            'updated_by' => $order->user_id,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);

                        $duration = Duration::where('id', $pakage_type)->select('duration')->first();
                        $order->razorpay_order_id = $payment['order_id'];
                        $order->pakage_type = $pakage_type;
                        $order->cleaning_type = $cleaning_type;
                        $order->cloth_service = $cloth_service;
                        $order->cloth_id = $cloth_id;
                        $order->cloth_count = $order->cloth_count + $clothCount;
                        $order->status = $payment['status'] == 'captured' ? 2 : 1;
                        $order->amount = $payment['amount'] / 100;
                        $order->paid_amount = $payment['amount'] / 100;
                        $order->payment_date = Carbon::now();

                        //$order->start_date = Carbon::now();
                        //$order->renew_date = Carbon::now()->addMonths($duration->duration);

                        $currentDate = Carbon::now();
                        $order->renew_date = Carbon::parse($order->renew_date);
                        // if ($order->renew_date->startOfDay() >= $currentDate->startOfDay()) {
                        $order->start_date = Carbon::now();
                        $order->renew_date = $order->renew_date->startOfDay()->addMonths($duration->duration);
                        // } else {
                        //     $order->renew_date = $currentDate->startOfDay()->addMonths($duration->duration);
                        // }

                        $order->payment_mode = $payment['method'];
                        $order->transaction_id = $acquirerData->upi_transaction_id ?? '';
                        $order->order_type = 'online';
                        $order->payment_id = $payment['id'];
                        $order->save();
                        $user = User::find($order->user_id);

                        $package = Package::where('id', $order->package_id)->select('name')->first();
                        $clothService = $order->cloth_service == 1 ? "Yes - $cloth->name" : "No";
                        if ($new_order) {
                            SMSService::sendWhatsAppMsg($order->user->mobile, 'subscription_notification_customer2', [$package->name, $duration->duration, $order->car_number, $clothService, '****', '***']);
                            SMSService::sendWhatsAppMsg('8650316068', 'subscription_notification_admin', [$order->car_number, $package->name, $duration->duration]);
                        } else {
                            SMSService::sendWhatsAppMsg($order->user->mobile, 'renew_notification_customer', [$package->name, $duration->duration, $order->car_number, $clothService]);
                        }
                    }
                } catch (\Exception $e) {
                    return  $e->getMessage();
                    \Session::put('error', $e->getMessage());
                    return redirect()->back();
                }
            }
            $paymentData = [
                'user_id' => $user->id ?? 0,
                'order_id' => $order->id ?? 0,
                'payment_amount' => $payment['amount'] / 100,
                'currency' => 'INR',
                'payment_status' => $payment['status'] ?? 'Pending',
                'payment_method' => $payment['method'] ?? '',
                'payment_date_time' => now(),
                'transaction_id' => $acquirerData->upi_transaction_id ?? '',
                'payment_gateway' => 'Razorpay',
                'payment_for' => 'Subscription',
            ];
            DB::table('payment_history')->insert($paymentData);

            \Session::put('success', 'Payment successful, your order has been placed successfully.');
        }
        return view("order::frontend.orders.success");
    }
    public function renewComplete(Request $request)
    {
        $input = $request->all();
        Log::info("Receiving response from Razorpay for payment capture");
        Log::info("Response => " . print_r($input, true));
        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);
        $local_order_id = $payment['notes']['local_order_id'] ?? '';
        $pakage_type = $payment['notes']['pakage_type'] ?? '';
        $cleaning_type = $payment['notes']['cleaning_type'] ?? '';
        $cloth_service = $payment['notes']['cloth_service'] ?? 0;
        $new_order = $payment['notes']['new_order'] ?? false;
        $cloth_id = $payment['notes']['cloth_id'] ?? '';

        if (count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $acquirerData = $payment['acquirer_data'];
                $order = Order::find($local_order_id);
                $cloth = Cloth::find($cloth_id);
                $clothCount = $cloth->count ?? 0;
                if ($order) {
                    $user = auth()->user();
                    $oldOrder = $order->replicate();
                    DB::table('order_history')->insert([
                        'order_id' => $order->id,
                        'order_data' => json_encode($oldOrder->toArray()),
                        'created_by' => $user->id,
                        'updated_by' => $user->id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    $duration = Duration::where('id', $pakage_type)->select('duration')->first();
                    $order->razorpay_order_id = $payment['order_id'];
                    $order->pakage_type = $pakage_type;
                    $order->cleaning_type = $cleaning_type;
                    $order->cloth_service = $cloth_service;
                    $order->cloth_id = $cloth_id;
                    $order->cloth_count = $order->cloth_count + $clothCount;
                    $order->status = $payment['status'] == 'captured' ? 2 : 1;
                    $order->amount = $payment['amount'] / 100;
                    $order->paid_amount = $payment['amount'] / 100;
                    $order->payment_date = Carbon::now();

                    //$order->start_date = Carbon::now();
                    //$order->renew_date = Carbon::now()->addMonths($duration->duration);

                    $currentDate = Carbon::now();
                    $order->renew_date = Carbon::parse($order->renew_date);
                    // if ($order->renew_date->startOfDay() >= $currentDate->startOfDay()) {
                    $order->start_date = Carbon::now();
                    $order->renew_date = $order->renew_date->startOfDay()->addMonths($duration->duration);
                    // } else {
                    //     $order->renew_date = $currentDate->startOfDay()->addMonths($duration->duration);
                    // }

                    $order->payment_mode = $payment['method'];
                    $order->transaction_id = $acquirerData->upi_transaction_id ?? '';
                    $order->order_type = 'online';
                    $order->payment_id = $payment['id'];
                    $order->save();
                    $user = User::find($order->user_id);

                    $package = Package::where('id', $order->package_id)->select('name')->first();
                    $clothService = $order->cloth_service == 1 ? "Yes - $cloth->name" : "No";
                    if ($new_order) {
                        SMSService::sendWhatsAppMsg($order->user->mobile, 'subscription_notification_customer2', [$package->name, $duration->duration, $order->car_number, $clothService, '****', '***']);
                        SMSService::sendWhatsAppMsg('8650316068', 'subscription_notification_admin', [$order->car_number, $package->name, $duration->duration]);
                    } else {
                        SMSService::sendWhatsAppMsg($order->user->mobile, 'renew_notification_customer', [$package->name, $duration->duration, $order->car_number, $clothService]);
                    }
                }
            } catch (\Exception $e) {
                return  $e->getMessage();
                \Session::put('error', $e->getMessage());
                return redirect()->back();
            }
        }
        $paymentData = [
            'user_id' => $user->id ?? 0,
            'order_id' => $order->id ?? 0,
            'payment_amount' => $payment['amount'] / 100,
            'currency' => 'INR',
            'payment_status' => $payment['status'] ?? 'Pending',
            'payment_method' => $payment['method'] ?? '',
            'payment_date_time' => now(),
            'transaction_id' => $acquirerData->upi_transaction_id ?? '',
            'payment_gateway' => 'Razorpay',
            'payment_for' => 'Subscription',
        ];
        DB::table('payment_history')->insert($paymentData);

        \Session::put('success', 'Payment successful, your order has been placed successfully.');
        return redirect()->route('backend.orders.show', ['order' => $order]);
    }
    public function store(Request $request)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Store';

        $order = new \Modules\Order\Models\Order();
        $response = $order->getPrice($request);
        $pdata =  json_decode($response->getContent(), true);
        $paid_amount =  $pdata['final_price'] ?? 0;
        $request['paid_amount'] = $paid_amount;
        $request['renew_date'] = Carbon::parse($request->start_date)->addMonths($request->pakage_type);

        $$module_name_singular = $module_model::create($request->all());
        flash(icon() . "New '" . Str::singular($module_title) . "' Added")->success()->important();

        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id);


        return redirect("admin/$module_name");
    }
    public function renewNotification(Request $request)
    {
        $ids = $request->input('ids');
        $orders = Order::whereIn('id', $ids)->get();
        foreach ($orders as $key => $order) {
            if (!empty($request->cloth_notification)) {
                if ($order->cloth_service == 1 && $order->cloth_count <= 5) {
                    SMSService::sendWhatsAppMsg($order->user->mobile, 'lower_cloth_count_cutomer');
                }
            } elseif (!empty($request->hold_notification)) {
                $renewDate = Carbon::parse($order->renew_date)->format('Y-m-d');
                SMSService::sendWhatsAppMsg($order->user->mobile, 'hold_cars', [$renewDate]);
            } elseif (!empty($request->dynamic_notification)) {
                if(!empty($request->dynamic_template_name)){
                    SMSService::sendWhatsAppMsg($order->user->mobile, $request->dynamic_template_name);
                }
            } else {
                $renewDate = Carbon::parse($order->renew_date)->format('Y-m-d');
                SMSService::sendWhatsAppMsg($order->user->mobile, 'subscription_expire', [$renewDate]);
            }
        }
        return response()->json(['message' => 'Notofication send successfully.']);
    }
    public function index_data()
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        $page_heading = label_case($module_title);
        $title = $page_heading . ' ' . label_case($module_action);

        $$module_name = Order::join('users', 'orders.user_id', '=', 'users.id')
            ->select('orders.*', 'users.mobile');

        $user = auth()->user();
        $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';

        if ($roles == 'customer') {
            $$module_name = $$module_name->where('user_id', $user->id);
        }
        if ($roles == 'cleaner') {
            $$module_name = $$module_name->where('assigned_user_id', $user->id);
            $$module_name = $$module_name->where(function ($query) {
                $query->where('orders.status', 2)
                    ->orWhere('orders.status', 4);
            });
        }
        $$module_name = $$module_name->whereNotNull('renew_date');
        $request = request()->all();
        if (!empty($request['filter_status']) && $request['filter_status'] != '*') {
            $$module_name = $$module_name->where('orders.status', $request['filter_status']);
        }
        if (!empty($request['filter_package_id']) && $request['filter_package_id'] != '*') {
            $$module_name = $$module_name->where('package_id', $request['filter_package_id']);
        }
        if (isset($request['filter_assigned_user_id']) && $request['filter_assigned_user_id'] != '*') {
            if ($request['filter_assigned_user_id'] !== 'null') {
                $$module_name = $$module_name->where('assigned_user_id', $request['filter_assigned_user_id']);
            } else {
                $$module_name = $$module_name->whereNull('assigned_user_id');
            }
        }
        if (!empty($request['filter_order_date']) && $request['filter_order_date'] != '*') {
            $$module_name = $$module_name->whereDate('created_at', $request['filter_order_date']);
        }
        if (!empty($request['filter_renew_date_start'])) {
            $$module_name = $$module_name->whereDate('renew_date', '>', $request['filter_renew_date_start']);
        }
        if (!empty($request['filter_renew_date_end'])) {
            $$module_name = $$module_name->whereDate('renew_date', '<', $request['filter_renew_date_end']);
        }
        if (!empty($request['filter_clothexpired'])) {
            $$module_name = $$module_name->where('cloth_service', 1)->where('cloth_count', '<=', 10);
        }

        $$module_name = $$module_name->orderBy('orders.status', 'ASC');

        return Datatables::of($$module_name)
            ->addColumn('checkbox', function ($data) {
                return '<input type="checkbox" class="row-checkbox" data-id="' . $data->id . '">';
            })
            ->addColumn('action', function ($data) {
                $module_name = $this->module_name;

                return view('backend.includes.action_column', compact('module_name', 'data'));
            })
            ->editColumn('updated_at', function ($data) {
                $module_name = $this->module_name;

                $diff = Carbon::now()->diffInHours($data->updated_at);
                if ($data->updated_at) {
                    if ($data->updated_at && $diff < 25) {
                        return $data->updated_at->diffForHumans();
                    } else {
                        return $data->updated_at->isoFormat('llll');
                    }
                } else {
                    return 'NA';
                }
            })
            ->editColumn('renew_date', function ($data) {
                return Carbon::parse($data->renew_date)->format('d-m-Y');
            })
            ->editColumn('user_name', function ($data) {
                return User::find($data->user_id)->name ?? '';
            })
            // ->editColumn('mobile', function ($data) {
            //     return User::find($data->user_id)->mobile ?? '';
            // })
            ->editColumn('assigned_user', function ($data) {
                return User::find($data->assigned_user_id)->name ?? 'Not Assigned';
            })
            ->editColumn('package_name', function ($data) {
                return Package::find($data->package_id)->name ?? '';
            })
            ->editColumn('car_name', function ($data) {
                return Car::find($data->car_id)->name ?? '';
            })
            ->editColumn('status', function ($data) {
                $select_options = [
                    '1' => 'Pending',
                    '2' => 'Active',
                    '3' => 'Deactive',
                    '4' => 'Hold'
                ];
                return $select_options[$data->status] ?? '';
            })
            ->rawColumns(['checkbox', 'name', 'action'])
            ->make(true);
    }
    public function show($id)
    {
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);
        $module_action = 'Show';

        $$module_name = $module_model::select();
        $user = auth()->user();
        $roles = !empty($user) ? $user->roles()->pluck('name')[0] : '';
        if ($roles == 'customer') {
            $$module_name_singular = \Modules\Order\Models\Order::leftjoin('packages', 'orders.package_id', '=', 'packages.id')
                ->leftjoin('cars', 'orders.car_id', '=', 'cars.id')
                ->leftjoin('internaltypes', 'orders.cleaning_type', '=', 'internaltypes.id')
                ->leftjoin('durations', 'orders.pakage_type', '=', 'durations.id')
                ->leftjoin('users', 'orders.assigned_user_id', '=', 'users.id')
                ->leftjoin('users as users2', 'orders.user_id', '=', 'users2.id')
                ->leftjoin('cloths as c', 'orders.cloth_id', '=', 'c.id')
                ->select('orders.*', 'packages.name as package_name', 'cars.name as car_name', 'users.name as assigned_user', 'users2.name as user_name', 'users2.mobile as user_mobile', 'users.mobile as cleaner_mobile', 'internaltypes.name as internaltype_name', 'durations.name as duration_name', 'c.name as cloth_name')
                ->where('orders.id', $id)
                ->where('orders.user_id', $user->id)
                ->firstOrFail();
        } else if ($roles == 'cleaner') {
            $$module_name_singular = \Modules\Order\Models\Order::leftjoin('packages', 'orders.package_id', '=', 'packages.id')
                ->leftjoin('cars', 'orders.car_id', '=', 'cars.id')
                ->leftjoin('internaltypes', 'orders.cleaning_type', '=', 'internaltypes.id')
                ->leftjoin('durations', 'orders.pakage_type', '=', 'durations.id')
                ->leftjoin('users', 'orders.assigned_user_id', '=', 'users.id')
                ->leftjoin('users as users2', 'orders.user_id', '=', 'users2.id')
                ->leftjoin('cloths as c', 'orders.cloth_id', '=', 'c.id')
                ->select('orders.*', 'packages.name as package_name', 'cars.name as car_name', 'users.name as assigned_user', 'users2.name as user_name', 'users2.mobile as user_mobile', 'users.mobile as cleaner_mobile', 'internaltypes.name as internaltype_name', 'durations.name as duration_name', 'c.name as cloth_name')
                ->where('orders.id', $id)
                ->where('orders.assigned_user_id', $user->id)
                ->firstOrFail();
        } else {
            $$module_name_singular = \Modules\Order\Models\Order::leftjoin('packages', 'orders.package_id', '=', 'packages.id')
                ->leftjoin('cars', 'orders.car_id', '=', 'cars.id')
                ->leftjoin('internaltypes', 'orders.cleaning_type', '=', 'internaltypes.id')
                ->leftjoin('durations', 'orders.pakage_type', '=', 'durations.id')
                ->leftjoin('users', 'orders.assigned_user_id', '=', 'users.id')
                ->leftjoin('users as users2', 'orders.user_id', '=', 'users2.id')
                ->leftjoin('cloths as c', 'orders.cloth_id', '=', 'c.id')
                ->select('orders.*', 'packages.name as package_name', 'cars.name as car_name', 'users.name as assigned_user', 'users2.name as user_name', 'users2.mobile as user_mobile', 'users.mobile as cleaner_mobile', 'internaltypes.name as internaltype_name', 'durations.name as duration_name', 'c.name as cloth_name')
                ->where('orders.id', $id)
                ->firstOrFail();
        }
        //  echo"<pre>";print_r($$module_name_singular);die;
        $select_options = [
            '1' => 'Pending',
            '2' => 'Active',
            '3' => 'Deactive',
            '4' => 'Hold'
        ];
        $$module_name_singular->status = $select_options[$$module_name_singular->status];
        logUserAccess($module_title . ' ' . $module_action . ' | Id: ' . $$module_name_singular->id ?? '');

        return view(
            "$module_path.$module_name.show",
            compact('module_title', 'module_name', 'module_path', 'module_icon', 'module_name_singular', 'module_action', "$module_name_singular")
        );
    }
}
