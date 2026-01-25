<?php

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
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
        // Create Taxonomy permissions
        $taxonomyActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($taxonomyActions as $action) {
            Permission::firstOrCreate([
                'name' => "{$action} Taxonomy",
                'category' => 'Taxonomy',
            ]);
        }

        // Get the roles
        $regular = Role::where('name', 'Regular User')->first();
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();
        $internalAuditor = Role::where('name', 'Internal Auditor')->first();

        // Get the permissions
        $taxonomyPermissions = Permission::where('category', 'Taxonomy')->get();

        // Assign permissions to Super Admin (all permissions)
        if ($superAdmin) {
            $superAdmin->givePermissionTo($taxonomyPermissions);
        }

        // Assign permissions to Regular User (List and Read only)
        if ($regular) {
            $regular->givePermissionTo([
                'List Taxonomy',
                'Read Taxonomy',
            ]);
        }

        // Assign permissions to Security Admin (List, Create, Read, Update - no Delete)
        if ($securityAdmin) {
            $securityAdmin->givePermissionTo([
                'List Taxonomy',
                'Create Taxonomy',
                'Read Taxonomy',
                'Update Taxonomy',
            ]);
        }

        // Assign permissions to Internal Auditor (List and Read only)
        if ($internalAuditor) {
            $internalAuditor->givePermissionTo([
                'List Taxonomy',
                'Read Taxonomy',
            ]);
        }

        // Create Taxonomy terms
        $this->createTaxonomyTerms();
    }

    /**
     * Create taxonomy terms
     */
    private function createTaxonomyTerms(): void
    {
        // Create Scope taxonomy with Global term
        $scopeTaxonomy = Taxonomy::firstOrCreate([
            'name' => 'Scope',
            'slug' => 'scope',
            'type' => 'scope',
        ], [
            'description' => 'Organizational scope categories',
        ]);

        Taxonomy::firstOrCreate([
            'name' => 'Global',
            'slug' => 'global',
            'type' => 'scope',
            'parent_id' => $scopeTaxonomy->id,
        ], [
            'description' => 'Global scope',
        ]);

        // Create Department taxonomy with Security term
        $departmentTaxonomy = Taxonomy::firstOrCreate([
            'name' => 'Department',
            'slug' => 'department',
            'type' => 'department',
        ], [
            'description' => 'Organizational departments',
        ]);

        Taxonomy::firstOrCreate([
            'name' => 'Security',
            'slug' => 'security',
            'type' => 'department',
            'parent_id' => $departmentTaxonomy->id,
        ], [
            'description' => 'Security department',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove taxonomy terms
        Taxonomy::whereIn('type', ['scope', 'department'])->delete();

        // Remove Taxonomy permissions
        $taxonomyActions = ['List', 'Create', 'Read', 'Update', 'Delete'];
        foreach ($taxonomyActions as $action) {
            Permission::where('name', "{$action} Taxonomy")->delete();
        }
    }
};
