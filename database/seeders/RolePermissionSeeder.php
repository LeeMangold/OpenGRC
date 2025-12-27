<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // -----------------------------------------------------------------------------------------
        // Create Roles
        $none = Role::firstOrCreate(['name' => 'None', 'guard_name' => 'web']);
        $regular = Role::firstOrCreate(
            ['name' => 'Regular User', 'guard_name' => 'web'],
            ['description' => 'Read-Only-Responder User']
        );
        $superAdmin = Role::firstOrCreate(
            ['name' => 'Super Admin', 'guard_name' => 'web'],
            ['description' => 'Super User with all permissions']
        );
        $securityAdmin = Role::firstOrCreate(
            ['name' => 'Security Admin', 'guard_name' => 'web'],
            ['description' => 'Able to Edit all data and run Audits but not manage users']
        );
        $internalAuditor = Role::firstOrCreate(
            ['name' => 'Internal Auditor', 'guard_name' => 'web'],
            ['description' => 'Able to run Audits but not edit other foundational data']
        );

        // -----------------------------------------------------------------------------------------
        // Create Resource Permissions
        $entities = ['Standards', 'Controls', 'Implementations', 'Audits', 'AuditItems', 'Programs', 'Vendors', 'Applications', 'Risks', 'Assets', 'Policies', 'DataRequests', 'DataRequestResponses', 'FileAttachments'];
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$entity}", 'category' => "{$entity}"]);
            }
        }

        // -----------------------------------------------------------------------------------------
        // Create Additional Permissions
        $additionalPermissions = [
            'Configure Authentication',
            'Manage Users',
            'View Audit Log',
            'Manage Preferences',
        ];

        foreach ($additionalPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'category' => 'other']);
        }

        // Bundle Permissions
        Permission::firstOrCreate(['name' => 'Manage Bundles', 'category' => 'Bundles']);
        Permission::firstOrCreate(['name' => 'View Bundles', 'category' => 'Bundles']);

        // Trust Center Permissions
        Permission::firstOrCreate(['name' => 'Manage Trust Center', 'category' => 'Trust Center']);
        Permission::firstOrCreate(['name' => 'Manage Trust Access', 'category' => 'Trust Center']);

        // Vendor Management Permissions
        Permission::firstOrCreate(['name' => 'Manage Vendor Management', 'category' => 'Vendors']);

        // -----------------------------------------------------------------------------------------
        // Assign Permissions to Super Admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign Resource Permissions to Regular Users
        foreach ($entities as $entity) {
            foreach (['List', 'Read'] as $action) {
                $regular->givePermissionTo("{$action} {$entity}");
            }
        }

        // Assign specific Permissions to Security Admin
        foreach ($entities as $entity) {
            foreach (['List', 'Create', 'Read', 'Update'] as $action) {
                $securityAdmin->givePermissionTo("{$action} {$entity}");
            }
        }
        $securityAdmin->givePermissionTo('Manage Preferences');
        $securityAdmin->givePermissionTo('View Bundles');
        $securityAdmin->givePermissionTo('Manage Trust Center');
        $securityAdmin->givePermissionTo('Manage Trust Access');
        $securityAdmin->givePermissionTo('Manage Vendor Management');

        // Assign specific Permissions to Internal Auditor
        $internalAuditor->givePermissionTo([
            'List Audits',
            'Read Audits',
            'List Standards',
            'Read Standards',
            'List Controls',
            'Read Controls',
            'List Implementations',
            'Read Implementations',
            'List Programs',
            'Read Programs',
            'List Audits',
            'Create Audits',
            'Read Audits',
        ]);

        // -----------------------------------------------------------------------------------------
        // Assign Super Admin role to first user (by ID) or admin user (by email from env)
        $adminEmail = env('ADMIN_EMAIL', 'admin@example.com');
        $adminUser = User::where('email', $adminEmail)->first();

        if ($adminUser) {
            // Assign Super Admin to the admin user from environment
            $adminUser->assignRole($superAdmin);
        } else {
            // Fallback: assign to first user if admin email user doesn't exist
            $firstUser = User::find(1);
            if ($firstUser) {
                $firstUser->assignRole($superAdmin);
            }
        }

    }
}
