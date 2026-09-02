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
 * Three groups, each with its own message:
 *   - already past their renewal date
 *   - sitting on hold
 *   - running low on cloths
 *
 * The first two go out every day and keep going until the customer renews or
 * the order is taken out of that state by hand. Nothing is sent before a plan
 * has run out - see the note in handle() for the group that used to be here.
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

        /*
         * Nothing is sent before a plan has actually run out.
         *
         * There used to be a fourth group here - everyone renewing within the
         * next seven days - and it was messaged with the `subscription_expire`
         * template, whose approved wording is "your car subscription expired
         * and is due on ...". So twelve customers a day, whose plans were
         * perfectly current, were told theirs had expired. Every day, until it
         * actually did.
         *
         * There is no approved template that says a renewal is *coming up*
         * (`toallren` is the closest, and it is a general broadcast with no
         * date on it), so the group had nothing correct to send even in
         * principle. The chase starts on the day the plan runs out.
         */
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

        /*
         * Both of these go out every day this job runs, which is every day,
         * and keep going until the customer renews or somebody changes the
         * status. That is the business rule and it is deliberately not a
         * setting here - v1 is being replaced, and a configuration screen for
         * it would be thrown away with the rest. The equivalent numbers in v2
         * are on its Settings screen.
         *
         * There is no dedupe: the same customer is messaged again tomorrow,
         * and the day after. Marking the order inactive is what stops it.
         */
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
