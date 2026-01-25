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
        // Create Asset permissions
        $assetActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($assetActions as $action) {
            $permission = Permission::where('name', "{$action} Assets")->first();
            if (! $permission) {
                Permission::create([
                    'name' => "{$action} Assets",
                    'guard_name' => 'web',
                    'category' => 'Assets',
                ]);
            }
        }

        // Clear permission cache so newly created permissions are available
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get the roles
        $regular = Role::where('name', 'Regular User')->first();
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $internalAuditor = Role::where('name', 'Internal Auditor')->first();

        // Get the permissions
        $assetPermissions = Permission::where('name', 'LIKE', '% Assets')->get();

        // Assign permissions to Super Admin (all permissions)
        if ($superAdmin && $assetPermissions->isNotEmpty()) {
            foreach ($assetPermissions as $permission) {
                if (! $superAdmin->hasPermissionTo($permission)) {
                    $superAdmin->givePermissionTo($permission);
                }
            }
        }

        // Assign permissions to Regular User (List and Read only)
        if ($regular) {
            $permissionsToAssign = ['List Assets', 'Read Assets'];
            foreach ($permissionsToAssign as $permName) {
                if (! $regular->hasPermissionTo($permName)) {
                    $regular->givePermissionTo($permName);
                }
            }
        }

        // Assign permissions to Security Admin (List, Create, Read, Update - no Delete)
        if ($securityAdmin) {
            $permissionsToAssign = ['List Assets', 'Create Assets', 'Read Assets', 'Update Assets'];
            foreach ($permissionsToAssign as $permName) {
                if (! $securityAdmin->hasPermissionTo($permName)) {
                    $securityAdmin->givePermissionTo($permName);
                }
            }
        }

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove Asset permissions
        $assetActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($assetActions as $action) {
            Permission::where('name', "{$action} Assets")->delete();
        }
    }
};
