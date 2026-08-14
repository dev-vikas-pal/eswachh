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
use App\Services\RazorpayService;
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
        Log::info('Razorpay callback for new subscription.', [
            'razorpay_payment_id' => $input['razorpay_payment_id'] ?? null,
            'razorpay_order_id' => $input['razorpay_order_id'] ?? null,
        ]);

        if (empty($input['razorpay_payment_id'])) {
            \Session::put('error', 'We did not receive a payment reference. Please try again.');

            return redirect()->route('frontend.index');
        }

        if (! RazorpayService::signatureIsValid($input)) {
            \Session::put('error', 'This payment could not be verified. Please contact support.');

            return redirect()->route('frontend.index');
        }

        $payment = RazorpayService::api()->payment->fetch($input['razorpay_payment_id']);

        $order = Order::where('razorpay_order_id', $payment['order_id'])->first();

        // A resubmitted callback must not start a second subscription period.
        if (RazorpayService::alreadyProcessed($payment['id'])) {
            Log::info('Razorpay payment already processed; ignoring repeat callback.', [
                'razorpay_payment_id' => $payment['id'],
            ]);

            return redirect()->route('order-success', ['order' => $order]);
        }

        // The money is already taken, so the record is written before the
        // subscription is touched.
        RazorpayService::record($payment, $order, optional($order)->user_id, 'Subscription');

        if (! $order) {
            Log::error('Razorpay payment has no matching order.', [
                'razorpay_payment_id' => $payment['id'],
                'razorpay_order_id' => $payment['order_id'],
            ]);

            \Session::put('error', 'Your payment was received but we could not match it to an order. Our team will contact you.');

            return redirect()->route('frontend.index');
        }

        try {
            $duration = Duration::where('id', $order->pakage_type)->select('duration')->first();
            $acquirerData = $payment['acquirer_data'] ?? null;
            $temp_password = $order->temp_password;

            $order->status = $payment['status'] == 'captured' ? 2 : 1;
            $order->payment_date = Carbon::now();
            $order->start_date = Carbon::now();
            $order->renew_date = Carbon::now()->addMonths($duration->duration ?? 1);
            $order->payment_mode = $payment['method'];
            $order->transaction_id = $acquirerData->upi_transaction_id ?? '';
            $order->order_type = 'online';
            $order->payment_id = $payment['id'];
            $order->save();

            $user = User::find($order->user_id);
            $package = Package::where('id', $order->package_id)->select('name')->first();
            $cloth = Cloth::where('id', $order->cloth_id)->select('name')->first();
            $clothService = $order->cloth_service == 1 ? "Yes - " . ($cloth->name ?? '') : "No";

            if ($user) {
                SMSService::sendWhatsAppMsg($user->mobile, 'subscription_notification_customer2', [$package->name ?? '', $duration->duration ?? '', $order->car_number, $clothService, $user->email, $temp_password]);
                SMSService::sendWhatsAppMsg('8650316068', 'subscription_notification_admin', [$order->car_number, $package->name ?? '', $duration->duration ?? '']);
            }
        } catch (\Throwable $e) {
            \Session::put('error', RazorpayService::reportFailure($e, $payment, $order));

            return redirect()->route('order-success', ['order' => $order]);
        }

        \Session::put('success', 'Payment successful, your order will be despatched in the next 48 hours.');

        return redirect()->route('order-success', ['order' => $order]);
    }
}
