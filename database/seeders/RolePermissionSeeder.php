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
        $none = Role::create(['name' => 'None']);
        $regular = Role::create(['name' => 'Regular User', 'description' => 'Read-Only-Responder User']);
        $superAdmin = Role::create(['name' => 'Super Admin', 'description' => 'Super User with all permissions']);
        $securityAdmin = Role::create(['name' => 'Security Admin', 'description' => 'Able to Edit all data and run Audits but not manage users']);
        $internalAuditor = Role::create(['name' => 'Internal Auditor', 'description' => 'Able to run Audits but not edit other foundational data']);

        // -----------------------------------------------------------------------------------------
        // Create Resource Permissions
        $entities = ['Standards', 'Controls', 'Implementations', 'Audits', 'Programs'];
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
        // Assign users with ID 1 Super Admin
        User::find(1)->assignRole($superAdmin);

    }
}
