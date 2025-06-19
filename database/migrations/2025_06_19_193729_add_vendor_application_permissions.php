<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create Vendor permissions
        $vendorActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($vendorActions as $action) {
            Permission::firstOrCreate([
                'name' => "{$action} Vendors",
                'category' => 'Vendors'
            ]);
        }

        // Create Application permissions
        $applicationActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($applicationActions as $action) {
            Permission::firstOrCreate([
                'name' => "{$action} Applications",
                'category' => 'Applications'
            ]);
        }

        // Get the roles
        $regular = Role::where('name', 'Regular User')->first();
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $internalAuditor = Role::where('name', 'Internal Auditor')->first();

        // Get the permissions
        $vendorPermissions = Permission::where('category', 'Vendors')->get();
        $applicationPermissions = Permission::where('category', 'Applications')->get();

        // Assign permissions to Super Admin (all permissions)
        $superAdmin->givePermissionTo($vendorPermissions);
        $superAdmin->givePermissionTo($applicationPermissions);

        // Assign permissions to Regular User (List and Read only)
        $regular->givePermissionTo([
            'List Vendors',
            'Read Vendors',
            'List Applications',
            'Read Applications',
        ]);

        // Assign permissions to Security Admin (List, Create, Read, Update - no Delete)
        $securityAdmin->givePermissionTo([
            'List Vendors',
            'Create Vendors',
            'Read Vendors',
            'Update Vendors',
            'List Applications',
            'Create Applications',
            'Read Applications',
            'Update Applications',
        ]);

        // Assign permissions to Internal Auditor (List and Read only)
        $internalAuditor->givePermissionTo([
            'List Vendors',
            'Read Vendors',
            'List Applications',
            'Read Applications',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Vendor permissions
        $vendorActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($vendorActions as $action) {
            Permission::where('name', "{$action} Vendors")->delete();
        }

        // Remove Application permissions
        $applicationActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($applicationActions as $action) {
            Permission::where('name', "{$action} Applications")->delete();
        }
    }
};
