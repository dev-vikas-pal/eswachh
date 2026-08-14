<?php

namespace App\Console\Commands;

use App\Services\SMSService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Modules\Order\Models\Order;

/**
 * Daily job: chase the subscriptions that need attention.
 *
 * Four groups, each with its own message:
 *   - due to renew within the next week
 *   - already past their renewal date
 *   - sitting on hold
 *   - running low on cloths
 *
 * Queries go through the Order model so soft deleted orders are excluded; the
 * previous version joined the tables directly and messaged deleted orders too.
 */
class SendRenewalNotifications extends Command
{
    private const STATUS_ACTIVE = 2;

    private const STATUS_HOLD = 4;

    protected $signature = 'renewal:send-notifications
                            {--dry-run : Show who would be messaged without sending anything}';

    protected $description = 'Send renewal, expiry, hold and low cloth reminders';

    private bool $dryRun = false;

    public function handle(): int
    {
        $this->dryRun = (bool) $this->option('dry-run');

        if ($this->dryRun) {
            $this->warn('Dry run: no messages will be sent.');
        }

        $today = Carbon::today();

        $dueSoon = Order::with('user')
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('renew_date')
            ->whereDate('renew_date', '>=', $today)
            ->whereDate('renew_date', '<=', $today->copy()->addDays(7))
            ->get();

        $expired = Order::with('user')
            ->where('status', self::STATUS_ACTIVE)
            ->whereNotNull('renew_date')
            ->whereDate('renew_date', '<', $today)
            ->get();

        $onHold = Order::with('user')
            ->where('status', self::STATUS_HOLD)
            ->whereNotNull('renew_date')
            ->get();

        $lowCloths = Order::with('user')
            ->where('status', self::STATUS_ACTIVE)
            ->where('cloth_service', 1)
            ->where('cloth_count', '<=', 10)
            ->get();

        $sent = 0;
        $sent += $this->notify($dueSoon, 'due to renew soon', fn ($order) => [
            'subscription_expire', [Carbon::parse($order->renew_date)->format('Y-m-d')],
        ]);

        $sent += $this->notify($expired, 'past their renewal date', fn ($order) => [
            'subscription_expire', [Carbon::parse($order->renew_date)->format('Y-m-d')],
        ]);

        $sent += $this->notify($onHold, 'on hold', fn ($order) => [
            'hold_cars', [Carbon::parse($order->renew_date)->format('Y-m-d')],
        ]);

        $sent += $this->notify($lowCloths, 'low on cloths', fn ($order) => [
            'lower_cloth_count_cutomer', [],
        ]);

        if (! $this->dryRun) {
            Log::info('Renewal notification run complete.', ['messages' => $sent]);
        }

        $this->info($this->dryRun ? 'Dry run finished.' : $sent.' reminder(s) sent.');

        return self::SUCCESS;
    }

    /**
     * @param  \Illuminate\Support\Collection  $orders
     * @param  callable  $message  Returns [template, arguments] for an order
     */
    private function notify($orders, string $label, callable $message): int
    {
        if ($orders->isEmpty()) {
            $this->line('None '.$label.'.');

            return 0;
        }

        $this->line($orders->count().' '.$label.'.');

        $sent = 0;

        foreach ($orders as $order) {
            $mobile = optional($order->user)->mobile;

            if (! $mobile) {
                continue;
            }

            [$template, $arguments] = $message($order);

            if ($this->dryRun) {
                $this->line('  would send "'.$template.'" to order '.$order->id.' ('.$order->car_number.')');
                continue;
            }

            try {
                SMSService::sendWhatsAppMsg($mobile, $template, $arguments);
                $sent++;
            } catch (\Throwable $e) {
                // A single failed message must not abort the whole run.
                Log::error('Reminder could not be sent.', [
                    'order_id' => $order->id,
                    'template' => $template,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }
}
