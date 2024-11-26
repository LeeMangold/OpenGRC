<?php

namespace Database\Seeders;

//use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
//use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Create Roles
        $none = Role::create(['name' => 'None']);
        $regular = Role::create(['name' => 'Regular User']);
        $superAdmin = Role::create(['name' => 'Super Admin']);
        $securityAdmin = Role::create(['name' => 'Security Admin']);

        // Define Permissions
        $entities = ['Standards', 'Controls', 'Implementations', 'Audits'];
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($entities as $entity) {
            foreach ($actions as $action) {
                Permission::firstOrCreate(['name' => "{$action} {$entity}", 'category' => "{$entity}"]);
            }
        }

        // Additional Permissions
        $additionalPermissions = [
            'Configure Authentication',
            'Manage Users',
            'View Audit Log',
            'Manage Preferences',
        ];

        foreach ($additionalPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'category' => 'other']);
        }

        //        dd(Permission::all());

        // Assign Permissions to Super Admin
        $superAdmin->givePermissionTo(Permission::all());

        // Assign specific Permissions to Regular Users
        foreach ($entities as $entity) {
            foreach (['List', 'Create', 'Read'] as $action) {
                $regular->givePermissionTo("{$action} {$entity}");
            }
        }

        // Assign specific Permissions to Security Admin
        foreach ($entities as $entity) {
            foreach (['List', 'Create', 'Read'] as $action) {
                $securityAdmin->givePermissionTo("{$action} {$entity}");
            }
        }
        $securityAdmin->givePermissionTo('Manage Preferences');

        // Assign users with ID 1, 2, 3, and 4 to the Super Admin role
        $userIds = [1, 2, 3, 4, 5];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->assignRole($superAdmin);
            }
        }

        // Assign users with ID 6, 7, 8, 9, 10 Regular User
        $userIds = [6, 7, 8, 9, 10];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $user->assignRole($regular);
            }
        }
    }
}
