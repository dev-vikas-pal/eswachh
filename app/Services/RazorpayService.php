<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Razorpay\Api\Api;

/**
 * The parts of the Razorpay flow that every payment callback needs.
 *
 * There are four callbacks (new subscription, renew, renew without login and
 * cloth top up). They each apply their own business rules, but the gateway
 * handling around them - building the client, checking the signature, not
 * processing the same payment twice and writing the payment record - is the
 * same, and lives here.
 */
class RazorpayService
{
    /**
     * Configured client. Reads through config so payments keep working once
     * config is cached, which env() would not survive.
     */
    public static function api(): Api
    {
        return new Api(self::key(), config('services.razorpay.secret'));
    }

    public static function key(): ?string
    {
        return config('services.razorpay.key');
    }

    /**
     * Confirm the callback really came from Razorpay.
     *
     * Returns false only when a signature was supplied and does not match. If
     * the gateway sent no signature at all the payment is allowed through and
     * a warning is logged, so that an unexpected flow cannot silently start
     * rejecting real customer payments.
     *
     * @param  array<string, mixed>  $input
     */
    public static function signatureIsValid(array $input): bool
    {
        $signature = $input['razorpay_signature'] ?? null;
        $orderId = $input['razorpay_order_id'] ?? null;
        $paymentId = $input['razorpay_payment_id'] ?? null;

        if (empty($signature) || empty($orderId) || empty($paymentId)) {
            Log::warning('Razorpay callback carried no signature to verify.', [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
            ]);

            return true;
        }

        try {
            self::api()->utility->verifyPaymentSignature([
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ]);

            return true;
        } catch (\Throwable $e) {
            Log::error('Razorpay signature verification failed.', [
                'razorpay_payment_id' => $paymentId,
                'razorpay_order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Has this gateway payment already been dealt with?
     *
     * Guards against a resubmitted callback, which would otherwise extend a
     * subscription twice and bank the same money twice.
     */
    public static function alreadyProcessed(?string $paymentId): bool
    {
        if (empty($paymentId)) {
            return false;
        }

        return DB::table('payment_history')->where('payment_id', $paymentId)->exists();
    }

    /** A Razorpay order exists and the customer has been sent to pay. */
    public const STATUS_INITIATED = 'initiated';

    /** Money taken. Revenue reporting keys off this value. */
    public const STATUS_CAPTURED = 'captured';

    public const STATUS_FAILED = 'failed';

    /**
     * How each stored status reads on screen.
     *
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_INITIATED => 'Initiated',
            self::STATUS_CAPTURED => 'Completed',
            self::STATUS_FAILED => 'Failed',
        ];
    }

    /**
     * Note a payment the moment the customer is sent to the gateway.
     *
     * Without this a customer who starts a payment and never finishes leaves no
     * trace, so there is nothing to chase and nothing to compare against the
     * bank statement.
     *
     * @param  \Modules\Order\Models\Order|null  $order
     */
    public static function recordInitiated(string $razorpayOrderId, $order, ?int $userId, string $paymentFor, float $amount): void
    {
        DB::table('payment_history')->insert([
            'user_id' => $userId ?? optional($order)->user_id ?? 0,
            'order_id' => optional($order)->id ?? 0,
            'sector_id' => optional($order)->sector_id,
            'razorpay_order_id' => $razorpayOrderId,
            'payment_amount' => $amount,
            'currency' => 'INR',
            'payment_status' => self::STATUS_INITIATED,
            'payment_method' => '',
            'payment_date_time' => now(),
            'payment_gateway' => 'Razorpay',
            'payment_for' => $paymentFor,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Write the completed payment.
     *
     * Called before the order is touched: the money has already left the
     * customer by this point, so it is recorded first and the subscription
     * update follows. If that update then fails the payment is still on file
     * and can be reconciled, rather than disappearing.
     *
     * If the payment was noted at initiation that same row is completed in
     * place, so one attempt stays one row.
     *
     * @param  \Razorpay\Api\Payment|array  $payment
     * @param  \Modules\Order\Models\Order|null  $order
     */
    public static function record($payment, $order, ?int $userId, string $paymentFor): void
    {
        $acquirerData = $payment['acquirer_data'] ?? null;

        $values = [
            'user_id' => $userId ?? optional($order)->user_id ?? 0,
            'order_id' => optional($order)->id ?? 0,
            'sector_id' => optional($order)->sector_id,
            'payment_id' => $payment['id'] ?? null,
            'payment_amount' => ($payment['amount'] ?? 0) / 100,
            'currency' => $payment['currency'] ?? 'INR',
            'payment_status' => $payment['status'] ?? 'Pending',
            'payment_method' => $payment['method'] ?? '',
            'transaction_id' => $acquirerData->upi_transaction_id ?? '',
            'payment_gateway' => 'Razorpay',
            'payment_for' => $paymentFor,
        ];

        $initiated = DB::table('payment_history')
            ->where('razorpay_order_id', $payment['order_id'] ?? '')
            ->where('payment_status', self::STATUS_INITIATED)
            ->orderBy('id')
            ->first();

        if ($initiated) {
            DB::table('payment_history')
                ->where('id', $initiated->id)
                ->update($values + ['payment_date_time' => now(), 'updated_at' => now()]);

            return;
        }

        DB::table('payment_history')->insert($values + [
            'razorpay_order_id' => $payment['order_id'] ?? null,
            'payment_date_time' => now(),
        ]);
    }

    /**
     * What to say to a customer when their payment went through but we could
     * not finish updating their subscription. Never show them the exception.
     */
    public static function reportFailure(\Throwable $e, $payment, $order = null): string
    {
        Log::error('Razorpay payment captured but the order could not be updated.', [
            'razorpay_payment_id' => $payment['id'] ?? null,
            'order_id' => optional($order)->id,
            'error' => $e->getMessage(),
            'file' => $e->getFile().':'.$e->getLine(),
        ]);

        return 'Your payment went through, but we could not finish updating your subscription. '
            .'Our team has been notified and will sort it out shortly. Please do not pay again.';
    }
}
