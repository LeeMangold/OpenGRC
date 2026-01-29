<?php

namespace Modules\DataManager\Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DataManagerPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear permission cache
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Define permissions for Data Manager
        $permissionNames = [
            'Import Data',
            'Export Data',
        ];

        $category = 'Data Manager';

        // Create permissions and store them
        $permissions = [];
        foreach ($permissionNames as $name) {
            $permissions[$name] = Permission::firstOrCreate(
                ['name' => $name, 'guard_name' => 'web'],
                ['category' => $category]
            );
        }

        // Clear cache again after creating permissions
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Get roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $regularUser = Role::where('name', 'Regular User')->first();
        $internalAuditor = Role::where('name', 'Internal Auditor')->first();

        // Assign permissions to Super Admin (all permissions)
        if ($superAdmin) {
            $superAdmin->syncPermissions(
                $superAdmin->permissions->merge(collect($permissions)->values())
            );
        }

        // Assign permissions to Security Admin (both import and export)
        if ($securityAdmin) {
            $securityAdmin->syncPermissions(
                $securityAdmin->permissions->merge(collect($permissions)->values())
            );
        }

        // Regular User gets Export only (read-only operation)
        if ($regularUser && isset($permissions['Export Data'])) {
            $regularUser->syncPermissions(
                $regularUser->permissions->merge([$permissions['Export Data']])
            );
        }

        // Internal Auditor gets Export only (for audit purposes)
        if ($internalAuditor && isset($permissions['Export Data'])) {
            $internalAuditor->syncPermissions(
                $internalAuditor->permissions->merge([$permissions['Export Data']])
            );
        }

        // Clear permission cache again
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
