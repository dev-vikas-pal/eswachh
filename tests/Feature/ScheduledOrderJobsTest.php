<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Userprofile;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Modules\Order\Models\Order;
use Tests\TestCase;

/**
 * The two scheduled jobs from the proposal: the weekly auto hold and the daily
 * reminder run. Both are exercised in dry run so no WhatsApp message is sent.
 */
class ScheduledOrderJobsTest extends TestCase
{
    use DatabaseTransactions;

    private const STATUS_ACTIVE = 2;

    private const STATUS_HOLD = 4;

    public function test_an_order_past_the_grace_period_is_put_on_hold(): void
    {
        $order = $this->makeOrder('HOLD-0001', self::STATUS_ACTIVE, Carbon::today()->subDays(10));

        $this->artisan('orders:auto-hold')->assertExitCode(0);

        $this->assertSame(self::STATUS_HOLD, (int) $order->fresh()->status);
    }

    public function test_an_order_inside_the_grace_period_is_left_alone(): void
    {
        // Overdue, but only by three days: the customer still has time.
        $order = $this->makeOrder('HOLD-0002', self::STATUS_ACTIVE, Carbon::today()->subDays(3));

        $this->artisan('orders:auto-hold')->assertExitCode(0);

        $this->assertSame(self::STATUS_ACTIVE, (int) $order->fresh()->status);
    }

    public function test_an_order_that_is_not_yet_due_is_left_alone(): void
    {
        $order = $this->makeOrder('HOLD-0003', self::STATUS_ACTIVE, Carbon::today()->addMonth());

        $this->artisan('orders:auto-hold')->assertExitCode(0);

        $this->assertSame(self::STATUS_ACTIVE, (int) $order->fresh()->status);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $order = $this->makeOrder('HOLD-0004', self::STATUS_ACTIVE, Carbon::today()->subDays(30));

        $this->artisan('orders:auto-hold --dry-run')->assertExitCode(0);

        $this->assertSame(self::STATUS_ACTIVE, (int) $order->fresh()->status);
    }

    public function test_a_soft_deleted_order_is_never_chased(): void
    {
        $order = $this->makeOrder('HOLD-0005', self::STATUS_ACTIVE, Carbon::today()->subDays(30));
        $order->delete();

        $this->artisan('orders:auto-hold')->assertExitCode(0);
        $this->artisan('renewal:send-notifications --dry-run')->assertExitCode(0);

        $this->assertSame(self::STATUS_ACTIVE, (int) $order->fresh()->status);
    }

    public function test_the_reminder_run_completes_without_sending_in_dry_run(): void
    {
        $this->makeOrder('REMIND-0001', self::STATUS_ACTIVE, Carbon::today()->subDays(2));
        $this->makeOrder('REMIND-0002', self::STATUS_HOLD, Carbon::today()->subDays(20));
        $this->makeOrder('REMIND-0003', self::STATUS_ACTIVE, Carbon::today()->addDays(3));

        $this->artisan('renewal:send-notifications --dry-run')
            ->expectsOutputToContain('Dry run')
            ->assertExitCode(0);
    }

    private function makeOrder(string $carNumber, int $status, Carbon $renewDate): Order
    {
        $customer = User::create([
            'name' => 'Cron Customer '.$carNumber,
            'first_name' => 'Cron',
            'last_name' => 'Customer',
            'email' => strtolower($carNumber).'@cron.test',
            'mobile' => (string) random_int(6000000000, 9999999999),
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);

        Userprofile::create([
            'user_id' => $customer->id,
            'name' => $customer->name,
            'first_name' => 'Cron',
            'last_name' => 'Customer',
            'email' => $customer->email,
            'status' => 1,
        ]);

        return Order::create([
            'user_id' => $customer->id,
            'name' => $carNumber,
            'car_number' => $carNumber,
            'status' => $status,
            'start_date' => Carbon::today()->subMonths(3),
            'renew_date' => $renewDate,
            'paid_amount' => 999,
        ]);
    }
}
