<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ChecklistPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Creates CRUD permissions for Checklists and ChecklistTemplates and assigns them to roles
     * following the same pattern as Assets.
     */
    public function run(): void
    {
        // Reset cached permissions before starting
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        // Create permissions for Checklists
        $checklistActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        $checklistPermissions = [];

        foreach ($checklistActions as $action) {
            $checklistPermissions[$action] = Permission::firstOrCreate(
                ['name' => "{$action} Checklists", 'guard_name' => 'web'],
                ['category' => 'Checklists']
            );
        }

        // Create permissions for ChecklistTemplates
        $templatePermissions = [];

        foreach ($checklistActions as $action) {
            $templatePermissions[$action] = Permission::firstOrCreate(
                ['name' => "{$action} ChecklistTemplates", 'guard_name' => 'web'],
                ['category' => 'Checklist Templates']
            );
        }

        // Get existing roles
        $superAdmin = Role::where('name', 'Super Admin')->where('guard_name', 'web')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->where('guard_name', 'web')->first();
        $regular = Role::where('name', 'Regular User')->where('guard_name', 'web')->first();

        // Assign all permissions to Super Admin
        if ($superAdmin) {
            foreach ($checklistPermissions as $permission) {
                if (! $superAdmin->hasPermissionTo($permission)) {
                    $superAdmin->givePermissionTo($permission);
                }
            }
            foreach ($templatePermissions as $permission) {
                if (! $superAdmin->hasPermissionTo($permission)) {
                    $superAdmin->givePermissionTo($permission);
                }
            }
        }

        // Assign List, Create, Read, Update to Security Admin (same as other entities)
        if ($securityAdmin) {
            foreach (['List', 'Create', 'Read', 'Update'] as $action) {
                if (! $securityAdmin->hasPermissionTo($checklistPermissions[$action])) {
                    $securityAdmin->givePermissionTo($checklistPermissions[$action]);
                }
                if (! $securityAdmin->hasPermissionTo($templatePermissions[$action])) {
                    $securityAdmin->givePermissionTo($templatePermissions[$action]);
                }
            }
        }

        // Assign List, Read to Regular User (same as other entities)
        if ($regular) {
            foreach (['List', 'Read'] as $action) {
                if (! $regular->hasPermissionTo($checklistPermissions[$action])) {
                    $regular->givePermissionTo($checklistPermissions[$action]);
                }
                if (! $regular->hasPermissionTo($templatePermissions[$action])) {
                    $regular->givePermissionTo($templatePermissions[$action]);
                }
            }
        }

        // Clear cache after assigning
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
