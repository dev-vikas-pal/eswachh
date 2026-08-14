<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ROLE = 'franchise owner';

    /**
     * Permissions granted to a Franchise Owner.
     *
     * The master-data view permissions (cars, packages, internaltypes,
     * durations) are required by the select2 endpoints the order form depends
     * on. The matching sidebar entries are hidden separately in
     * App\Http\Middleware\GenerateMenus so a franchise owner can use the order
     * form without getting access to the Manage Masters navigation.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'view_backend',
        'view_orders',
        'add_orders',
        'edit_orders',
        'view_users',
        'view_customers',
        'view_cleaners',
        'view_cloths',
        'view_cars',
        'view_packages',
        'view_internaltypes',
        'view_durations',
        'view_reports',
        'view_reminders',
    ];

    /**
     * Permissions that do not exist yet and are created by this migration.
     *
     * @var array<int, string>
     */
    private const NEW_PERMISSIONS = [
        'view_reports',
        'view_reminders',
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

        $role = Role::firstOrCreate(['name' => self::ROLE, 'guard_name' => 'web']);
        $role->syncPermissions(self::PERMISSIONS);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $role = Role::where('name', self::ROLE)->first();

        if ($role) {
            $role->syncPermissions([]);
            $role->delete();
        }

        Permission::whereIn('name', self::NEW_PERMISSIONS)->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
