<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use App\Models\Userprofile;
use App\Services\SectorService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Order\Models\Order;
use Modules\Sector\Models\Sector;
use Spatie\Permission\PermissionRegistrar;

/**
 * Sample data for manually testing Franchise Owner sector access.
 *
 * Everything it creates is tagged so it can be removed again:
 *   users  - email ends with @franchisetest.local
 *   orders - car_number starts with FT-
 *
 * Re-running the seeder removes the previous set first, so it is safe to run
 * as often as you like. Remove the data for good with:
 *   php artisan db:seed --class=FranchiseTestDataSeeder --  (see purge() below)
 */
class FranchiseTestDataSeeder extends Seeder
{
    private const EMAIL_DOMAIN = '@franchisetest.local';

    private const CAR_PREFIX = 'FT-';

    private const PASSWORD = 'Franchise@123';

    public function run(): void
    {
        // Roles are created by migration; the permission cache may still be
        // holding the state from before that ran.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->purge();

        $chi4 = $this->sector('Chi 4');
        $chi3 = $this->sector('Chi 3');
        $phi4 = $this->sector('Phi 4');

        if (! $chi4 || ! $chi3 || ! $phi4) {
            $this->command->error('Expected sectors Chi 4, Chi 3 and Phi 4 to exist. Nothing was seeded.');

            return;
        }

        // Franchise A owns two sectors, which is what exercises the
        // "select among my own sectors" behaviour.
        $franchiseA = $this->user('Test Franchise A', 'franchise.a', SectorService::FRANCHISE_ROLE);
        $franchiseA->sectors()->sync([$chi4->id, $chi3->id]);

        // Franchise B exists purely to prove the two cannot see each other.
        $franchiseB = $this->user('Test Franchise B', 'franchise.b', SectorService::FRANCHISE_ROLE);
        $franchiseB->sectors()->sync([$phi4->id]);

        $cleanerChi4 = $this->cleaner('Test Cleaner Chi4', 'cleaner.chi4', $chi4);
        $cleanerChi3 = $this->cleaner('Test Cleaner Chi3', 'cleaner.chi3', $chi3);
        $cleanerPhi4 = $this->cleaner('Test Cleaner Phi4', 'cleaner.phi4', $phi4);

        // Chi 4: 2 active, 1 hold, 1 expired
        $this->order($chi4, 'CHI4-01', 'active', $cleanerChi4, [1500, 900]);
        $this->order($chi4, 'CHI4-02', 'active', $cleanerChi4, [800]);
        $this->order($chi4, 'CHI4-03', 'hold', $cleanerChi4, [500]);
        $this->order($chi4, 'CHI4-04', 'expired', null, [300]);

        // Chi 3: 1 active, 1 hold
        $this->order($chi3, 'CHI3-01', 'active', $cleanerChi3, [1000, 700]);
        $this->order($chi3, 'CHI3-02', 'hold', $cleanerChi3, [800]);

        // Phi 4: 1 active, 1 expired - belongs to the other franchise
        $this->order($phi4, 'PHI4-01', 'active', $cleanerPhi4, [800]);
        $this->order($phi4, 'PHI4-02', 'expired', null, [1000]);

        $this->report([$chi4, $chi3, $phi4]);
    }

    /**
     * Remove every record this seeder has ever created.
     */
    public function purge(): void
    {
        $userIds = User::withTrashed()
            ->where('email', 'like', '%'.self::EMAIL_DOMAIN)
            ->pluck('id');

        $orderIds = Order::withTrashed()
            ->where('car_number', 'like', self::CAR_PREFIX.'%')
            ->pluck('id');

        DB::table('payment_history')->whereIn('order_id', $orderIds)->delete();
        Order::withTrashed()->whereIn('id', $orderIds)->forceDelete();

        DB::table('sector_user')->whereIn('user_id', $userIds)->delete();
        DB::table('model_has_roles')->whereIn('model_id', $userIds)->where('model_type', User::class)->delete();
        DB::table('model_has_permissions')->whereIn('model_id', $userIds)->where('model_type', User::class)->delete();
        Userprofile::withTrashed()->whereIn('user_id', $userIds)->forceDelete();
        User::withTrashed()->whereIn('id', $userIds)->forceDelete();
    }

    private function sector(string $name): ?Sector
    {
        return Sector::where('name', $name)->where('status', 1)->first();
    }

