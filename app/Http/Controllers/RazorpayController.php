<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Razorpay\Api\Api;
use Session;
use Redirect;
use Modules\Order\Models\Order;
use Modules\Duration\Models\Duration;
use Modules\Package\Models\Package;
use Modules\Cloth\Models\Cloth;
use Carbon\Carbon;
use App\Services\SMSService;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use App\Mail\KuMail;
use Illuminate\Support\Facades\Log;

class RazorpayController extends Controller
{
    public function success()
    {
        $input = request()->all();
        $order_number = $input['order'];
        return view('razorpay_success', compact('order_number'));
    }

    public function payment(Request $request)
    {
        $input = $request->all();
        Log::info("Receiving response from Razorpay for new subscription");
        Log::info("Response => " . print_r($input, true));
        $api = new Api(env('RAZOR_KEY'), env('RAZOR_SECRET'));
        $payment = $api->payment->fetch($input['razorpay_payment_id']);

        if (count($input)  && !empty($input['razorpay_payment_id'])) {
            try {
                $acquirerData = $payment['acquirer_data'];
                $order = Order::where('razorpay_order_id', $payment['order_id'])->first();
                if ($order) {
                    $duration = Duration::where('id', $order->pakage_type)->select('duration')->first();
                    $order = Order::find($order->id);
                    $temp_password = $order->temp_password;
                    $order->temp_password = $temp_password;
                    $order->status = $payment['status'] == 'captured' ? 2 : 1;
                    $order->payment_date = Carbon::now();
                    $order->start_date = Carbon::now();
                    $order->renew_date = Carbon::now()->addMonths($duration->duration);
                    $order->payment_mode = $payment['method'];
                    $order->transaction_id = $acquirerData->upi_transaction_id??'';
                    $order->order_type = 'online';
                    $order->payment_id = $payment['id'];
                    $order->save();
                    $user = User::find($order->user_id);

                    $package = Package::where('id', $order->package_id)->select('name')->first();
                    $cloth = Cloth::where('id', $order->cloth_id)->select('name')->first();
                    $clothService = $order->cloth_service == 1 ? "Yes - $cloth->name" : "No";

                    SMSService::sendWhatsAppMsg($user->mobile, 'subscription_notification_customer2', [$package->name, $duration->duration, $order->car_number, $clothService, $user->email, $temp_password]);
                    SMSService::sendWhatsAppMsg('8650316068', 'subscription_notification_admin', [$order->car_number, $package->name, $duration->duration]);
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

        \Session::put('success', 'Payment successful, your order will be despatched in the next 48 hours.');
        return redirect()->route('order-success', ['order' => $order]);
    }
}
