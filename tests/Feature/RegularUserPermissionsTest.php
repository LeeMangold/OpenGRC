<?php

namespace Tests\Feature;

use App\Models\Application;
use App\Models\Audit;
use App\Models\Control;
use App\Models\DataRequest;
use App\Models\Implementation;
use App\Models\Program;
use App\Models\Risk;
use App\Models\Standard;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RegularUserPermissionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Run seeders to set up roles and permissions
        $this->seed(\Database\Seeders\RolePermissionSeeder::class);

        // Create a Regular User
        $this->regularUser = User::factory()->create([
            'name' => 'Regular Test User',
            'email' => 'regular@test.com',
            'password' => bcrypt('password'),
        ]);

        // Assign Regular User role
        $regularRole = Role::where('name', 'Regular User')->first();
        $this->regularUser->assignRole($regularRole);
    }

    /** @test */
    public function regular_user_can_view_standards_list(): void
    {
        $standard = Standard::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/standards');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_standard_details(): void
    {
        $standard = Standard::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/standards/{$standard->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_standards(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/standards/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_edit_standards(): void
    {
        $standard = Standard::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/standards/{$standard->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_delete_standards(): void
    {
        $standard = Standard::factory()->create();

        // Note: Filament doesn't expose delete routes directly, so we test via policy
        $this->assertFalse($this->regularUser->can('Delete Standards'));
        $this->assertDatabaseHas('standards', ['id' => $standard->id]);
    }

    /** @test */
    public function regular_user_can_view_controls_list(): void
    {
        $control = Control::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/controls');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_control_details(): void
    {
        $control = Control::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/controls/{$control->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_controls(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/controls/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_implementations_list(): void
    {
        $implementation = Implementation::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/implementations');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_implementation_details(): void
    {
        $implementation = Implementation::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/implementations/{$implementation->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_implementations(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/implementations/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_edit_implementations(): void
    {
        $implementation = Implementation::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/implementations/{$implementation->id}/edit");

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_audits_list(): void
    {
        // Create a standard first for audit factory dependency
        $standard = Standard::factory()->create();
        $audit = Audit::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/audits');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_audit_details(): void
    {
        // Create a standard first for audit factory dependency
        $standard = Standard::factory()->create();
        $audit = Audit::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/audits/{$audit->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_audits(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/audits/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_delete_audits(): void
    {
        // Create a standard first for audit factory dependency
        $standard = Standard::factory()->create();
        $audit = Audit::factory()->create();

        // Test via policy instead of route
        $this->assertFalse($this->regularUser->can('Delete Audits'));
        $this->assertDatabaseHas('audits', ['id' => $audit->id]);
    }

    /** @test */
    public function regular_user_can_view_programs_list(): void
    {
        $program = Program::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/programs');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_program_details(): void
    {
        $program = Program::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/programs/{$program->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_programs(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/programs/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_vendors_list(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get('/app/vendors');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_vendor_details(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $this->regularUser->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get("/app/vendors/{$vendor->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_vendors(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/vendors/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_applications_list(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $this->regularUser->id,
        ]);

        $application = Application::create([
            'name' => 'Test Application',
            'owner_id' => $this->regularUser->id,
            'vendor_id' => $vendor->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get('/app/applications');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_application_in_list(): void
    {
        $vendor = Vendor::create([
            'name' => 'Test Vendor',
            'vendor_manager_id' => $this->regularUser->id,
        ]);

        $application = Application::create([
            'name' => 'Test Application',
            'owner_id' => $this->regularUser->id,
            'vendor_id' => $vendor->id,
        ]);

        // Application resource doesn't have a dedicated view page
        // Regular users can see applications in the list, which verifies Read permission
        $this->assertTrue($this->regularUser->can('Read Applications'));
        $this->assertDatabaseHas('applications', ['id' => $application->id]);
    }

    /** @test */
    public function regular_user_cannot_create_applications(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/applications/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_risks_list(): void
    {
        $risk = Risk::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get('/app/risks');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_risk_details(): void
    {
        $risk = Risk::factory()->create();

        $response = $this->actingAs($this->regularUser)
            ->get("/app/risks/{$risk->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_risks(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/risks/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_can_view_data_requests_list(): void
    {
        $standard = Standard::factory()->create();
        $audit = Audit::factory()->create();

        $dataRequest = DataRequest::create([
            'code' => 'DR-001',
            'created_by_id' => $this->regularUser->id,
            'assigned_to_id' => $this->regularUser->id,
            'audit_id' => $audit->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get('/app/data-requests');

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_can_view_data_request_details(): void
    {
        $standard = Standard::factory()->create();
        $audit = Audit::factory()->create();

        $dataRequest = DataRequest::create([
            'code' => 'DR-002',
            'created_by_id' => $this->regularUser->id,
            'assigned_to_id' => $this->regularUser->id,
            'audit_id' => $audit->id,
        ]);

        $response = $this->actingAs($this->regularUser)
            ->get("/app/data-requests/{$dataRequest->id}");

        $response->assertStatus(200);
    }

    /** @test */
    public function regular_user_cannot_create_data_requests(): void
    {
        $response = $this->actingAs($this->regularUser)
            ->get('/app/data-requests/create');

        $response->assertStatus(403);
    }

    /** @test */
    public function regular_user_cannot_manage_users(): void
    {
        $this->assertFalse($this->regularUser->can('Manage Users'));
    }

    /** @test */
    public function regular_user_cannot_configure_authentication(): void
    {
        $this->assertFalse($this->regularUser->can('Configure Authentication'));
    }

    /** @test */
    public function regular_user_cannot_view_audit_log(): void
    {
        $this->assertFalse($this->regularUser->can('View Audit Log'));
    }

    /** @test */
    public function regular_user_cannot_manage_preferences(): void
    {
        $this->assertFalse($this->regularUser->can('Manage Preferences'));
    }

    /** @test */
    public function regular_user_cannot_manage_bundles(): void
    {
        $this->assertFalse($this->regularUser->can('Manage Bundles'));
    }

    /** @test */
    public function regular_user_cannot_view_bundles(): void
    {
        $this->assertFalse($this->regularUser->can('View Bundles'));
    }

    /** @test */
    public function regular_user_has_list_and_read_permissions_only(): void
    {
        $entities = ['Standards', 'Controls', 'Implementations', 'Audits', 'AuditItems', 'Programs', 'Vendors', 'Applications', 'Risks', 'DataRequests', 'DataRequestResponses', 'FileAttachments'];

        foreach ($entities as $entity) {
            // Should have List and Read permissions
            $this->assertTrue(
                $this->regularUser->can("List {$entity}"),
                "Regular User should have 'List {$entity}' permission"
            );
            $this->assertTrue(
                $this->regularUser->can("Read {$entity}"),
                "Regular User should have 'Read {$entity}' permission"
            );

            // Should NOT have Create, Update, or Delete permissions
            $this->assertFalse(
                $this->regularUser->can("Create {$entity}"),
                "Regular User should NOT have 'Create {$entity}' permission"
            );
            $this->assertFalse(
                $this->regularUser->can("Update {$entity}"),
                "Regular User should NOT have 'Update {$entity}' permission"
            );
            $this->assertFalse(
                $this->regularUser->can("Delete {$entity}"),
                "Regular User should NOT have 'Delete {$entity}' permission"
            );
        }
    }
}
