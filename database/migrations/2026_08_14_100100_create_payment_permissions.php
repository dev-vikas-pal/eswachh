<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Franchise Owners see the payments taken in their own sectors. Only an
     * administrator may override a payment status by hand, so edit_payments is
     * not granted to them.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = ['view_payments', 'edit_payments'];

    private const FRANCHISE_PERMISSIONS = ['view_payments'];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role = Role::where('name', 'franchise owner')->first();

        if ($role) {
            $role->givePermissionTo(self::FRANCHISE_PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::where('name', 'franchise owner')->first();

        if ($role) {
            $role->revokePermissionTo(self::FRANCHISE_PERMISSIONS);
        }

        Permission::whereIn('name', self::PERMISSIONS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
