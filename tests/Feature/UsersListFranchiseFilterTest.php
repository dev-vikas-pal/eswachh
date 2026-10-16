<?php

namespace Tests\Feature;

use App\Http\Livewire\UsersIndex;
use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SectorService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Modules\Sector\Models\Sector;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class UsersListFranchiseFilterTest extends TestCase
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

        Role::firstOrCreate(['name' => 'super admin', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => 'customer', 'guard_name' => 'web']);
        Role::firstOrCreate(['name' => SectorService::FRANCHISE_ROLE, 'guard_name' => 'web']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->ownSector = Sector::create(['name' => 'Users Franchise A', 'status' => 1]);
        $this->otherSector = Sector::create(['name' => 'Users Franchise B', 'status' => 1]);

        $this->admin = $this->makeUser('users.admin@test.local', 'super admin');
        $this->franchiseOwner = $this->makeUser('users.franchise@test.local', SectorService::FRANCHISE_ROLE);
        $this->franchiseOwner->sectors()->sync([$this->ownSector->id]);

        SectorService::forgetCache();
    }

    public function test_an_admin_can_filter_the_user_list_by_franchise(): void
    {
        $own = $this->makeCustomer($this->ownSector, 'users.own@test.local', 'Own Franchise Customer');
        $other = $this->makeCustomer($this->otherSector, 'users.other@test.local', 'Other Franchise Customer');

        Livewire::actingAs($this->admin)
            ->test(UsersIndex::class)
            ->set('franchiseId', (string) $this->ownSector->id)
            ->assertSee($own->name)
            ->assertDontSee($other->name)
            ->assertSee('Franchise');
    }

    public function test_a_franchise_owner_only_sees_their_own_franchise_users(): void
    {
        $own = $this->makeCustomer($this->ownSector, 'users.own2@test.local', 'Visible Franchise Customer');
        $other = $this->makeCustomer($this->otherSector, 'users.other2@test.local', 'Hidden Franchise Customer');

        Livewire::actingAs($this->franchiseOwner)
            ->test(UsersIndex::class)
            ->assertSee($own->name)
            ->assertSee($this->franchiseOwner->name)
            ->assertDontSee($other->name)
            ->assertDontSee('Users Franchise B');
    }

    private function makeUser(string $email, string $role): User
    {
        $user = User::create([
            'name' => 'Test '.$role.' '.$email,
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

    private function makeCustomer(Sector $sector, string $email, string $name): User
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
}
