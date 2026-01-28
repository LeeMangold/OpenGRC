<?php

use Database\Seeders\ChecklistPermissionSeeder;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $seeder = new ChecklistPermissionSeeder;
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        Permission::where('name', 'like', '% Checklists')->delete();
        Permission::where('name', 'like', '% ChecklistTemplates')->delete();

        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
