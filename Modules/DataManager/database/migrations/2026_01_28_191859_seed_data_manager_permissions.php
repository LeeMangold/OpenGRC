<?php

use Illuminate\Database\Migrations\Migration;
use Modules\DataManager\Database\Seeders\DataManagerPermissionSeeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seeder = new DataManagerPermissionSeeder;
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', 'Import Data')->delete();
        Permission::where('name', 'Export Data')->delete();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
