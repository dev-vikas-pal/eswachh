<?php

namespace Modules\Order\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SectorService;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Modules\Car\Models\Car;
use Modules\Package\Models\Package;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Backend\UserController;
use Illuminate\Support\Facades\Log;

use App\Events\Backend\UserCreated;
use App\Events\Backend\UserProfileUpdated;
use App\Events\Backend\UserUpdated;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Models\UserProvider;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;
use App\Notifications\UserAccountCreated;
use App\Models\OTP;
use Illuminate\Http\JsonResponse;
use Modules\Order\Models\Order;
use Modules\Cloth\Models\Cloth;
use App\Services\RazorpayService;
use Illuminate\Support\Facades\Auth;

class OrdersController extends Controller
{
    public $module_title;

    public $module_name;

    public $module_path;

    public $module_icon;

    public $module_model;

    public function __construct()
    {
        // Page Title
        $this->module_title = 'Orders';

        // module name
        $this->module_name = 'orders';

        // directory path of the module
        $this->module_path = 'order::frontend';

        // module icon
        $this->module_icon = 'fa-regular fa-sun';

        // module model name, path
        $this->module_model = "Modules\Order\Models\Order";
    }

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        if (Auth::check()) {
            // If the user is logged in, redirect to the admin page
            return redirect()->route('backend.orders.create');
        }

        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'List';

        $$module_name = $module_model::latest()->paginate();

        $car_model = "Modules\Car\Models\Car";
        $carList = $car_model::latest()->select('id', 'name')->get();

        $package_model = "Modules\Package\Models\Package";
        $packageList = $package_model::latest()->select('id', 'name')->get();

        $internaltype_model = "Modules\Internaltype\Models\Internaltype";
        $internaltypeList = $internaltype_model::latest()->select('id', 'name')->get();

        $duration_model = "Modules\Duration\Models\Duration";
        $durationList = $duration_model::latest()->select('id', 'name')->get();

        $cloth_model = "Modules\Cloth\Models\Cloth";
        $clothList = $cloth_model::latest()->select('id', 'name', 'count')->get();

        $stateList = DB::table('states')->where('status', 1)->get();

