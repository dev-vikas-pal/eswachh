<?php

namespace App\Console\Commands;

use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;

/**
 * Weekly job: park a subscription on Hold once it is a week past its renewal
 * date. Until now this was done by hand, so cleaning carried on indefinitely
 * for cars that had stopped paying.
 */
class AutoHoldExpiredOrders extends Command
{
    /**
     * Statuses: 2 = Active, 4 = Hold.
     */
    private const STATUS_ACTIVE = 2;

    private const STATUS_HOLD = 4;

    private const GRACE_DAYS = 7;

    protected $signature = 'orders:auto-hold
                            {--dry-run : List what would be held without changing anything or sending messages}';

    protected $description = 'Put active subscriptions on hold once they are past their renewal date plus a grace period';

    public function handle(): int
    {
        $cutoff = Carbon::today()->subDays(self::GRACE_DAYS);
        $dryRun = (bool) $this->option('dry-run');

        $orders = Order::with('user')
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('renew_date')
            ->whereDate('renew_date', '<', $cutoff)
            ->get();

        if ($orders->isEmpty()) {
            $this->info('Nothing to hold. No active subscription is more than '.self::GRACE_DAYS.' days overdue.');

            return self::SUCCESS;
        }

        $this->info($orders->count().' subscription(s) are more than '.self::GRACE_DAYS.' days past renewal.');

        if ($dryRun) {
            $this->table(
                ['Order', 'Car', 'Customer', 'Renew date'],
                $orders->map(fn ($order) => [
                    $order->id,
                    $order->car_number,
                    optional($order->user)->name,
                    Carbon::parse($order->renew_date)->toDateString(),
                ])->all()
            );

            $this->warn('Dry run: nothing was changed and no messages were sent.');

            return self::SUCCESS;
        }

        $held = 0;

        foreach ($orders as $order) {
            try {
                $order->status = self::STATUS_HOLD;
                $order->save();
                $held++;

                if ($mobile = optional($order->user)->mobile) {
                    SMSService::sendWhatsAppMsg($mobile, 'change_status', [
                        'HOLD',
                        Carbon::parse($order->renew_date)->format('Y-m-d'),
                    ]);
                }
            } catch (\Throwable $e) {
                // One bad record must not stop the rest of the run.
                Log::error('Auto hold failed for an order.', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error('Order '.$order->id.' could not be held: '.$e->getMessage());
            }
        }

        Log::info('Auto hold run complete.', ['held' => $held]);
        $this->info($held.' subscription(s) moved to Hold.');

        return self::SUCCESS;
    }
}
