<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\RazorpayService;
use App\Services\SectorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Modules\Order\Models\Order;
use Modules\Sector\Models\Sector;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Payment tracking: a payment is followed from the moment it is initiated to
 * the moment it completes, a Franchise Owner only ever sees their own sectors'
 * takings, and only an administrator can correct a status by hand.
 */
class PaymentTrackingTest extends TestCase
{
    use DatabaseTransactions;

    private Sector $ownSector;

    private Sector $otherSector;

    private User $admin;

    private User $franchiseOwner;

    protected function setUp(): void
    {
        parent::setUp();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (['view_backend', 'view_payments', 'edit_payments'] as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => SectorService::FRANCHISE_ROLE, 'guard_name' => 'web'])
            ->syncPermissions(['view_backend', 'view_payments']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownSector = Sector::create(['name' => 'Pay Sector A', 'status' => 1]);
        $this->otherSector = Sector::create(['name' => 'Pay Sector B', 'status' => 1]);

        $this->admin = $this->makeUser('pay.admin@test.local', 'super admin');
        $this->franchiseOwner = $this->makeUser('pay.franchise@test.local', SectorService::FRANCHISE_ROLE);
        $this->franchiseOwner->sectors()->sync([$this->ownSector->id]);

        SectorService::forgetCache();
    }

    public function test_a_payment_is_recorded_when_it_is_initiated(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYINIT-01');

        RazorpayService::recordInitiated('order_INIT001', $order, $order->user_id, 'Subscription', 499.00);

        $row = DB::table('payment_history')->where('razorpay_order_id', 'order_INIT001')->first();

        $this->assertNotNull($row, 'An abandoned payment must still leave a record.');
        $this->assertSame(RazorpayService::STATUS_INITIATED, $row->payment_status);
        $this->assertSame($this->ownSector->id, (int) $row->sector_id);
        $this->assertNull($row->payment_id);
    }

    public function test_completing_a_payment_updates_the_initiated_row_rather_than_adding_one(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYINIT-02');

        RazorpayService::recordInitiated('order_INIT002', $order, $order->user_id, 'Subscription', 499.00);
        RazorpayService::record($this->payment('pay_DONE002', 'order_INIT002', 49900), $order, $order->user_id, 'Subscription');

        $rows = DB::table('payment_history')->where('razorpay_order_id', 'order_INIT002')->get();

        $this->assertCount(1, $rows, 'One payment attempt must stay one row.');
        $this->assertSame(RazorpayService::STATUS_CAPTURED, $rows->first()->payment_status);
        $this->assertSame('pay_DONE002', $rows->first()->payment_id);
    }

    public function test_a_completion_with_no_initiated_row_still_records(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYINIT-03');

        RazorpayService::record($this->payment('pay_DONE003', 'order_INIT003', 25000), $order, $order->user_id, 'Subscription');

        $row = DB::table('payment_history')->where('payment_id', 'pay_DONE003')->first();

        $this->assertNotNull($row);
        $this->assertSame(RazorpayService::STATUS_CAPTURED, $row->payment_status);
    }

    public function test_initiated_payments_are_not_counted_as_revenue(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYREV-01');

        RazorpayService::recordInitiated('order_REV001', $order, $order->user_id, 'Subscription', 999.00);

        $revenue = DB::table('payment_history')
            ->where('sector_id', $this->ownSector->id)
            ->where('payment_status', RazorpayService::STATUS_CAPTURED)
            ->sum('payment_amount');

        $this->assertEqualsWithDelta(0, (float) $revenue, 0.01);
    }

    public function test_franchise_owner_only_sees_payments_from_their_own_sectors(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'PAYLIST-01');
        $theirs = $this->makeOrder($this->otherSector, 'PAYLIST-02');

        RazorpayService::record($this->payment('pay_MINE', 'order_MINE', 10000), $mine, $mine->user_id, 'Subscription');
        RazorpayService::record($this->payment('pay_THEIRS', 'order_THEIRS', 20000), $theirs, $theirs->user_id, 'Subscription');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.payments.index_data'));

        $response->assertOk();

        $ids = collect($response->json('data'))->pluck('payment_id')->all();

