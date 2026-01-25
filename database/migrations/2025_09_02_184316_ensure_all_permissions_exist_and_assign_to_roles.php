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
        // Define all entities and actions
        $entities = [
            'Standards', 'Controls', 'Implementations', 'Audits', 'AuditItems',
            'Programs', 'Vendors', 'Applications', 'Risks', 'DataRequests',
            'DataRequestResponses', 'FileAttachments',
        ];
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        // Create all resource permissions
        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => "{$action} {$entity}",
                    'category' => $entity,
                    'guard_name' => 'web',
                ]);
            }
        }

        // Create additional permissions
        $additionalPermissions = [
            ['name' => 'Configure Authentication', 'category' => 'other'],
            ['name' => 'Manage Users', 'category' => 'other'],
            ['name' => 'View Audit Log', 'category' => 'other'],
            ['name' => 'Manage Preferences', 'category' => 'other'],
            ['name' => 'Manage Bundles', 'category' => 'Bundles'],
            ['name' => 'View Bundles', 'category' => 'Bundles'],
        ];

        foreach ($additionalPermissions as $permissionData) {
            Permission::firstOrCreate([
                'name' => $permissionData['name'],
                'category' => $permissionData['category'],
                'guard_name' => 'web',
            ]);
        }

        // Get all permissions
        $allPermissions = Permission::all();

        // Assign all permissions to Super Admin if role exists
        $superAdmin = Role::where('name', 'Super Admin')->first();
        if ($superAdmin) {
            $superAdmin->syncPermissions($allPermissions);
        }

        // Assign List, Create, Read, Update permissions to Security Admin if role exists
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        if ($securityAdmin) {
            $securityAdminPermissions = [];

            // Add CRUD permissions (excluding Delete)
            foreach ($entities as $entity) {
                foreach (['List', 'Create', 'Read', 'Update'] as $action) {
                    $permission = Permission::where('name', "{$action} {$entity}")->first();
                    if ($permission) {
                        $securityAdminPermissions[] = $permission->id;
                    }
                }
            }

            // Add additional permissions for Security Admin
            $additionalSecurityAdminPerms = ['Manage Preferences', 'View Bundles'];
            foreach ($additionalSecurityAdminPerms as $permName) {
                $permission = Permission::where('name', $permName)->first();
                if ($permission) {
                    $securityAdminPermissions[] = $permission->id;
                }
            }

            $securityAdmin->syncPermissions($securityAdminPermissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // This migration is for ensuring permissions exist
        // We don't reverse it as it could break existing functionality
        // If needed, permissions can be managed through the admin interface
    }
};
