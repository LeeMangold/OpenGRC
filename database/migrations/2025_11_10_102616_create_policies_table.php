<?php

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $tableExists = Schema::hasTable('policies');

        Schema::create('policies', function (Blueprint $table) {
            $table->id();

            // Core Fields
            $table->string('code')->unique()->index();
            $table->string('name');
            $table->longText('policy_scope')->nullable();
            $table->longText('purpose')->nullable();
            $table->longText('body')->nullable();
            $table->string('document_path')->nullable();

            // Taxonomy Relationships
            $table->foreignId('scope_id')->nullable()->constrained('taxonomies')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('taxonomies')->nullOnDelete();
            $table->foreignId('status_id')->nullable()->constrained('taxonomies')->nullOnDelete();

            // Owner and Dates
            $table->foreignId('owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('effective_date')->nullable();
            $table->date('retired_date')->nullable();

            // Revision History
            $table->json('revision_history')->nullable();

            // Audit Fields
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });

        // If table didn't exist before (fresh creation), seed the policy taxonomies and permissions
        if (! $tableExists) {
            $this->seedPolicyTaxonomies();
            $this->seedPolicyPermissions();
        }
    }

    /**
     * Seed policy-related taxonomies.
     */
    private function seedPolicyTaxonomies(): void
    {
        // Policy Status Taxonomy
        $policyStatusParent = Taxonomy::firstOrCreate(
            [
                'slug' => 'policy-status',
                'type' => 'policy',
            ],
            [
                'name' => 'Policy Status',
                'description' => 'Status values for policy documents',
                'parent_id' => null,
                'sort_order' => 1,
            ]
        );

        $policyStatuses = [
            ['name' => 'Draft', 'slug' => 'draft', 'description' => 'Policy is in draft state'],
            ['name' => 'In Review', 'slug' => 'in-review', 'description' => 'Policy is being reviewed'],
            ['name' => 'Awaiting Feedback', 'slug' => 'awaiting-feedback', 'description' => 'Policy is awaiting feedback'],
            ['name' => 'Pending Approval', 'slug' => 'pending-approval', 'description' => 'Policy is pending approval'],
            ['name' => 'Approved', 'slug' => 'approved', 'description' => 'Policy has been approved'],
            ['name' => 'Archived', 'slug' => 'archived', 'description' => 'Policy has been archived'],
            ['name' => 'Superseded', 'slug' => 'superseded', 'description' => 'Policy has been superseded by a newer version'],
            ['name' => 'Retired', 'slug' => 'retired', 'description' => 'Policy has been retired'],
        ];

        foreach ($policyStatuses as $index => $status) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => $status['slug'],
                    'type' => 'policy',
                    'parent_id' => $policyStatusParent->id,
                ],
                [
                    'name' => $status['name'],
                    'description' => $status['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }

        // Policy Scope Taxonomy
        $policyScopeParent = Taxonomy::firstOrCreate(
            [
                'slug' => 'policy-scope',
                'type' => 'policy',
            ],
            [
                'name' => 'Policy Scope',
                'description' => 'Scope values for policy documents',
                'parent_id' => null,
                'sort_order' => 2,
            ]
        );

        $policyScopes = [
            ['name' => 'Organization-wide', 'slug' => 'organization-wide', 'description' => 'Applies to entire organization'],
            ['name' => 'Department-specific', 'slug' => 'department-specific', 'description' => 'Applies to specific department'],
            ['name' => 'Project-specific', 'slug' => 'project-specific', 'description' => 'Applies to specific project'],
            ['name' => 'Regional', 'slug' => 'regional', 'description' => 'Applies to specific region'],
            ['name' => 'Global', 'slug' => 'global', 'description' => 'Applies globally across all entities'],
        ];

        foreach ($policyScopes as $index => $scope) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => $scope['slug'],
                    'type' => 'policy',
                    'parent_id' => $policyScopeParent->id,
                ],
                [
                    'name' => $scope['name'],
                    'description' => $scope['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Seed policy-related permissions and assign to roles.
     */
    private function seedPolicyPermissions(): void
    {
        // Create Policy permissions
        $actions = ['List', 'Create', 'Read', 'Update', 'Delete'];

        foreach ($actions as $action) {
            $permission = Permission::where('name', "{$action} Policies")->first();
            if (! $permission) {
                Permission::create([
                    'name' => "{$action} Policies",
                    'guard_name' => 'web',
                    'category' => 'Policies',
                ]);
            }
        }

        // Clear permission cache so newly created permissions are available
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Get existing roles
        $superAdmin = Role::where('name', 'Super Admin')->first();
        $regular = Role::where('name', 'Regular User')->first();
        $securityAdmin = Role::where('name', 'Security Admin')->first();

        // Assign permissions to Super Admin if they don't already have them
        if ($superAdmin) {
            $permissionsToAssign = ['List Policies', 'Create Policies', 'Read Policies', 'Update Policies', 'Delete Policies'];
            foreach ($permissionsToAssign as $permName) {
                if (! $superAdmin->hasPermissionTo($permName)) {
                    $superAdmin->givePermissionTo($permName);
                }
            }
        }

        // Assign permissions to Regular User if they don't already have them
        if ($regular) {
            $permissionsToAssign = ['List Policies', 'Read Policies'];
            foreach ($permissionsToAssign as $permName) {
                if (! $regular->hasPermissionTo($permName)) {
                    $regular->givePermissionTo($permName);
                }
            }
        }

        // Assign permissions to Security Admin if they don't already have them
        if ($securityAdmin) {
            $permissionsToAssign = ['List Policies', 'Create Policies', 'Read Policies', 'Update Policies'];
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
        Schema::dropIfExists('policies');
    }
};