        return view(
            "$module_path.$module_name.index",
            compact('module_title', 'module_name', "$module_name", 'module_icon', 'module_action', 'carList', 'packageList', 'internaltypeList', 'durationList', 'stateList', 'module_name_singular', 'clothList')
        );
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Response
     */
    public function show($id)
    {
        $id = decode_id($id);
        $module_title = $this->module_title;
        $module_name = $this->module_name;
        $module_path = $this->module_path;
        $module_icon = $this->module_icon;
        $module_model = $this->module_model;
        $module_name_singular = Str::singular($module_name);

        $module_action = 'Show';

        $$module_name_singular = $module_model::findOrFail($id);

        return view(
            "$module_path.$module_name.show",
            compact('module_title', 'module_name', 'module_icon', 'module_action', 'module_name_singular', "$module_name_singular")
        );
    }
    public function getPrice(Request $request)
    {
        $order = new Order();
        return $order->getPrice($request);
    }
    public function getLocationInfos(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'parent_type' => 'required',
            'parent_id' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        // This route is served by the api middleware group, which carries no
        // session, so there is never a logged in user here and the list comes
        // back unrestricted. That is correct for the public signup form. The
        // backend uses backend.users.locationOptions instead, which does have a
        // session and therefore applies the Franchise Owner's sector limits.
        $list = SectorService::locationOptions($request->parent_type, $request->parent_id);

        $html = '<option></option>';
        foreach ($list as $value) {
            $html .= '<option value="' . $value->id . '">' . $value->name . '</option>';
        }

        return response()->json(['html' => $html, 'success' => true]);
    }
    public function addToCart(Request $request)
    {
        $validator = Validator::make(
            $request->all(),
            [
                'car_id' => 'required',
                'package_id' => 'required',
                'cleaning_type' => 'required',
                'pakage_type' => 'required',
                'name' => 'required',
                'email' => [
                    'required',
                    'email',
                    function ($attribute, $value, $fail) {
                        $user = User::where('email', $value)->whereNotNull('email_verified_at')->first();
                        if ($user) {
                            $fail('The email is already in use or not verified.');
                        }
                    },
                ],
                'mobile_no' => 'required|digits:10|numeric|unique:users,mobile,NULL,id,deleted_at,NULL',
                //'car_number' => 'required',
                'car_number' => 'required|unique:orders,car_number,NULL,id,deleted_at,NULL|regex:/^[^\s]+$/',
                'state_id' => 'required',
                'city_id' => 'required',
                'area_id' => 'required',
                'sector_id' => 'required',
                'society_id' => 'required',
                'house_no' => 'required',
            ],
            [
                'car_id.required' => 'Car Name is required.',
                'sector_id.required' => 'Sector is required.',
                'package_id.required' => 'Package is required.',
                'cleaning_type.required' => 'Cleaning Type is required.',
                'pakage_type.required' => 'Duration is required.',
                'email.email' => 'Email must be a valid email address.',
                'email.unique' => 'This email is already in use.',
                'mobile_no.unique' => 'This mobile number is already in use.',
                'mobile_no.required' => 'The mobile number is required.',
                'mobile_no.digits' => 'The mobile number must be 10 digits long.',
                'mobile_no.numeric' => 'The mobile number must only contain numbers.',
                'car_number.regex' => 'The car number should not contain spaces.',
            ]
        );


        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 400);
        }

        $result = $this->checkMobileVerification($request);

        if (!$result) {
            return response()->json(['otp' => false, 'errors' => ["mobile_no" => 'Mobile Number Not verified.']], 400);
        }

        $response = $this->getPrice($request);
        $pdata =  json_decode($response->getContent(), true);
        $final_price =  $pdata['final_price'] ?? 0;
        if ($final_price < 1) {
            return response()->json(['price' => false, 'errors' => ["package_id" => 'Price Not verified.']], 400);
        }
        $userData = array(
            'first_name' => explode(' ', $request->name)[0] ?? '',
            'last_name' => explode(' ', $request->name)[1] ?? '',
            'name' => $request->name,
            'email' => $request->email,
            'password' => Str::random(6),
            'status' => 1,
            'confirmed' => 1,
            'email_credentials' => 1,
            'roles' => array('customer'),
            'permissions' => array('view_backend', 'view_orders', 'add_orders', 'edit_orders'),
            'mobile' => $request->mobile_no,
            'car_number' => $request->car_number,
            'state_id' => $request->state_id,
            'city_id' => $request->city_id,
            'area_id' => $request->area_id,
            'sector_id' => $request->sector_id,
            'society_id' => $request->society_id,
            'house_no' => $request->house_no,
            'office_time' => $request->office_time
        );

        $request->merge($userData);

        $user = $this->addUser($userData, $request);

        $api = RazorpayService::api();

        $order = $api->order->create([
            'amount' => $final_price * 100,
            'currency' => 'INR',
            'receipt' => Str::uuid(),
            'payment_capture' => 1,
            'notes' => [
                'order_type' => 'online',
                'customer_id' => $user->id,
            ],
        ]);
        $cloth = Cloth::where('id', $request->cloth_id)->select('count')->first();
        $orderData = array(
            'car_id' => $request->car_id,
            'package_id' => $request->package_id,
            'cleaning_type' => $request->cleaning_type,
            // A select with nothing chosen posts the literal "null", which the
            // null coalescing operator does not catch; cloth_id is an integer.
            'cloth_id' => is_numeric($request->cloth_id) ? (int) $request->cloth_id : 0,
            'cloth_count' => $cloth->count ?? 0,
            'cloth_service' => $request->cloth_service ?? 0,
            'pakage_type' => $request->pakage_type,
            'user_id' => $user->id,
            'temp_password' => $userData['password'],
            'name' => $request->name,
            'car_number' => $request->car_number,
            'status' => 2,
            'amount' => $pdata['final_price'],
            'discount' => 0,
            'paid_amount' => $pdata['final_price'],
            'razorpay_order_id' => $order->id,
        );
        $localOrder = $this->addOrder($orderData);
        // Only identifiers: $orderData carries the customer's temporary
        // password, which must never reach the log file.
        Log::info('Razorpay order created for new subscription.', [
            'razorpay_order_id' => $order->id,
            'car_number' => $orderData['car_number'],
        ]);

        // Note the payment now, so a signup that is abandoned at the payment
        // screen still leaves a record to chase.
        RazorpayService::recordInitiated($order->id, $localOrder, $user->id, 'Subscription', (float) $pdata['final_price']);

        return response()->json(['success' => true, 'razorpay_order_id' => $order->id]);
    }
    public function addOrder($data)
    {
        $order_info = $this->module_model::create($data);
        return $order_info;
    }
    public function addUser($data, $request)
    {
        $module_name_singular = Str::singular('users');
        $data['name'] = $request->first_name . ' ' . $request->last_name;
        $data['password'] = Hash::make($request->password);

        $$module_name_singular = User::create($data);

        $roles = $data['roles'];

        $permissions = $data['permissions'] ?? '';

        // Sync Roles
        if (isset($roles)) {
            $$module_name_singular->syncRoles($roles);
        } else {
            $roles = [];
            $$module_name_singular->syncRoles($roles);
        }

        // Sync Permissions
        if (isset($permissions)) {
            $$module_name_singular->syncPermissions($permissions);
        } else {
            $permissions = [];
            $$module_name_singular->syncPermissions($permissions);
        }

        // Username
        $id = $$module_name_singular->id;
        $username = config('app.initial_username') . $id;
        $$module_name_singular->username = $username;
        $$module_name_singular->save();

        event(new UserCreated($$module_name_singular));

        $user_profile = Userprofile::where('user_id', '=', $$module_name_singular->id)->first();
        $user_profile->update($data);

        event(new UserProfileUpdated($user_profile));

        return $$module_name_singular;

        $data = [
            'password' => $data['password'],
        ];
        $$module_name_singular->notify(new UserAccountCreated($data));
        return $$module_name_singular;
    }
    public function checkMobileVerification($request)
    {
        $mobilNumber = $request->get('mobile_no');
        $otp = $request->get('otp');
        $otp =  OTP::where([
            'mobile_no' => $mobilNumber,
            'verify_status' => 1,
            'otp' => $otp,
        ])->first();
        if (!$otp instanceof OTP) {
            return false;
        }
        if ($otp->expired_at > Carbon::now()) {
            return true;
        }
        return false;
    }
    public function checkCar()
    {
        $request = request();
        $request->validate(['car_number' => 'required|string']);
        $order = Order::select('id','user_id', 'car_id','car_number', 'package_id', 'cleaning_type', 'pakage_type', 'cloth_id', 'name', 'renew_date', 'car_number')->where('car_number', $request->car_number)->first();
        if (!$order) {
            return response()->json(['error' => 'Car not found']);
        }
        $user = User::find($order->user_id);
        if($user->name){
            $order->name= $user->name;
        }
        $token = encrypt([
            'user_id' => $user->id,
            'expires_at' => now()->addMinutes(10)->timestamp
        ]);

        return response()->json(['token' => $token,'order' => $order]);
    }
}
