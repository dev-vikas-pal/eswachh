<?php

namespace Tests\Feature;

use App\Models\CleanerAttendance;
use App\Models\Complaint;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SectorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Modules\Order\Models\Order;
use Modules\Sector\Models\Sector;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

/**
 * Complaints and cleaner attendance, across all four audiences.
 */
class ComplaintAndAttendanceTest extends TestCase
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

        $permissions = [
            'view_backend', 'view_complaints', 'add_complaints', 'edit_complaints',
            'view_attendances', 'add_attendances',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web'])
            ->syncPermissions(['view_backend', 'view_complaints', 'add_complaints']);
        Role::firstOrCreate(['name' => 'cleaner', 'guard_name' => 'web'])
            ->syncPermissions(['view_backend', 'view_complaints', 'edit_complaints', 'view_attendances', 'add_attendances']);
        Role::firstOrCreate(['name' => SectorService::FRANCHISE_ROLE, 'guard_name' => 'web'])
            ->syncPermissions(['view_backend', 'view_complaints', 'view_attendances']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownSector = Sector::create(['name' => 'Complaint Sector A', 'status' => 1]);
        $this->otherSector = Sector::create(['name' => 'Complaint Sector B', 'status' => 1]);

        $this->admin = $this->makeUser('complaint.admin@test.local', 'super admin', null);
        $this->franchiseOwner = $this->makeUser('complaint.franchise@test.local', SectorService::FRANCHISE_ROLE, null);
        $this->franchiseOwner->sectors()->sync([$this->ownSector->id]);

        SectorService::forgetCache();
    }

    public function test_a_customer_can_raise_a_complaint_about_their_own_car(): void
    {
        [$order, $customer, $cleaner] = $this->makeServicedCar($this->ownSector, 'CMP-0001');

        $this->actingAs($customer)
            ->post(route('backend.complaints.store'), [
                'order_id' => $order->id,
                'message' => 'The car was not cleaned properly today.',
            ])
            ->assertRedirect(route('backend.complaints.index'));

        $complaint = Complaint::where('order_id', $order->id)->firstOrFail();

        $this->assertSame(Complaint::STATUS_OPEN, $complaint->status);
        $this->assertSame($this->ownSector->id, (int) $complaint->sector_id);
        // The cleaner on the round at the time is captured with the complaint.
        $this->assertSame($cleaner->id, (int) $complaint->assigned_user_id);
    }

    public function test_a_customer_cannot_complain_about_someone_elses_car(): void
    {
        [$order] = $this->makeServicedCar($this->ownSector, 'CMP-0002');
        $stranger = $this->makeUser('stranger@test.local', 'customer', $this->ownSector);

        $this->actingAs($stranger)
            ->post(route('backend.complaints.store'), [
                'order_id' => $order->id,
                'message' => 'Not my car at all.',
            ])
            ->assertNotFound();

        $this->assertSame(0, Complaint::where('order_id', $order->id)->count());
    }

    public function test_a_complaint_longer_than_two_hundred_words_is_rejected(): void
    {
        [$order, $customer] = $this->makeServicedCar($this->ownSector, 'CMP-0003');

        // Asserted on the exception rather than the session: this app registers
        // StartSession twice, which loses flashed validation errors.
        $this->assertValidationFails(
            fn () => $this->actingAs($customer)->post(route('backend.complaints.store'), [
                'order_id' => $order->id,
                'message' => str_repeat('word ', 201),
            ]),
            'message'
        );

        $this->assertSame(0, Complaint::where('order_id', $order->id)->count());
    }

    public function test_a_customer_only_sees_their_own_complaints(): void
    {
        $mine = $this->makeComplaint($this->ownSector, 'CMP-0004');
        $theirs = $this->makeComplaint($this->ownSector, 'CMP-0005');

        $response = $this->actingAs($mine->customer)
            ->getJson(route('backend.complaints.index_data'));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_a_cleaner_only_sees_complaints_assigned_to_them(): void
    {
        $mine = $this->makeComplaint($this->ownSector, 'CMP-0006');
        $theirs = $this->makeComplaint($this->ownSector, 'CMP-0007');

        $response = $this->actingAs($mine->cleaner)
            ->getJson(route('backend.complaints.index_data'));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_a_franchise_owner_only_sees_complaints_from_their_sectors(): void
    {
        $mine = $this->makeComplaint($this->ownSector, 'CMP-0008');
        $theirs = $this->makeComplaint($this->otherSector, 'CMP-0009');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.complaints.index_data'));

        $response->assertOk();
        $ids = collect($response->json('data'))->pluck('id')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_a_cleaner_closes_a_complaint_by_saying_whether_they_talked(): void
    {
        $complaint = $this->makeComplaint($this->ownSector, 'CMP-0010');

        $this->actingAs($complaint->cleaner)
            ->patchJson(route('backend.complaints.resolve', $complaint->id), [
                'resolution' => Complaint::RESOLUTION_TALKED,
                'resolution_note' => 'Spoke to them, will redo tomorrow.',
            ])
            ->assertOk();

        $complaint->refresh();

        $this->assertSame(Complaint::STATUS_CLOSED, $complaint->status);
        $this->assertSame(Complaint::RESOLUTION_TALKED, $complaint->resolution);
        $this->assertSame($complaint->cleaner->id, (int) $complaint->closed_by);
        $this->assertNotNull($complaint->closed_at);
    }

    public function test_a_cleaner_cannot_close_someone_elses_complaint(): void
    {
        $complaint = $this->makeComplaint($this->ownSector, 'CMP-0011');
        $otherCleaner = $this->makeUser('other.cleaner@test.local', 'cleaner', $this->ownSector);

        $this->actingAs($otherCleaner)
            ->patchJson(route('backend.complaints.resolve', $complaint->id), [
                'resolution' => Complaint::RESOLUTION_TALKED,
            ])
            ->assertNotFound();

        $this->assertSame(Complaint::STATUS_OPEN, $complaint->fresh()->status);
    }

    public function test_a_complaint_cannot_be_closed_twice(): void
    {
        $complaint = $this->makeComplaint($this->ownSector, 'CMP-0012');

        $this->actingAs($complaint->cleaner)
            ->patchJson(route('backend.complaints.resolve', $complaint->id), [
                'resolution' => Complaint::RESOLUTION_TALKED,
            ])->assertOk();

        $this->actingAs($complaint->cleaner)
            ->patchJson(route('backend.complaints.resolve', $complaint->id), [
                'resolution' => Complaint::RESOLUTION_NOT_TALKED,
            ])->assertStatus(422);

        // The first outcome stands.
        $this->assertSame(Complaint::RESOLUTION_TALKED, $complaint->fresh()->resolution);
    }

    public function test_a_customer_cannot_reach_a_complaint_from_another_customer(): void
    {
        $complaint = $this->makeComplaint($this->ownSector, 'CMP-0013');
        $stranger = $this->makeUser('nosy@test.local', 'customer', $this->ownSector);

        $this->actingAs($stranger)
            ->get(route('backend.complaints.show', $complaint->id))
            ->assertNotFound();
    }

    public function test_a_cleaner_reports_attendance_and_is_marked_present(): void
    {
        [$order, , $cleaner] = $this->makeServicedCar($this->ownSector, 'ATT-0001');

        $this->actingAs($cleaner)
            ->post(route('backend.attendances.store'), ['cars_serviced' => 1])
            ->assertRedirect(route('backend.attendances.index'));

        $entry = CleanerAttendance::where('user_id', $cleaner->id)->firstOrFail();

        $this->assertSame(CleanerAttendance::STATUS_PRESENT, $entry->status);
        $this->assertSame(1, (int) $entry->cars_serviced);
        $this->assertSame(1, (int) $entry->total_cars);
        $this->assertSame($this->ownSector->id, (int) $entry->sector_id);
    }

    public function test_servicing_no_cars_is_recorded_as_absent(): void
    {
        [, , $cleaner] = $this->makeServicedCar($this->ownSector, 'ATT-0002');

        $this->actingAs($cleaner)
            ->post(route('backend.attendances.store'), ['cars_serviced' => 0])
            ->assertRedirect();

        $this->assertSame(
            CleanerAttendance::STATUS_ABSENT,
            CleanerAttendance::where('user_id', $cleaner->id)->value('status')
        );
    }

    public function test_a_cleaner_cannot_claim_more_cars_than_they_have(): void
    {
        [, , $cleaner] = $this->makeServicedCar($this->ownSector, 'ATT-0003');

        $this->assertValidationFails(
            fn () => $this->actingAs($cleaner)->post(route('backend.attendances.store'), ['cars_serviced' => 99]),
            'cars_serviced'
        );

        $this->assertSame(0, CleanerAttendance::where('user_id', $cleaner->id)->count());
    }

    public function test_filing_attendance_twice_corrects_the_day_rather_than_duplicating(): void
    {
        [, , $cleaner] = $this->makeServicedCar($this->ownSector, 'ATT-0004');

        $this->actingAs($cleaner)->post(route('backend.attendances.store'), ['cars_serviced' => 0]);
        $this->actingAs($cleaner)->post(route('backend.attendances.store'), ['cars_serviced' => 1]);

        $entries = CleanerAttendance::where('user_id', $cleaner->id)
            ->whereDate('date', Carbon::today())
            ->get();

        $this->assertCount(1, $entries);
        $this->assertSame(CleanerAttendance::STATUS_PRESENT, $entries->first()->status);
    }

    public function test_a_franchise_owner_only_sees_attendance_from_their_sectors(): void
    {
        [, , $mine] = $this->makeServicedCar($this->ownSector, 'ATT-0005');
        [, , $theirs] = $this->makeServicedCar($this->otherSector, 'ATT-0006');

        $this->actingAs($mine)->post(route('backend.attendances.store'), ['cars_serviced' => 1]);
        $this->actingAs($theirs)->post(route('backend.attendances.store'), ['cars_serviced' => 1]);

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.attendances.index_data'));

        $response->assertOk();
        $names = collect($response->json('data'))->pluck('cleaner_name')->all();

        $this->assertContains($mine->name, $names);
        $this->assertNotContains($theirs->name, $names);
    }

    public function test_only_a_cleaner_may_record_attendance(): void
    {
        $this->actingAs($this->franchiseOwner)
            ->post(route('backend.attendances.store'), ['cars_serviced' => 1])
            ->assertForbidden();
    }

    public function test_the_complaint_and_attendance_screens_render(): void
    {
        $complaint = $this->makeComplaint($this->ownSector, 'CMP-0014');

        $this->actingAs($this->admin)->get(route('backend.complaints.index'))->assertOk();
        $this->actingAs($this->admin)->get(route('backend.complaints.show', $complaint->id))->assertOk();
        $this->actingAs($complaint->customer)->get(route('backend.complaints.create'))->assertOk();
        $this->actingAs($complaint->cleaner)->get(route('backend.attendances.index'))->assertOk();
    }

    /**
     * Assert a request is rejected by validation for a given field.
     *
     * The session cannot be relied on here: this application registers
     * StartSession both globally and in the web group, so flashed errors are
     * lost before the response is inspected.
     */
    private function assertValidationFails(callable $request, string $field): void
    {
        $this->withoutExceptionHandling();

        try {
            $request();
            $this->fail('Expected validation to reject '.$field.'.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey($field, $e->errors());
        } finally {
            $this->withExceptionHandling();
        }
    }

    /**
     * @return array{0: Order, 1: User, 2: User}
     */
    private function makeServicedCar(Sector $sector, string $carNumber): array
    {
        $customer = $this->makeUser(strtolower($carNumber).'.customer@test.local', 'customer', $sector);
        $cleaner = $this->makeUser(strtolower($carNumber).'.cleaner@test.local', 'cleaner', $sector);

        $order = Order::create([
            'user_id' => $customer->id,
            'name' => $carNumber,
            'car_number' => $carNumber,
            'assigned_user_id' => $cleaner->id,
            'status' => 2,
            'start_date' => Carbon::today()->subMonth(),
            'renew_date' => Carbon::today()->addMonth(),
            'paid_amount' => 999,
        ]);

        return [$order, $customer, $cleaner];
    }

    private function makeComplaint(Sector $sector, string $carNumber): Complaint
    {
        [$order, $customer, $cleaner] = $this->makeServicedCar($sector, $carNumber);

        $complaint = Complaint::create([
            'order_id' => $order->id,
            'user_id' => $customer->id,
            'sector_id' => $sector->id,
            'assigned_user_id' => $cleaner->id,
            'message' => 'Test complaint for '.$carNumber,
            'status' => Complaint::STATUS_OPEN,
        ]);

        $complaint->setRelation('customer', $customer);
        $complaint->setRelation('cleaner', $cleaner);

        return $complaint;
    }

    private function makeUser(string $email, string $role, ?Sector $sector): User
    {
        $user = User::create([
            'name' => 'Test '.$email,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'mobile' => (string) random_int(6000000000, 9999999999),
            'password' => bcrypt('secret'),
            'status' => 1,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$role]);

        Userprofile::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => $email,
            'sector_id' => $sector?->id,
            'status' => 1,
        ]);

        return $user;
    }
}
