<?php

namespace Tests\Feature\Mcp;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Mcp\EntityConfig;
use App\Mcp\Tools\ManagePolicyTool;
use App\Mcp\Tools\ManageStandardTool;
use App\Mcp\Tools\ManageVendorTool;
use App\Models\Control;
use App\Models\Policy;
use App\Models\Standard;
use App\Models\User;
use App\Models\Vendor;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Mcp\Request;
use Tests\TestCase;

class McpToolsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected User $regularUser;

    protected User $noPermissionsUser;

    protected function setUp(): void
    {
        parent::setUp();
        EntityConfig::clearCache();

        // Seed permissions and roles
        $this->seed(RolePermissionSeeder::class);

        // Create users with different permission levels
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('Super Admin');

        $this->regularUser = User::factory()->create();
        $this->regularUser->assignRole('Regular User');

        $this->noPermissionsUser = User::factory()->create();
        $this->noPermissionsUser->assignRole('None');
    }

    /**
     * Helper to get JSON response from a tool with an authenticated user.
     */
    protected function getToolResponse(object $tool, array $arguments, ?User $user = null): array
    {
        // Default to admin user for backward compatibility
        $user = $user ?? $this->adminUser;

        // Authenticate the user so $request->user() returns the user
        $this->actingAs($user);

        $request = new Request($arguments);
        $response = $tool->handle($request);

        return json_decode((string) $response->content(), true);
    }

    // ========================================
    // Tool List Action Tests
    // ========================================

    /**
     * Test ManageVendorTool list action lists vendors.
     */
    public function test_manage_vendor_list_action_lists_vendors(): void
    {
        Vendor::factory()->count(3)->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, ['action' => 'list']);

        $this->assertTrue($result['success']);
        $this->assertEquals('list', $result['action']);
        $this->assertEquals('vendor', $result['type']);
        $this->assertArrayHasKey('pagination', $result);
        $this->assertArrayHasKey('vendors', $result);
        $this->assertCount(3, $result['vendors']);
    }

    /**
     * Test ManageVendorTool list action includes relation data.
     */
    public function test_manage_vendor_list_action_includes_relation_data(): void
    {
        $user = User::factory()->create(['name' => 'Test Manager']);
        Vendor::factory()->create(['name' => 'Test Vendor', 'vendor_manager_id' => $user->id]);

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, ['action' => 'list']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('vendor_manager', $result['vendors'][0]);
        $this->assertEquals('Test Manager', $result['vendors'][0]['vendor_manager']['name']);
    }

    /**
     * Test ManageStandardTool list action includes counts.
     */
    public function test_manage_standard_list_action_includes_counts(): void
    {
        $standard = Standard::factory()->create();
        Control::factory()->count(5)->create(['standard_id' => $standard->id]);

        $tool = new ManageStandardTool;
        $result = $this->getToolResponse($tool, ['action' => 'list']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('controls_count', $result['standards'][0]);
        $this->assertEquals(5, $result['standards'][0]['controls_count']);
    }

    /**
     * Test ManageVendorTool list action includes URL.
     */
    public function test_manage_vendor_list_action_includes_url(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, ['action' => 'list']);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result['vendors'][0]);
        $this->assertStringContainsString('/app/vendors/', $result['vendors'][0]['url']);
    }

    // ========================================
    // Tool Get Action Tests
    // ========================================

    /**
     * Test ManageVendorTool get action retrieves entity by ID.
     */
    public function test_manage_vendor_get_action_retrieves_by_id(): void
    {
        $vendor = Vendor::factory()->create([
            'name' => 'Test Vendor',
            'description' => 'A test vendor',
        ]);

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $vendor->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('get', $result['action']);
        $this->assertEquals('vendor', $result['type']);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals('Test Vendor', $result['vendor']['name']);
    }

    /**
     * Test ManageStandardTool get action includes relations.
     */
    public function test_manage_standard_get_action_includes_relations(): void
    {
        $standard = Standard::factory()->create();
        Control::factory()->count(3)->create(['standard_id' => $standard->id]);

        $tool = new ManageStandardTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $standard->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('controls', $result['standard']);
        $this->assertCount(3, $result['standard']['controls']);
    }

    /**
     * Test ManageVendorTool get action includes URL.
     */
    public function test_manage_vendor_get_action_includes_url(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $vendor->id,
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('url', $result['vendor']);
        $this->assertStringContainsString("/app/vendors/{$vendor->id}", $result['vendor']['url']);
    }

    /**
     * Test ManageVendorTool get action returns error for not found.
     */
    public function test_manage_vendor_get_action_returns_error_for_not_found(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => 99999,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    // ========================================
    // Individual Manage*Tool Tests - Create Action
    // ========================================

    /**
     * Test ManageVendorTool create creates vendor.
     */
    public function test_manage_vendor_create_creates_vendor(): void
    {
        $user = User::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'Acme Corporation',
                'description' => 'A vendor for testing',
                'vendor_manager_id' => $user->id,
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('created', $result['action']);
        $this->assertStringContainsString('created successfully', $result['message']);
        $this->assertArrayHasKey('vendor', $result);
        $this->assertEquals('Acme Corporation', $result['vendor']['name']);

        $this->assertDatabaseHas('vendors', [
            'name' => 'Acme Corporation',
        ]);
    }

    /**
     * Test ManageVendorTool create validates required fields.
     */
    public function test_manage_vendor_create_validates_required_fields(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'description' => 'Test description',
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('Validation failed', $result['error']);
        $this->assertArrayHasKey('validation_errors', $result);
    }

    /**
     * Test ManagePolicyTool create prevents duplicate codes.
     */
    public function test_manage_policy_create_prevents_duplicate_codes(): void
    {
        Policy::create(['name' => 'First Policy', 'code' => 'POL-001']);

        $tool = new ManagePolicyTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'New Policy',
                'code' => 'POL-001',
            ],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    /**
     * Test ManagePolicyTool create auto-generates policy code.
     */
    public function test_manage_policy_create_auto_generates_policy_code(): void
    {
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
        ]);

        $tool = new ManagePolicyTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'Test Policy',
            ],
        ]);

        $this->assertTrue($result['success']);
        $this->assertStringStartsWith('POL-', $result['policy']['code']);
    }

    /**
     * Test ManagePolicyTool create sequential policy codes.
     */
    public function test_manage_policy_create_sequential_policy_codes(): void
    {
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
        ]);

        Policy::create(['name' => 'First', 'code' => 'POL-001']);

        $tool = new ManagePolicyTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => ['name' => 'Second Policy'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('POL-002', $result['policy']['code']);
    }

    // ========================================
    // Individual Manage*Tool Tests - Update Action
    // ========================================

    /**
     * Test ManageVendorTool update returns error for nonexistent entity.
     */
    public function test_manage_vendor_update_returns_error_for_nonexistent(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => 99999,
            'data' => ['name' => 'Updated'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test ManageVendorTool update updates entity.
     */
    public function test_manage_vendor_update_updates_entity(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Original Name']);

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $vendor->id,
            'data' => ['name' => 'Updated Name'],
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('updated', $result['action']);
        $this->assertStringContainsString('updated successfully', $result['message']);
        $this->assertContains('name', $result['updated_fields']);

        $this->assertDatabaseHas('vendors', [
            'id' => $vendor->id,
            'name' => 'Updated Name',
        ]);
    }

    /**
     * Test ManagePolicyTool update prevents duplicate codes.
     */
    public function test_manage_policy_update_prevents_duplicate_codes(): void
    {
        Policy::create(['name' => 'First', 'code' => 'POL-001']);
        $policy = Policy::create(['name' => 'Second', 'code' => 'POL-002']);

        $tool = new ManagePolicyTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $policy->id,
            'data' => ['code' => 'POL-001'],
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('already exists', $result['error']);
    }

    /**
     * Test ManageVendorTool update only updates allowed fields.
     */
    public function test_manage_vendor_update_only_updates_allowed_fields(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Original']);

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $vendor->id,
            'data' => [
                'name' => 'Updated',
                'id' => 99999,
            ],
        ]);

        $this->assertTrue($result['success']);
        $vendor->refresh();
        $this->assertEquals('Updated', $vendor->name);
        $this->assertNotEquals(99999, $vendor->id);
    }

    // ========================================
    // Individual Manage*Tool Tests - Delete Action
    // ========================================

    /**
     * Test ManageVendorTool delete returns error without confirmation.
     */
    public function test_manage_vendor_delete_returns_error_without_confirmation(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendor->id,
            'confirm' => false,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not confirmed', $result['error']);
    }

    /**
     * Test ManageVendorTool delete returns error for nonexistent entity.
     */
    public function test_manage_vendor_delete_returns_error_for_nonexistent(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => 99999,
            'confirm' => true,
        ]);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('not found', $result['error']);
    }

    /**
     * Test ManageVendorTool delete deletes entity.
     */
    public function test_manage_vendor_delete_deletes_entity(): void
    {
        $vendor = Vendor::factory()->create(['name' => 'Delete Me']);

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendor->id,
            'confirm' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertEquals('deleted', $result['action']);
        $this->assertStringContainsString('deleted', $result['message']);
        $this->assertStringContainsString('Delete Me', $result['message']);
    }

    /**
     * Test ManageVendorTool delete soft deletes when model supports it.
     */
    public function test_manage_vendor_delete_soft_deletes(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendor->id,
            'confirm' => true,
        ]);

        $this->assertTrue($result['success']);
        $this->assertTrue($result['soft_deleted']);
        $this->assertTrue($result['restorable']);

        $this->assertSoftDeleted('vendors', ['id' => $vendor->id]);
    }

    // ========================================
    // Authorization Tests
    // ========================================

    /**
     * Test user without permissions cannot list entities.
     */
    public function test_user_without_permissions_cannot_list_entities(): void
    {
        Vendor::factory()->count(3)->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, ['action' => 'list'], $this->noPermissionsUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('List Vendors', $result['required_permission']);
    }

    /**
     * Test user without permissions cannot get entity.
     */
    public function test_user_without_permissions_cannot_get_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $vendor->id,
        ], $this->noPermissionsUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('Read Vendors', $result['required_permission']);
    }

    /**
     * Test user without permissions cannot create entity.
     */
    public function test_user_without_permissions_cannot_create_entity(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => ['name' => 'Test Vendor'],
        ], $this->noPermissionsUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('Create Vendors', $result['required_permission']);
    }

    /**
     * Test user without permissions cannot update entity.
     */
    public function test_user_without_permissions_cannot_update_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $vendor->id,
            'data' => ['name' => 'Updated'],
        ], $this->noPermissionsUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('Update Vendors', $result['required_permission']);
    }

    /**
     * Test user without permissions cannot delete entity.
     */
    public function test_user_without_permissions_cannot_delete_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendor->id,
            'confirm' => true,
        ], $this->noPermissionsUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('Delete Vendors', $result['required_permission']);
    }

    /**
     * Test regular user (read-only) can list entities.
     */
    public function test_regular_user_can_list_entities(): void
    {
        Vendor::factory()->count(3)->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, ['action' => 'list'], $this->regularUser);

        $this->assertTrue($result['success']);
        $this->assertCount(3, $result['vendors']);
    }

    /**
     * Test regular user (read-only) can get entity.
     */
    public function test_regular_user_can_get_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $vendor->id,
        ], $this->regularUser);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('vendor', $result);
    }

    /**
     * Test regular user (read-only) cannot create entity.
     */
    public function test_regular_user_cannot_create_entity(): void
    {
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => ['name' => 'Test Vendor'],
        ], $this->regularUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
        $this->assertEquals('Create Vendors', $result['required_permission']);
    }

    /**
     * Test regular user (read-only) cannot update entity.
     */
    public function test_regular_user_cannot_update_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $vendor->id,
            'data' => ['name' => 'Updated'],
        ], $this->regularUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
    }

    /**
     * Test regular user (read-only) cannot delete entity.
     */
    public function test_regular_user_cannot_delete_entity(): void
    {
        $vendor = Vendor::factory()->create();

        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendor->id,
            'confirm' => true,
        ], $this->regularUser);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('do not have permission', $result['error']);
    }

    /**
     * Test super admin can perform all actions.
     */
    public function test_super_admin_can_perform_all_actions(): void
    {
        // Create
        $tool = new ManageVendorTool;
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'Admin Created Vendor',
                'vendor_manager_id' => $this->adminUser->id,
            ],
        ], $this->adminUser);

        $this->assertTrue($result['success']);
        $this->assertEquals('created', $result['action']);

        $vendorId = $result['vendor']['id'];

        // Read
        $result = $this->getToolResponse($tool, [
            'action' => 'get',
            'id' => $vendorId,
        ], $this->adminUser);

        $this->assertTrue($result['success']);

        // Update
        $result = $this->getToolResponse($tool, [
            'action' => 'update',
            'id' => $vendorId,
            'data' => ['name' => 'Updated by Admin'],
        ], $this->adminUser);

        $this->assertTrue($result['success']);

        // Delete
        $result = $this->getToolResponse($tool, [
            'action' => 'delete',
            'id' => $vendorId,
            'confirm' => true,
        ], $this->adminUser);

        $this->assertTrue($result['success']);
    }

    /**
     * Test authorization works for different entity types (Policy).
     */
    public function test_authorization_works_for_policy_entity(): void
    {
        Taxonomy::create([
            'type' => 'policy-status',
            'name' => 'Draft',
            'slug' => 'draft',
        ]);

        $tool = new ManagePolicyTool;

        // Regular user cannot create
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => ['name' => 'Test Policy'],
        ], $this->regularUser);

        $this->assertFalse($result['success']);
        $this->assertEquals('Create Policies', $result['required_permission']);

        // Admin can create
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => ['name' => 'Admin Policy'],
        ], $this->adminUser);

        $this->assertTrue($result['success']);
    }

    /**
     * Test authorization works for Standard entity.
     */
    public function test_authorization_works_for_standard_entity(): void
    {
        $tool = new ManageStandardTool;

        // Regular user cannot create
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'Test Standard',
                'code' => 'TST-001',
                'authority' => 'Test',
            ],
        ], $this->regularUser);

        $this->assertFalse($result['success']);
        $this->assertEquals('Create Standards', $result['required_permission']);

        // Admin can create
        $result = $this->getToolResponse($tool, [
            'action' => 'create',
            'data' => [
                'name' => 'Admin Standard',
                'code' => 'ADM-001',
                'authority' => 'Admin',
                'description' => 'A test standard created by admin',
            ],
        ], $this->adminUser);

        $this->assertTrue($result['success']);
    }
}
