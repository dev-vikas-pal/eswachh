<?php

namespace Modules\Order\Http\Controllers\Backend;

use App\Authorizable;
use App\Http\Controllers\Backend\BackendBaseController;
use App\Services\SectorService;
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
use App\Services\RazorpayService;
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

    /**
     * Stop a Franchise Owner from reaching an order outside their sectors.
     * Answers with a 404 rather than a 403 so the existence of orders in other
     * sectors is not disclosed.
     *
     * @param  int  $id
     * @return void
     */
    private function guardSectorAccess($id)
    {
        $sectorIds = SectorService::allowedSectorIds();

        if ($sectorIds === null) {
            return;
        }

        if (! Order::where('id', $id)->whereIn('sector_id', $sectorIds)->exists()) {
            abort(404);
        }
    }

    /**
     * Stop a Franchise Owner from booking an order for a customer that lives
     * in somebody else's sector.
     *
     * @param  int|null  $user_id
     * @return void
     */
    private function guardCustomerSector($user_id)
    {
        if (! SectorService::canAccessSector(Order::resolveSectorIdForUser($user_id))) {
            abort(403, 'This customer belongs to another sector.');
        }
    }

    /**
     * Normalise a cloth id coming from a request or from Razorpay notes.
     *
     * Razorpay returns every note as a string, and a select with nothing
     * chosen posts the literal "null", which the null coalescing operator does
     * not catch. Writing that straight to orders.cloth_id, an integer column,
     * fails with "Incorrect integer value: 'null'".
     *
     * @param  mixed  $value
     * @return int
     */
    private function clothId($value)
    {
        return is_numeric($value) ? (int) $value : 0;
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
        $api = RazorpayService::api();
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
        Log::info('Razorpay order created for cloth top up.', ['razorpay_order_id' => $razorPayorder->id, 'order_id' => $order->id]);

        RazorpayService::recordInitiated($razorPayorder->id, $order, $user->id, 'Cloth', $cloth->price);
        return response()->json(['success' => true, 'razorpay_order_id' => $razorPayorder->id]);
    }
    public function addTopUpComplete(Request $request)
    {
        $input = $request->all();
        Log::info('Razorpay callback for cloth top up.', [
            'razorpay_payment_id' => $input['razorpay_payment_id'] ?? null,
        ]);

        if (empty($input['razorpay_payment_id'])) {
            \Session::put('error', 'We did not receive a payment reference. Please try again.');

            return redirect()->route('backend.orders.index');
        }

        if (! RazorpayService::signatureIsValid($input)) {
            \Session::put('error', 'This payment could not be verified. Please contact support.');

            return redirect()->route('backend.orders.index');
        }

        $payment = RazorpayService::api()->payment->fetch($input['razorpay_payment_id']);

        $order = Order::find($payment['notes']['local_order_id'] ?? null);
        $cloth_id = $this->clothId($payment['notes']['cloth_id'] ?? 0);

        // Without this a refreshed callback would top the cloth count up twice.
        if (RazorpayService::alreadyProcessed($payment['id'])) {
            Log::info('Razorpay cloth top up already processed; ignoring repeat callback.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            return redirect()->route('backend.orders.show', ['order' => $order]);
        }

        RazorpayService::record($payment, $order, optional(auth()->user())->id ?? optional($order)->user_id, 'Cloth');

        if (! $order) {
            Log::error('Razorpay cloth top up has no matching order.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            \Session::put('error', 'Your payment was received but we could not match it to an order. Our team will contact you.');

            return redirect()->route('backend.orders.index');
        }

        try {
            $cloth = Cloth::find($cloth_id);
            $actorId = optional(auth()->user())->id ?? $order->user_id;

            DB::transaction(function () use ($order, $cloth, $cloth_id, $actorId) {
                DB::table('order_history')->insert([
                    'order_id' => $order->id,
                    'order_data' => json_encode($order->replicate()->toArray()),
                    'created_by' => $actorId,
                    'updated_by' => $actorId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $order->cloth_id = $cloth_id;
                $order->cloth_count = $order->cloth_count + ($cloth->count ?? 0);
                $order->save();
            });

            if ($mobile = optional($order->user)->mobile) {
                SMSService::sendWhatsAppMsg($mobile, 'cloth_topup_notification_customer', [$cloth->name ?? '', $order->cloth_count]);
            }
        } catch (\Throwable $e) {
            \Session::put('error', RazorpayService::reportFailure($e, $payment, $order));

            return redirect()->route('backend.orders.show', ['order' => $order]);
        }

        \Session::put('success', 'Payment successful, your cloth count has been increased successfully.');

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

        $api = RazorpayService::api();
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
                'cloth_id' => $this->clothId($request->cloth_id),
            ],
        ]);
        Log::info('Razorpay order created for renewal.', ['razorpay_order_id' => $order->id]);

        RazorpayService::recordInitiated($order->id, $$module_name_singular, $user->id, 'Subscription', $final_price);

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

        $api = RazorpayService::api();
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
                'cloth_id' => $this->clothId($request->cloth_id),
            ],
        ]);
        Log::info('Razorpay order created for renewal.', ['razorpay_order_id' => $order->id]);

        RazorpayService::recordInitiated($order->id, $$module_name_singular, $user->id, 'Subscription', $final_price);

        return response()->json(['success' => true, 'name' => $user->name, 'mobile' => $user->mobile, 'email' => $user->email, 'razorpay_order_id' => $order->id]);
    }

    public function loginFreerenewComplete(Request $request)
    {
        $input = $request->all();
        Log::info('Razorpay callback for login free renewal.', [
            'razorpay_payment_id' => $input['razorpay_payment_id'] ?? null,
        ]);

        if (empty($input['razorpay_payment_id'])) {
            \Session::put('error', 'We did not receive a payment reference. Please try again.');

            return view("order::frontend.orders.success");
        }

        if (! RazorpayService::signatureIsValid($input)) {
            \Session::put('error', 'This payment could not be verified. Please contact support.');

            return view("order::frontend.orders.success");
        }

        $payment = RazorpayService::api()->payment->fetch($input['razorpay_payment_id']);

        $order = Order::find($payment['notes']['local_order_id'] ?? null);

        if (RazorpayService::alreadyProcessed($payment['id'])) {
            Log::info('Razorpay login free renewal already processed; ignoring repeat callback.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            return view("order::frontend.orders.success");
        }

        RazorpayService::record($payment, $order, optional($order)->user_id, 'Subscription');

        if (! $order) {
            Log::error('Razorpay login free renewal has no matching order.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            \Session::put('error', 'Your payment was received but we could not match it to an order. Our team will contact you.');

            return view("order::frontend.orders.success");
        }

        try {
            $this->applyRenewal($order, $payment);
            \Session::put('success', 'Payment successful, your order has been placed successfully.');
        } catch (\Throwable $e) {
            \Session::put('error', RazorpayService::reportFailure($e, $payment, $order));
        }

        return view("order::frontend.orders.success");
    }
    public function renewComplete(Request $request)
    {
        $input = $request->all();
        Log::info('Razorpay callback for renewal.', [
            'razorpay_payment_id' => $input['razorpay_payment_id'] ?? null,
        ]);

        if (empty($input['razorpay_payment_id'])) {
            \Session::put('error', 'We did not receive a payment reference. Please try again.');

            return redirect()->route('backend.orders.index');
        }

        if (! RazorpayService::signatureIsValid($input)) {
            \Session::put('error', 'This payment could not be verified. Please contact support.');

            return redirect()->route('backend.orders.index');
        }

        $payment = RazorpayService::api()->payment->fetch($input['razorpay_payment_id']);

        $order = Order::find($payment['notes']['local_order_id'] ?? null);

        // Never renew twice off one payment.
        if (RazorpayService::alreadyProcessed($payment['id'])) {
            Log::info('Razorpay renewal already processed; ignoring repeat callback.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            return redirect()->route('backend.orders.show', ['order' => $order]);
        }

        RazorpayService::record($payment, $order, optional($order)->user_id, 'Subscription');

        if (! $order) {
            Log::error('Razorpay renewal has no matching order.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            \Session::put('error', 'Your payment was received but we could not match it to an order. Our team will contact you.');

            return redirect()->route('backend.orders.index');
        }

        try {
            $this->applyRenewal($order, $payment);
        } catch (\Throwable $e) {
            \Session::put('error', RazorpayService::reportFailure($e, $payment, $order));

            return redirect()->route('backend.orders.show', ['order' => $order]);
        }

        \Session::put('success', 'Payment successful, your order has been placed successfully.');

        return redirect()->route('backend.orders.show', ['order' => $order]);
    }

    /**
     * Apply a captured renewal payment to an order.
     *
     * Shared by the logged in and the login free renewal callbacks, which
     * previously carried two copies of this logic.
     *
     * @param  \Modules\Order\Models\Order  $order
     * @param  \Razorpay\Api\Payment|array  $payment
     * @return void
     */
    private function applyRenewal($order, $payment)
    {
        $pakage_type = $payment['notes']['pakage_type'] ?? '';
        $cleaning_type = $payment['notes']['cleaning_type'] ?? '';
        $cloth_service = $payment['notes']['cloth_service'] ?? 0;
        $new_order = $payment['notes']['new_order'] ?? false;
        $cloth_id = $this->clothId($payment['notes']['cloth_id'] ?? 0);

        $acquirerData = $payment['acquirer_data'] ?? null;
        $cloth = Cloth::find($cloth_id);
        $clothCount = $cloth->count ?? 0;
        $duration = Duration::where('id', $pakage_type)->select('duration')->first();
        $months = $duration->duration ?? 1;

        DB::transaction(function () use ($order, $payment, $acquirerData, $pakage_type, $cleaning_type, $cloth_service, $cloth_id, $clothCount, $months) {
            $actorId = optional(auth()->user())->id ?? $order->user_id;

            DB::table('order_history')->insert([
                'order_id' => $order->id,
                'order_data' => json_encode($order->replicate()->toArray()),
                'created_by' => $actorId,
                'updated_by' => $actorId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

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
            $order->start_date = Carbon::now();
            $order->renew_date = Carbon::parse($order->renew_date)->startOfDay()->addMonths($months);
            $order->payment_mode = $payment['method'];
            $order->transaction_id = $acquirerData->upi_transaction_id ?? '';
            $order->order_type = 'online';
            $order->payment_id = $payment['id'];
            $order->save();
        });

        // Notifications sit outside the transaction: a WhatsApp failure must
        // not roll back a paid renewal.
        $package = Package::where('id', $order->package_id)->select('name')->first();
        $clothService = $order->cloth_service == 1 ? 'Yes - ' . ($cloth->name ?? '') : 'No';
        $mobile = optional($order->user)->mobile;

        if (! $mobile) {
            return;
        }

        if ($new_order) {
            SMSService::sendWhatsAppMsg($mobile, 'subscription_notification_customer2', [$package->name ?? '', $months, $order->car_number, $clothService, '****', '***']);
            SMSService::sendWhatsAppMsg('8650316068', 'subscription_notification_admin', [$order->car_number, $package->name ?? '', $months]);
        } else {
            SMSService::sendWhatsAppMsg($mobile, 'renew_notification_customer', [$package->name ?? '', $months, $order->car_number, $clothService]);
        }
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

        $this->guardCustomerSector($request->user_id);

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
        $orders = Order::whereIn('id', $ids)
            ->forSectors(SectorService::allowedSectorIds())
            ->get();
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
    /**
     * Reassign many cars to one cleaner in a single action.
     *
     * Both ends are checked: the orders are narrowed to the sectors the user
     * may touch, and the cleaner has to be someone they are allowed to pick.
     * Anything outside that is reported back rather than silently skipped.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bulkAssignCleaner(Request $request)
    {
        $this->authorize('edit_orders');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer',
            'assigned_user_id' => 'required|integer',
        ]);

        $sectorIds = SectorService::allowedSectorIds();

        $cleaner = SectorService::scopeByUserSector(
            User::whereHas('roles', function ($query) {
                $query->where('name', SectorService::CLEANER_ROLE);
            })->where('id', $request->assigned_user_id),
            $sectorIds,
            'users.id'
        )->first();

        if (! $cleaner) {
            return response()->json([
                'message' => 'That cleaner is not one you can assign.',
            ], 422);
        }

        $orders = Order::with('user')
            ->whereIn('id', $request->ids)
            ->forSectors($sectorIds)
            ->get();

        $skipped = count($request->ids) - $orders->count();
        $assigned = 0;

        foreach ($orders as $order) {
            if ((int) $order->assigned_user_id === (int) $cleaner->id) {
                continue;
            }

            $order->assigned_user_id = $cleaner->id;
            $order->save();
            $assigned++;

            // Same notification the single order screen sends.
            if ($mobile = optional($order->user)->mobile) {
                SMSService::sendWhatsAppMsg($mobile, 'cleaner_assigned_customer', [
                    $order->car_number,
                    $cleaner->name,
                    $cleaner->mobile,
                ]);
            }
        }

        Log::info('Bulk cleaner assignment.', [
            'cleaner_id' => $cleaner->id,
            'assigned' => $assigned,
            'skipped' => $skipped,
        ]);

        $message = $assigned.' order(s) assigned to '.$cleaner->name.'.';

        if ($skipped > 0) {
            $message .= ' '.$skipped.' were skipped because they are outside your sectors.';
        }

        return response()->json(['message' => $message, 'assigned' => $assigned]);
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

        // Every display column is joined in. Resolving them per row instead
        // cost four extra queries for each of the 50 rows on a page.
        $$module_name = Order::join('users', 'orders.user_id', '=', 'users.id')
            ->leftJoin('sectors', 'orders.sector_id', '=', 'sectors.id')
            ->leftJoin('users as assigned_users', 'orders.assigned_user_id', '=', 'assigned_users.id')
            ->leftJoin('packages', 'orders.package_id', '=', 'packages.id')
            ->leftJoin('cars', 'orders.car_id', '=', 'cars.id')
            ->select(
                'orders.*',
                'users.mobile',
                'users.name as user_name',
                'sectors.name as sector_name',
                'assigned_users.name as assigned_user',
                'packages.name as package_name',
                'cars.name as car_name'
            );

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

        // Sector restriction. The requested sector is validated against the
        // user's own sectors, so this cannot be widened from the browser.
        $$module_name = $$module_name->forSectors(
            SectorService::selectedSectorIds(request()->get('filter_sector_id'))
        );

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
                return $data->user_name ?? '';
            })
            ->editColumn('assigned_user', function ($data) {
                return $data->assigned_user ?? 'Not Assigned';
            })
            ->editColumn('package_name', function ($data) {
                return $data->package_name ?? '';
            })
            ->editColumn('sector_name', function ($data) {
                return $data->sector_name ?? 'NA';
            })
            ->editColumn('car_name', function ($data) {
                return $data->car_name ?? '';
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
                ->forSectors(SectorService::allowedSectorIds())
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

    /**
     * A Franchise Owner may open, edit and delete orders, but only the ones in
     * their own sectors. The shared CRUD in BackendBaseController does the work
     * once the sector has been checked.
     *
     * @param  int  $id
     * @return Response
     */
    public function edit($id)
    {
        $this->guardSectorAccess($id);

        return parent::edit($id);
    }

    /**
     * @param  int  $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $this->guardSectorAccess($id);

        // Moving an order onto a customer in another sector would move the
        // order out of the franchise's reach, so it is blocked.
        if (! empty($request->user_id)) {
            $this->guardCustomerSector($request->user_id);
        }

        return parent::update($request, $id);
    }

    /**
     * @param  int  $id
     * @return Response
     */
    public function destroy($id)
    {
        $this->guardSectorAccess($id);

        return parent::destroy($id);
    }
}
