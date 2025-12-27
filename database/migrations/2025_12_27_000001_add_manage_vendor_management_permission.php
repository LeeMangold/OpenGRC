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
        // Create Manage Vendor Management permission
        Permission::firstOrCreate(
            ['name' => 'Manage Vendor Management', 'guard_name' => 'web'],
            ['category' => 'Vendors']
        );

        // Get the roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        // Assign permission to Super Admin
        if ($superAdmin) {
            $superAdmin->givePermissionTo('Manage Vendor Management');
        }

        // Assign permission to Security Admin
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo('Manage Vendor Management');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permission from roles first
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        if ($superAdmin) {
            $superAdmin->revokePermissionTo('Manage Vendor Management');
        }

        if ($securityAdmin) {
            $securityAdmin->revokePermissionTo('Manage Vendor Management');
        }

        // Delete the permission
        Permission::where('name', 'Manage Vendor Management')->delete();
    }
};