        $this->assertContains('pay_MINE', $ids);
        $this->assertNotContains('pay_THEIRS', $ids);
    }

    public function test_franchise_owner_cannot_ask_for_another_sectors_payments(): void
    {
        $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.payments.index_data', ['filter_sector_id' => $this->otherSector->id]))
            ->assertForbidden();
    }

    public function test_an_admin_can_correct_a_payment_status_by_hand(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYFIX-01');
        RazorpayService::recordInitiated('order_FIX001', $order, $order->user_id, 'Subscription', 750.00);

        $id = DB::table('payment_history')->where('razorpay_order_id', 'order_FIX001')->value('id');

        $this->actingAs($this->admin)
            ->patchJson(route('backend.payments.updateStatus', $id), [
                'payment_status' => RazorpayService::STATUS_CAPTURED,
                'note' => 'seen on the bank statement',
            ])
            ->assertOk();

        $row = DB::table('payment_history')->where('id', $id)->first();

        $this->assertSame(RazorpayService::STATUS_CAPTURED, $row->payment_status);
        $this->assertSame($this->admin->id, (int) $row->verified_by);
        $this->assertNotNull($row->verified_at);
        $this->assertStringContainsString('seen on the bank statement', $row->additional_notes);
    }

    public function test_a_franchise_owner_cannot_correct_a_payment_status(): void
    {
        $order = $this->makeOrder($this->ownSector, 'PAYFIX-02');
        RazorpayService::recordInitiated('order_FIX002', $order, $order->user_id, 'Subscription', 750.00);

        $id = DB::table('payment_history')->where('razorpay_order_id', 'order_FIX002')->value('id');

        $this->actingAs($this->franchiseOwner)
            ->patchJson(route('backend.payments.updateStatus', $id), [
                'payment_status' => RazorpayService::STATUS_CAPTURED,
            ])
            ->assertForbidden();

        $this->assertSame(
            RazorpayService::STATUS_INITIATED,
            DB::table('payment_history')->where('id', $id)->value('payment_status')
        );
    }

    public function test_the_payments_screen_renders_for_both_roles(): void
    {
        // The override controls are for administrators only.
        $this->actingAs($this->admin)
            ->get(route('backend.payments.index'))
            ->assertOk();

        $this->assertTrue($this->admin->can('edit_payments'));

        $response = $this->actingAs($this->franchiseOwner)->get(route('backend.payments.index'));

        $response->assertOk();
        $this->assertFalse($response->viewData('canOverride'));
    }

    public function test_the_reports_screen_is_scoped_to_the_owners_sectors(): void
    {
        $response = $this->actingAs($this->franchiseOwner)->get(route('backend.payments.reports'));

        $response->assertOk();
        $this->assertSame([$this->ownSector->id], array_keys($response->viewData('sectorOptions')));
        $this->assertFalse($response->viewData('canSeeAllSectors'));
    }

    /**
     * @return array<string, mixed>
     */
    private function payment(string $id, string $orderId, int $amountInPaise): array
    {
        return [
            'id' => $id,
            'order_id' => $orderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'status' => RazorpayService::STATUS_CAPTURED,
            'method' => 'upi',
        ];
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'name' => 'Test '.$role,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'password' => bcrypt('secret'),
            'status' => 1,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$role]);

        return $user;
    }

    private function makeOrder(Sector $sector, string $carNumber): Order
    {
        $customer = User::create([
            'name' => 'Customer '.$carNumber,
            'first_name' => 'Pay',
            'last_name' => 'Customer',
            'email' => strtolower($carNumber).'@pay.test',
            'mobile' => (string) random_int(6000000000, 9999999999),
            'password' => bcrypt('secret'),
            'status' => 1,
        ]);

        Userprofile::create([
            'user_id' => $customer->id,
            'name' => $customer->name,
            'first_name' => 'Pay',
            'last_name' => 'Customer',
            'email' => $customer->email,
            'sector_id' => $sector->id,
            'status' => 1,
        ]);

        return Order::create([
            'user_id' => $customer->id,
            'name' => $carNumber,
            'car_number' => $carNumber,
            'status' => 2,
            'start_date' => now()->subMonth(),
            'renew_date' => now()->addMonth(),
            'paid_amount' => 999,
        ]);
    }
}
