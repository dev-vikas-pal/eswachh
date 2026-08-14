<?php

namespace App\Console\Commands;

use App\Services\RazorpayService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Finds money Razorpay captured that this system never recorded.
 *
 * Until the callbacks were fixed, an exception while updating an order stopped
 * the payment record from being written at all, so a captured payment could
 * vanish from the books. This compares Razorpay's captured payments against
 * payment_history and reports anything missing.
 */
class ReconcileRazorpayPayments extends Command
{
    protected $signature = 'payments:reconcile
                            {--days=30 : How far back to look}
                            {--record : Write the missing payments into payment_history}';

    protected $description = 'Report Razorpay payments that were captured but never recorded';

    public function handle(): int
    {
        $from = Carbon::now()->subDays((int) $this->option('days'));

        $this->info('Checking Razorpay payments captured since '.$from->toDateString().'...');

        try {
            $payments = RazorpayService::api()->payment->all([
                'from' => $from->timestamp,
                'to' => Carbon::now()->timestamp,
                'count' => 100,
            ]);
        } catch (\Throwable $e) {
            $this->error('Could not reach Razorpay: '.$e->getMessage());

            return self::FAILURE;
        }

        $missing = [];

        foreach ($payments['items'] ?? [] as $payment) {
            if (($payment['status'] ?? '') !== 'captured') {
                continue;
            }

            if (RazorpayService::alreadyProcessed($payment['id'])) {
                continue;
            }

            // Older records predate the payment_id column, so fall back to
            // matching the order and amount before calling one missing.
            $orderId = $payment['notes']['local_order_id'] ?? null;

            $looksRecorded = DB::table('payment_history')
                ->where('order_id', $orderId)
                ->where('payment_amount', ($payment['amount'] ?? 0) / 100)
                ->whereNull('payment_id')
                ->exists();

            if ($orderId && $looksRecorded) {
                continue;
            }

            $missing[] = [
                $payment['id'],
                number_format(($payment['amount'] ?? 0) / 100, 2),
                $payment['method'] ?? '',
                $orderId ?? '-',
                Carbon::createFromTimestamp($payment['created_at'])->toDateTimeString(),
            ];
        }

        if (empty($missing)) {
            $this->info('Nothing missing. Every captured payment is recorded.');

            return self::SUCCESS;
        }

        $this->warn(count($missing).' captured payment(s) are not in payment_history:');
        $this->table(['Payment id', 'Amount', 'Method', 'Order', 'Captured at'], $missing);

        if (! $this->option('record')) {
            $this->line('');
            $this->line('Re-run with --record to write these into payment_history.');

            return self::SUCCESS;
        }

        foreach ($missing as [$paymentId]) {
            $payment = RazorpayService::api()->payment->fetch($paymentId);
            $order = \Modules\Order\Models\Order::find($payment['notes']['local_order_id'] ?? null);

            RazorpayService::record($payment, $order, optional($order)->user_id, $payment['notes']['cloth_id'] ?? null ? 'Cloth' : 'Subscription');

            $this->line('Recorded '.$paymentId);
        }

        $this->info('Done. The subscriptions themselves were not changed - check each order.');

        return self::SUCCESS;
    }
}
