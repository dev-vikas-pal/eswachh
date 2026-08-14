<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SectorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Order\Models\Order;
use Modules\Sector\Models\Sector;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class FranchiseSectorAccessTest extends TestCase
{
    use DatabaseTransactions;

    private const TEST_AREA_ID = 999001;

    private Sector $ownSector;

    private Sector $otherSector;

    private User $franchiseOwner;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedRolesAndPermissions();

        // Both sit in the same area so the cascade tests exercise the sector
        // filter itself rather than the area boundary.
        $this->ownSector = Sector::create(['name' => 'Test Sector A', 'status' => 1, 'area_id' => self::TEST_AREA_ID]);
        $this->otherSector = Sector::create(['name' => 'Test Sector B', 'status' => 1, 'area_id' => self::TEST_AREA_ID]);

        $this->admin = $this->makeUser('admin@test.local', 'super admin');

        $this->franchiseOwner = $this->makeUser('franchise@test.local', SectorService::FRANCHISE_ROLE);
        $this->franchiseOwner->sectors()->sync([$this->ownSector->id]);

        SectorService::forgetCache();
    }

    public function test_franchise_owner_is_limited_to_their_own_sectors(): void
    {
        $this->actingAs($this->franchiseOwner);

        $this->assertTrue(SectorService::isFranchiseOwner());
        $this->assertSame([$this->ownSector->id], SectorService::allowedSectorIds());
        $this->assertSame([$this->ownSector->id], array_keys(SectorService::sectorOptions()));
    }

    public function test_admin_is_not_restricted_to_any_sector(): void
    {
        $this->actingAs($this->admin);

        $this->assertFalse(SectorService::isFranchiseOwner());
        $this->assertNull(SectorService::allowedSectorIds());
    }

    public function test_requesting_another_sector_is_rejected_server_side(): void
    {
        $this->actingAs($this->franchiseOwner);

        // The browser can send any sector id it likes; it must not be honoured.
        $this->assertSame(
            [$this->ownSector->id],
            SectorService::selectedSectorIds($this->ownSector->id)
        );

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        SectorService::selectedSectorIds($this->otherSector->id);
    }

    public function test_order_list_only_returns_orders_from_the_owned_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'MINE-0001');
        $theirs = $this->makeOrder($this->otherSector, 'THEIRS-0001');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.orders.index_data'));

        $response->assertOk();

        $carNumbers = collect($response->json('data'))->pluck('car_number')->all();

        $this->assertContains($mine->car_number, $carNumbers);
        $this->assertNotContains($theirs->car_number, $carNumbers);
    }

    public function test_order_list_filtered_to_a_foreign_sector_is_forbidden(): void
    {
        $this->makeOrder($this->otherSector, 'THEIRS-0002');

        $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.orders.index_data', ['filter_sector_id' => $this->otherSector->id]))
            ->assertForbidden();
    }

    public function test_admin_can_filter_the_order_list_by_any_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'ADMIN-0001');
        $theirs = $this->makeOrder($this->otherSector, 'ADMIN-0002');

        $response = $this->actingAs($this->admin)
            ->getJson(route('backend.orders.index_data', ['filter_sector_id' => $this->otherSector->id]));

        $response->assertOk();

        $carNumbers = collect($response->json('data'))->pluck('car_number')->all();

        $this->assertContains($theirs->car_number, $carNumbers);
        $this->assertNotContains($mine->car_number, $carNumbers);
    }

    public function test_franchise_owner_cannot_open_an_order_from_another_sector(): void
    {
        $theirs = $this->makeOrder($this->otherSector, 'THEIRS-0003');

        $this->actingAs($this->franchiseOwner)
            ->get(route('backend.orders.show', $theirs->id))
            ->assertNotFound();
    }

    public function test_franchise_owner_cannot_edit_or_delete_an_order_from_another_sector(): void
    {
        $theirs = $this->makeOrder($this->otherSector, 'THEIRS-0004');

        $this->actingAs($this->franchiseOwner)
            ->get(route('backend.orders.edit', $theirs->id))
            ->assertNotFound();

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.orders.update', $theirs->id), ['status' => 4])
            ->assertNotFound();

        $this->assertSame(2, (int) $theirs->fresh()->status);
    }

    public function test_franchise_owner_can_change_status_of_an_order_in_their_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'MINE-0002');

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.orders.update', $mine->id), ['status' => 4])
            ->assertRedirect();

        $this->assertSame(4, (int) $mine->fresh()->status);
    }

    public function test_dashboard_counts_and_revenue_are_scoped_to_the_owned_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'MINE-0003');
        $theirs = $this->makeOrder($this->otherSector, 'THEIRS-0005');

        $this->makePayment($mine, 500);
        $this->makePayment($theirs, 900);

        $response = $this->actingAs($this->franchiseOwner)->get(route('backend.dashboard'));
        $response->assertOk();

        $this->assertSame(1, $response->viewData('totalOrders'));
        $this->assertEqualsWithDelta(500, (float) $response->viewData('totalRevenue'), 0.01);

        $adminResponse = $this->actingAs($this->admin)->get(route('backend.dashboard'));
        $adminResponse->assertOk();

        $this->assertSame(2, $adminResponse->viewData('totalOrders'));
        $this->assertEqualsWithDelta(1400, (float) $adminResponse->viewData('totalRevenue'), 0.01);
    }

    public function test_dashboard_offers_only_the_owned_sectors_in_the_filter(): void
    {
        $response = $this->actingAs($this->franchiseOwner)->get(route('backend.dashboard'));

        $this->assertSame([$this->ownSector->id], array_keys($response->viewData('sectorOptions')));
        $this->assertFalse($response->viewData('canSeeAllSectors'));

        $adminResponse = $this->actingAs($this->admin)->get(route('backend.dashboard'));

        $this->assertContains($this->otherSector->id, array_keys($adminResponse->viewData('sectorOptions')));
        $this->assertTrue($adminResponse->viewData('canSeeAllSectors'));
    }

    public function test_new_orders_are_stamped_with_the_customers_sector(): void
    {
        $customer = $this->makeCustomer($this->otherSector, 'stamp@test.local');

        $order = Order::create([
            'user_id' => $customer->id,
            'car_number' => 'STAMP-0001',
            'status' => 2,
        ]);

        $this->assertSame($this->otherSector->id, (int) $order->sector_id);
    }

    public function test_orders_follow_the_customer_when_they_move_sector(): void
    {
        $order = $this->makeOrder($this->ownSector, 'MOVE-0001');

        $profile = Userprofile::where('user_id', $order->user_id)->firstOrFail();
        $profile->update(['sector_id' => $this->otherSector->id]);

        $this->assertSame($this->otherSector->id, (int) $order->fresh()->sector_id);
    }

    public function test_recorded_revenue_stays_with_the_sector_that_earned_it(): void
    {
        $order = $this->makeOrder($this->ownSector, 'MOVE-0002');
        $this->makePayment($order, 750);

        $profile = Userprofile::where('user_id', $order->user_id)->firstOrFail();
        $profile->update(['sector_id' => $this->otherSector->id]);

        $this->assertSame(
            $this->ownSector->id,
            (int) DB::table('payment_history')->where('order_id', $order->id)->value('sector_id')
        );
    }

    public function test_customer_picker_only_returns_customers_from_the_owned_sector(): void
    {
        $mine = $this->makeCustomer($this->ownSector, 'mine.customer@test.local', 'Zizzo Mine');
        $theirs = $this->makeCustomer($this->otherSector, 'their.customer@test.local', 'Zizzo Theirs');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.users.index_list', ['q' => 'Zizzo', 'user_type' => 'customer']));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->map('intval')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_franchise_owner_cannot_open_a_user_from_another_sector(): void
    {
        $theirs = $this->makeCustomer($this->otherSector, 'hidden@test.local');

        $this->actingAs($this->franchiseOwner)
            ->get(route('backend.users.show', $theirs->id))
            ->assertNotFound();
    }

    public function test_franchise_owner_without_sectors_sees_nothing(): void
    {
        $this->makeOrder($this->ownSector, 'NONE-0001');

        $stranded = $this->makeUser('stranded@test.local', SectorService::FRANCHISE_ROLE);
        SectorService::forgetCache();

        $response = $this->actingAs($stranded)->getJson(route('backend.orders.index_data'));

        $response->assertOk();
        $this->assertSame([], $response->json('data'));
    }

    public function test_cleaner_picker_only_returns_cleaners_from_the_owned_sector(): void
    {
        $mine = $this->makeCleaner($this->ownSector, 'mine.cleaner@test.local', 'Quixo Mine');
        $theirs = $this->makeCleaner($this->otherSector, 'their.cleaner@test.local', 'Quixo Theirs');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.users.index_list', ['q' => 'Quixo', 'user_type' => 'cleaner']));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->map('intval')->all();

        $this->assertContains($mine->id, $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_cleaner_picker_offers_options_before_anything_is_typed(): void
    {
        $mine = $this->makeCleaner($this->ownSector, 'untyped.cleaner@test.local');
        $this->makeCleaner($this->otherSector, 'untyped.other@test.local');

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.users.index_list', ['user_type' => 'cleaner']));

        $response->assertOk();

        $ids = collect($response->json())->pluck('id')->map('intval')->all();

        $this->assertContains($mine->id, $ids, 'The picker should list cleaners without a search term.');
    }

    public function test_cleaner_sector_comes_from_their_profile_not_their_orders(): void
    {
        // A cleaner who belongs to one sector but happens to be assigned an
        // order in another must stay with the sector on their own profile.
        $cleaner = $this->makeCleaner($this->otherSector, 'crossover@test.local', 'Quixo Crossover');

        $orderInOwnSector = $this->makeOrder($this->ownSector, 'CROSS-0001');
        $orderInOwnSector->update(['assigned_user_id' => $cleaner->id]);

        $response = $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.users.index_list', ['q' => 'Quixo', 'user_type' => 'cleaner']));

        $ids = collect($response->json())->pluck('id')->map('intval')->all();

        $this->assertNotContains($cleaner->id, $ids);
    }

    public function test_a_cleaner_is_created_with_exactly_one_sector(): void
    {
        $this->actingAs($this->admin)
            ->post(route('backend.users.store'), [
                'first_name' => 'New',
                'last_name' => 'Cleaner',
                'email' => 'new.cleaner@test.local',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'status' => 1,
                'roles' => ['cleaner'],
                'sector_id' => $this->otherSector->id,
            ])
            ->assertRedirect();

        $cleaner = User::where('email', 'new.cleaner@test.local')->firstOrFail();

        $this->assertTrue($cleaner->hasRole('cleaner'));
        $this->assertSame(
            $this->otherSector->id,
            (int) Userprofile::where('user_id', $cleaner->id)->value('sector_id')
        );
        // One sector only: the multi-sector pivot stays empty for cleaners.
        $this->assertSame([], $cleaner->sectors()->pluck('sectors.id')->all());
    }

    public function test_editing_a_cleaner_does_not_require_the_sector_again(): void
    {
        // The sector is asked for at creation; afterwards it is maintained on
        // the Edit Profile screen, so update must not demand it.
        $cleaner = $this->makeCleaner($this->ownSector, 'stable.cleaner@test.local');

        $this->actingAs($this->admin)
            ->patch(route('backend.users.update', $cleaner->id), [
                'first_name' => 'Stable',
                'last_name' => 'Cleaner',
                'email' => 'stable.cleaner@test.local',
                'roles' => ['cleaner'],
            ])
            ->assertSessionHasNoErrors();

        $this->assertSame(
            $this->ownSector->id,
            (int) Userprofile::where('user_id', $cleaner->id)->value('sector_id')
        );
    }

    public function test_a_cleaner_cannot_be_created_without_a_sector(): void
    {
        $errors = $this->assertUserStoreRejected([
            'first_name' => 'Sectorless',
            'last_name' => 'Cleaner',
            'email' => 'sectorless.cleaner@test.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'status' => 1,
            'roles' => ['cleaner'],
        ]);

        $this->assertArrayHasKey('sector_id', $errors);
        $this->assertNull(User::where('email', 'sectorless.cleaner@test.local')->first());
    }

    public function test_a_franchise_owner_cannot_be_created_without_a_sector(): void
    {
        $errors = $this->assertUserStoreRejected([
            'first_name' => 'Sectorless',
            'last_name' => 'Franchise',
            'email' => 'sectorless.franchise@test.local',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'status' => 1,
            'roles' => [SectorService::FRANCHISE_ROLE],
        ]);

        $this->assertArrayHasKey('sectors', $errors);
        $this->assertNull(User::where('email', 'sectorless.franchise@test.local')->first());
    }

    /**
     * Asserts creating a user is rejected and returns the validation errors.
     *
     * The exception is inspected directly rather than the session: this app
     * registers StartSession in both the global stack and the web group, which
     * makes flashed error bags unreliable.
     *
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function assertUserStoreRejected(array $payload): array
    {
        $this->withoutExceptionHandling();

        try {
            $this->actingAs($this->admin)->post(route('backend.users.store'), $payload);
        } catch (ValidationException $e) {
            return $e->errors();
        } finally {
            $this->withExceptionHandling();
        }

        $this->fail('Expected the user to be rejected, but it was created.');
    }

    public function test_franchise_owner_can_create_a_cleaner_in_their_own_sector(): void
    {
        $this->actingAs($this->franchiseOwner)
            ->post(route('backend.users.store'), [
                'first_name' => 'Franchise',
                'last_name' => 'Made',
                'email' => 'franchise.made@test.local',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'status' => 1,
                'roles' => ['cleaner'],
                'sector_id' => $this->ownSector->id,
            ])
            ->assertRedirect();

        $created = User::where('email', 'franchise.made@test.local')->firstOrFail();

        $this->assertTrue($created->hasRole('cleaner'));
        $this->assertSame(
            $this->ownSector->id,
            (int) Userprofile::where('user_id', $created->id)->value('sector_id')
        );
    }

    public function test_the_location_cascade_offers_only_the_owned_sectors(): void
    {
        $response = $this->actingAs($this->franchiseOwner)
            ->postJson(route('backend.users.locationOptions'), [
                'parent_type' => 'sectors',
                'parent_id' => self::TEST_AREA_ID,
            ]);

        $response->assertOk();

        $html = $response->json('html');

        $this->assertStringContainsString('value="'.$this->ownSector->id.'"', $html);
        $this->assertStringNotContainsString('value="'.$this->otherSector->id.'"', $html);
    }

    public function test_the_location_cascade_is_unfiltered_for_an_admin(): void
    {
        $html = $this->actingAs($this->admin)
            ->postJson(route('backend.users.locationOptions'), [
                'parent_type' => 'sectors',
                'parent_id' => self::TEST_AREA_ID,
            ])
            ->assertOk()
            ->json('html');

        $this->assertStringContainsString('value="'.$this->ownSector->id.'"', $html);
        $this->assertStringContainsString('value="'.$this->otherSector->id.'"', $html);
    }

    public function test_franchise_owner_cannot_edit_the_profile_of_another_sector(): void
    {
        $theirs = $this->makeCustomer($this->otherSector, 'otherprofile@test.local');

        $this->actingAs($this->franchiseOwner)
            ->get(route('backend.users.profileEdit', $theirs->id))
            ->assertNotFound();

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.users.profileUpdate', $theirs->id), [
                'first_name' => 'Hijacked',
                'last_name' => 'Profile',
                'email' => 'otherprofile@test.local',
                'sector_id' => $this->otherSector->id,
            ])
            ->assertNotFound();
    }

    public function test_franchise_owner_cannot_move_a_profile_into_another_sector(): void
    {
        $mine = $this->makeCustomer($this->ownSector, 'staysput@test.local');

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.users.profileUpdate', $mine->id), [
                'first_name' => 'Stays',
                'last_name' => 'Put',
                'email' => 'staysput@test.local',
                'sector_id' => $this->otherSector->id,
            ])
            ->assertForbidden();

        $this->assertSame(
            $this->ownSector->id,
            (int) Userprofile::where('user_id', $mine->id)->value('sector_id')
        );
    }

    public function test_franchise_owner_cannot_create_a_user_in_another_sector(): void
    {
        $this->actingAs($this->franchiseOwner)
            ->post(route('backend.users.store'), [
                'first_name' => 'Wrong',
                'last_name' => 'Sector',
                'email' => 'wrong.sector@test.local',
                'password' => 'secret123',
                'password_confirmation' => 'secret123',
                'status' => 1,
                'roles' => ['customer'],
                'sector_id' => $this->otherSector->id,
            ])
            ->assertForbidden();

        $this->assertNull(User::where('email', 'wrong.sector@test.local')->first());
    }

    public function test_franchise_owner_cannot_grant_privileged_roles(): void
    {
        foreach (['super admin', SectorService::FRANCHISE_ROLE, 'customer'] as $role) {
            $this->actingAs($this->franchiseOwner)
                ->post(route('backend.users.store'), [
                    'first_name' => 'Escalate',
                    'last_name' => 'Attempt',
                    'email' => 'escalate@test.local',
                    'password' => 'secret123',
                    'password_confirmation' => 'secret123',
                    'status' => 1,
                    'roles' => [$role],
                    'sector_id' => $this->ownSector->id,
                ])
                ->assertForbidden();
        }

        $this->assertNull(User::where('email', 'escalate@test.local')->first());
    }

    public function test_franchise_owner_cannot_edit_a_user_from_another_sector(): void
    {
        $theirs = $this->makeCustomer($this->otherSector, 'notmine@test.local');

        $this->actingAs($this->franchiseOwner)
            ->get(route('backend.users.edit', $theirs->id))
            ->assertNotFound();

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.users.update', $theirs->id), [
                'first_name' => 'Hijacked',
                'last_name' => 'Name',
                'email' => 'notmine@test.local',
            ])
            ->assertNotFound();

        $this->assertNotSame('Hijacked', $theirs->fresh()->first_name);
    }

    public function test_franchise_owner_can_assign_a_cleaner_to_their_own_order(): void
    {
        $order = $this->makeOrder($this->ownSector, 'ASSIGN-0001');
        $cleaner = $this->makeCleaner($this->ownSector, 'assignable@test.local');

        $this->actingAs($this->franchiseOwner)
            ->patch(route('backend.orders.update', $order->id), [
                'user_id' => $order->user_id,
                'assigned_user_id' => $cleaner->id,
                'status' => 2,
            ])
            ->assertRedirect();

        $this->assertSame($cleaner->id, (int) $order->fresh()->assigned_user_id);
    }

    public function test_franchise_owner_can_bulk_assign_a_cleaner_in_their_sector(): void
    {
        $first = $this->makeOrder($this->ownSector, 'BULK-0001');
        $second = $this->makeOrder($this->ownSector, 'BULK-0002');
        $cleaner = $this->makeCleaner($this->ownSector, 'bulk.cleaner@test.local');

        $this->actingAs($this->franchiseOwner)
            ->postJson(route('backend.orders.bulkAssignCleaner'), [
                'ids' => [$first->id, $second->id],
                'assigned_user_id' => $cleaner->id,
            ])
            ->assertOk()
            ->assertJson(['assigned' => 2]);

        $this->assertSame($cleaner->id, (int) $first->fresh()->assigned_user_id);
        $this->assertSame($cleaner->id, (int) $second->fresh()->assigned_user_id);
    }

    public function test_bulk_assign_skips_orders_from_another_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'BULK-0003');
        $theirs = $this->makeOrder($this->otherSector, 'BULK-0004');
        $cleaner = $this->makeCleaner($this->ownSector, 'bulk.cleaner2@test.local');

        $this->actingAs($this->franchiseOwner)
            ->postJson(route('backend.orders.bulkAssignCleaner'), [
                'ids' => [$mine->id, $theirs->id],
                'assigned_user_id' => $cleaner->id,
            ])
            ->assertOk()
            ->assertJson(['assigned' => 1]);

        $this->assertSame($cleaner->id, (int) $mine->fresh()->assigned_user_id);
        $this->assertNull($theirs->fresh()->assigned_user_id);
    }

    public function test_bulk_assign_refuses_a_cleaner_from_another_sector(): void
    {
        $mine = $this->makeOrder($this->ownSector, 'BULK-0005');
        $foreignCleaner = $this->makeCleaner($this->otherSector, 'foreign.cleaner@test.local');

        $this->actingAs($this->franchiseOwner)
            ->postJson(route('backend.orders.bulkAssignCleaner'), [
                'ids' => [$mine->id],
                'assigned_user_id' => $foreignCleaner->id,
            ])
            ->assertStatus(422);

        $this->assertNull($mine->fresh()->assigned_user_id);
    }

    public function test_order_list_does_not_query_once_per_row(): void
    {
        foreach (['NPLUS-1', 'NPLUS-2'] as $car) {
            $this->makeOrder($this->ownSector, $car);
        }

        $withTwoRows = $this->countQueriesForOrderList();

        foreach (['NPLUS-3', 'NPLUS-4', 'NPLUS-5', 'NPLUS-6'] as $car) {
            $this->makeOrder($this->ownSector, $car);
        }

        $withSixRows = $this->countQueriesForOrderList();

        // Four more rows must not mean more queries. Resolving the customer,
        // cleaner, package and car per row used to add four queries each.
        $this->assertLessThanOrEqual(
            2,
            $withSixRows - $withTwoRows,
            "Order list went from {$withTwoRows} to {$withSixRows} queries when 4 rows were added; it is querying per row."
        );
    }

    private function countQueriesForOrderList(): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($this->franchiseOwner)
            ->getJson(route('backend.orders.index_data'))
            ->assertOk();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    /**
     * Roles and permissions live in migrations, so the test database gets its
     * own copy of the ones these tests rely on.
     */
    private function seedRolesAndPermissions(): void
    {
        // Cleared up front as well as after: another test class may have run
        // migrate:fresh, which empties the tables while Spatie's cache still
        // lists the old permissions. firstOrCreate would then find nothing in
        // the database, try to create, and be refused by the stale cache.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'view_backend', 'view_orders', 'add_orders', 'edit_orders', 'delete_orders',
            'view_users', 'add_users', 'edit_users', 'view_customers', 'view_cleaners', 'view_cloths',
            'view_cars', 'view_packages', 'view_internaltypes', 'view_durations',
            'view_reports', 'view_reminders',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'cleaner', 'guard_name' => 'web']);

        Role::firstOrCreate(['name' => SectorService::FRANCHISE_ROLE, 'guard_name' => 'web'])
            ->syncPermissions($permissions);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
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

    private function makeCustomer(Sector $sector, string $email, string $name = 'Test Customer'): User
    {
        $customer = User::create([
            'name' => $name,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $email,
            'mobile' => (string) random_int(6000000000, 9999999999),
            'password' => bcrypt('secret'),
            'status' => 1,
            'email_verified_at' => now(),
        ]);

        $customer->syncRoles(['customer']);

        Userprofile::create([
            'user_id' => $customer->id,
            'name' => $name,
            'first_name' => 'Test',
            'last_name' => 'Customer',
            'email' => $email,
            'sector_id' => $sector->id,
            'status' => 1,
        ]);

        return $customer;
    }

    private function makeCleaner(Sector $sector, string $email, string $name = 'Test Cleaner'): User
    {
        $cleaner = $this->makeCustomer($sector, $email, $name);
        $cleaner->syncRoles(['cleaner']);

        return $cleaner;
    }

    private function makeOrder(Sector $sector, string $carNumber): Order
    {
        $customer = $this->makeCustomer($sector, strtolower($carNumber).'@test.local');

        return Order::create([
            'user_id' => $customer->id,
            'car_number' => $carNumber,
            'name' => $carNumber,
            'status' => 2,
            'paid_amount' => 1000,
            'start_date' => now()->subMonth(),
            'renew_date' => now()->addMonth(),
        ]);
    }

    private function makePayment(Order $order, float $amount): void
    {
        DB::table('payment_history')->insert([
            'user_id' => $order->user_id,
            'order_id' => $order->id,
            'sector_id' => $order->sector_id,
            'payment_amount' => $amount,
            'currency' => 'INR',
            'payment_status' => 'captured',
            'payment_method' => 'upi',
            'payment_date_time' => now(),
            'payment_gateway' => 'Razorpay',
            'payment_for' => 'Subscription',
        ]);
    }
}
