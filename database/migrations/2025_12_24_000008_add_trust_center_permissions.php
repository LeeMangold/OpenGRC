<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Trust Center permissions
        $trustCenterPermissions = [
            'Manage Trust Center' => 'Trust Center',
            'Manage Trust Access' => 'Trust Center',
        ];

        foreach ($trustCenterPermissions as $name => $category) {
            Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['category' => $category]
            );
        }

        // Get the roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        // Get Trust Center permissions
        $permissions = Permission::where('category', 'Trust Center')->get();

        // Assign permissions to Super Admin
        if ($superAdmin) {
            $superAdmin->givePermissionTo($permissions);
        }

        // Assign permissions to Security Admin
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions from roles first
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        $permissionNames = ['Manage Trust Center', 'Manage Trust Access'];

        if ($superAdmin) {
            $superAdmin->revokePermissionTo($permissionNames);
        }

        if ($securityAdmin) {
            $securityAdmin->revokePermissionTo($permissionNames);
        }

        // Delete the permissions
        Permission::whereIn('name', $permissionNames)->delete();
    }
};