    private function user(string $name, string $handle, string $role): User
    {
        [$first, $last] = [explode(' ', $name)[0], last(explode(' ', $name))];

        $user = User::create([
            'name' => $name,
            'first_name' => $first,
            'last_name' => $last,
            'username' => $handle,
            'email' => $handle.self::EMAIL_DOMAIN,
            'mobile' => (string) random_int(6000000000, 9999999999),
            'password' => Hash::make(self::PASSWORD),
            'status' => 1,
            'email_verified_at' => now(),
        ]);

        $user->syncRoles([$role]);

        Userprofile::create([
            'user_id' => $user->id,
            'name' => $name,
            'first_name' => $first,
            'last_name' => $last,
            'username' => $handle,
            'email' => $user->email,
            'mobile' => $user->mobile,
            'status' => 1,
        ]);

        return $user;
    }

    private function cleaner(string $name, string $handle, Sector $sector): User
    {
        $cleaner = $this->user($name, $handle, 'cleaner');

        // A cleaner's sector lives on their own profile, stored with its
        // parents so the profile screen's cascade can show it.
        Userprofile::where('user_id', $cleaner->id)->update(
            ['sector_id' => $sector->id] + SectorService::locationChainFor($sector->id)
        );

        return $cleaner;
    }

    /**
     * @param  array<int, float>  $payments
     */
    private function order(Sector $sector, string $reference, string $state, ?User $cleaner, array $payments): void
    {
        $customer = $this->user('Test Customer '.$reference, 'customer.'.strtolower($reference), 'customer');
        Userprofile::where('user_id', $customer->id)->update([
            'sector_id' => $sector->id,
            'house_no' => 'Flat '.$reference,
        ] + SectorService::locationChainFor($sector->id));

        $renewDate = $state === 'expired' ? now()->subDays(10) : now()->addMonths(2);

        $order = Order::create([
            'user_id' => $customer->id,
            'name' => 'Test Order '.$reference,
            'car_number' => self::CAR_PREFIX.$reference,
            'car_id' => 1,
            'package_id' => 1,
            'cleaning_type' => 1,
            'pakage_type' => 1,
            'status' => $state === 'hold' ? 4 : 2,
            'assigned_user_id' => $cleaner?->id,
            'start_date' => now()->subMonth(),
            'renew_date' => $renewDate,
            'paid_amount' => array_sum($payments),
            'order_type' => 'online',
            'payment_mode' => 'upi',
            'payment_date' => now()->subMonth(),
        ]);

        // First payment lands this month, the rest in earlier months, so the
        // two revenue tiles show different figures.
        foreach (array_values($payments) as $index => $amount) {
            DB::table('payment_history')->insert([
                'user_id' => $customer->id,
                'order_id' => $order->id,
                'sector_id' => $order->sector_id,
                'payment_amount' => $amount,
                'currency' => 'INR',
                'payment_status' => 'captured',
                'payment_method' => 'upi',
                'payment_date_time' => $index === 0 ? now()->startOfMonth()->addDays(2) : now()->subMonths($index + 1),
                'payment_gateway' => 'Razorpay',
                'payment_for' => 'Subscription',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * @param  array<int, Sector>  $sectors
     */
    private function report(array $sectors): void
    {
        $this->command->info('');
        $this->command->info('Franchise test data created. Password for every account: '.self::PASSWORD);
        $this->command->info('');

        $rows = [];

        foreach ($sectors as $sector) {
            $orders = Order::where('sector_id', $sector->id)->where('car_number', 'like', self::CAR_PREFIX.'%');

            $rows[] = [
                $sector->name,
                (clone $orders)->where('status', 2)->count(),
                (clone $orders)->where('status', 2)->whereDate('renew_date', '<', Carbon::today())->count(),
                (clone $orders)->where('status', 4)->count(),
                number_format($this->revenue($sector->id), 2),
                number_format($this->revenue($sector->id, true), 2),
            ];
        }

        $this->command->table(
            ['Sector', 'Subscriptions', 'Expired', 'Hold', 'Revenue', 'Revenue this month'],
            $rows
        );

        $this->command->info('franchise.a'.self::EMAIL_DOMAIN.'  sees Chi 4 + Chi 3');
        $this->command->info('franchise.b'.self::EMAIL_DOMAIN.'  sees Phi 4 only');
        $this->command->info('');
        $this->command->info('Note: these figures cover seeded data only. A super admin also sees your existing live records.');
    }

    private function revenue(int $sectorId, bool $thisMonthOnly = false): float
    {
        $query = DB::table('payment_history')
            ->join('orders', 'orders.id', '=', 'payment_history.order_id')
            ->where('payment_history.sector_id', $sectorId)
            ->where('payment_history.payment_status', 'captured')
            ->where('orders.car_number', 'like', self::CAR_PREFIX.'%');

        if ($thisMonthOnly) {
            $query->whereBetween('payment_history.payment_date_time', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth(),
            ]);
        }

        return (float) $query->sum('payment_history.payment_amount');
    }
}
