<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Who gets what:
     *
     *   customer         raises complaints and reads their own history
     *   cleaner          reads the complaints assigned to them, closes them,
     *                    and reports their own attendance
     *   franchise owner  reads both, for their own sectors only
     *   super admin      everything, via the usual gate bypass
     *
     * @var array<string, array<int, string>>
     */
    private const GRANTS = [
        'customer' => ['view_complaints', 'add_complaints'],
        'cleaner' => ['view_complaints', 'edit_complaints', 'view_attendances', 'add_attendances'],
        'franchise owner' => ['view_complaints', 'view_attendances'],
    ];

    private const PERMISSIONS = [
        'view_complaints',
        'add_complaints',
        'edit_complaints',
        'view_attendances',
        'add_attendances',
    ];

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

        foreach (self::GRANTS as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->givePermissionTo($permissions);
            }
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
        foreach (self::GRANTS as $roleName => $permissions) {
            $role = Role::where('name', $roleName)->first();

            if ($role) {
                $role->revokePermissionTo($permissions);
            }
        }

        Permission::whereIn('name', self::PERMISSIONS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
