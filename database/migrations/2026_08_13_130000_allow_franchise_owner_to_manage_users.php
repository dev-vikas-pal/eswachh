<?php

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const ROLE = 'franchise owner';

    /**
     * A Franchise Owner runs their own sectors day to day, which means adding
     * customers and cleaners. The permissions alone are not enough to reach
     * anybody else's records: UserController restricts every user it serves to
     * the franchise's own sectors, and refuses to grant any role beyond
     * customer and cleaner.
     *
     * @var array<int, string>
     */
    private const PERMISSIONS = [
        'add_users',
        'edit_users',
    ];

    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $role = Role::where('name', self::ROLE)->first();

        if (! $role) {
            return;
        }

        foreach (self::PERMISSIONS as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        $role->givePermissionTo(self::PERMISSIONS);

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
            $role->revokePermissionTo(self::PERMISSIONS);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
