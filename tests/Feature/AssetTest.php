<?php

namespace Tests\Feature;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Asset;
use App\Models\Implementation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AssetTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed asset taxonomies for testing
        $this->seed(\Database\Seeders\AssetTaxonomySeeder::class);
    }

    /**
     * Helper method to get a taxonomy term by parent slug and term name.
     */
    private function getTaxonomyTerm(string $parentSlug, string $termName): ?Taxonomy
    {
        $parent = Taxonomy::where('slug', $parentSlug)->first();

        return Taxonomy::where('parent_id', $parent->id)->where('name', $termName)->first();
    }

    /**
     * Test asset can be created with required fields.
     */
    public function test_asset_can_be_created(): void
    {
        $assetType = $this->getTaxonomyTerm('asset-type', 'Laptop');
        $assetStatus = $this->getTaxonomyTerm('asset-status', 'In Use');

        $asset = Asset::create([
            'asset_tag' => 'LAP-001',
            'name' => 'Dell Latitude 5520',
            'asset_type_id' => $assetType->id,
            'status_id' => $assetStatus->id,
            'serial_number' => 'DL5520-ABC123',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('assets', [
            'asset_tag' => 'LAP-001',
            'name' => 'Dell Latitude 5520',
            'serial_number' => 'DL5520-ABC123',
            'is_active' => true,
        ]);

        $this->assertEquals('LAP-001', $asset->asset_tag);
        $this->assertEquals('Dell Latitude 5520', $asset->name);
    }

    /**
     * Test asset belongs to asset type taxonomy.
     */
    public function test_asset_belongs_to_asset_type_taxonomy(): void
    {
        $assetType = $this->getTaxonomyTerm('asset-type', 'Server');

        $asset = Asset::create([
            'asset_tag' => 'SRV-001',
            'name' => 'Dell PowerEdge R750',
            'asset_type_id' => $assetType->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Taxonomy::class, $asset->assetType);
        $this->assertEquals('Server', $asset->assetType->name);
        $this->assertEquals($assetType->id, $asset->assetType->id);
    }

    /**
     * Test asset belongs to status taxonomy.
     */
    public function test_asset_belongs_to_status_taxonomy(): void
    {
        $assetStatus = $this->getTaxonomyTerm('asset-status', 'In Repair');

        $asset = Asset::create([
            'asset_tag' => 'LAP-002',
            'name' => 'HP ProBook',
            'status_id' => $assetStatus->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Taxonomy::class, $asset->status);
        $this->assertEquals('In Repair', $asset->status->name);
        $this->assertEquals($assetStatus->id, $asset->status->id);
    }

    /**
     * Test asset belongs to condition taxonomy.
     */
    public function test_asset_belongs_to_condition_taxonomy(): void
    {
        $condition = $this->getTaxonomyTerm('asset-condition', 'Excellent');

        $asset = Asset::create([
            'asset_tag' => 'LAP-003',
            'name' => 'MacBook Pro',
            'condition_id' => $condition->id,
            'is_active' => true,
        ]);

        $this->assertInstanceOf(Taxonomy::class, $asset->condition);
        $this->assertEquals('Excellent', $asset->condition->name);
        $this->assertEquals($condition->id, $asset->condition->id);
    }

    /**
     * Test asset can be assigned to a user.
     */
    public function test_asset_can_be_assigned_to_user(): void
    {
        $user = User::factory()->create(['name' => 'John Doe']);
        $assetType = $this->getTaxonomyTerm('asset-type', 'Laptop');

        $asset = Asset::create([
            'asset_tag' => 'LAP-004',
            'name' => 'Lenovo ThinkPad',
            'asset_type_id' => $assetType->id,
            'assigned_to_user_id' => $user->id,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->assertInstanceOf(User::class, $asset->assignedToUser);
        $this->assertEquals('John Doe', $asset->assignedToUser->name);
        $this->assertEquals($user->id, $asset->assignedToUser->id);
        $this->assertNotNull($asset->assigned_at);
    }

    /**
     * Test asset has many to many relationship with implementations.
     */
    public function test_asset_has_many_implementations(): void
    {
        $user = User::factory()->create();
        $assetType = $this->getTaxonomyTerm('asset-type', 'Server');

        $asset = Asset::create([
            'asset_tag' => 'SRV-002',
            'name' => 'Application Server',
            'asset_type_id' => $assetType->id,
            'is_active' => true,
        ]);

        // Create implementations
        $impl1 = Implementation::factory()->create(['title' => 'Firewall Configuration']);
        $impl2 = Implementation::factory()->create(['title' => 'Encryption Implementation']);
        $impl3 = Implementation::factory()->create(['title' => 'Backup Policy']);

        // Attach implementations to asset
        $asset->implementations()->attach([$impl1->id, $impl2->id, $impl3->id]);

        $this->assertCount(3, $asset->implementations);
        $this->assertTrue($asset->implementations->contains($impl1));
        $this->assertTrue($asset->implementations->contains($impl2));
        $this->assertTrue($asset->implementations->contains($impl3));
    }

    /**
     * Test asset soft delete functionality.
     */
    public function test_asset_can_be_soft_deleted(): void
    {
        $asset = Asset::create([
            'asset_tag' => 'DEL-001',
            'name' => 'Old Equipment',
            'is_active' => false,
        ]);

        $assetId = $asset->id;
        $asset->delete();

        $this->assertSoftDeleted('assets', ['id' => $assetId]);
        $this->assertNull(Asset::find($assetId));
        $this->assertNotNull(Asset::withTrashed()->find($assetId));
    }

    /**
     * Test asset scope for active assets.
     */
    public function test_asset_active_scope(): void
    {
        Asset::create([
            'asset_tag' => 'ACT-001',
            'name' => 'Active Asset',
            'is_active' => true,
        ]);

        Asset::create([
            'asset_tag' => 'INACT-001',
            'name' => 'Inactive Asset',
            'is_active' => false,
        ]);

        $activeAssets = Asset::active()->get();

        $this->assertCount(1, $activeAssets);
        $this->assertEquals('ACT-001', $activeAssets->first()->asset_tag);
    }

    /**
     * Test asset scope for assigned assets.
     */
    public function test_asset_assigned_scope(): void
    {
        $user = User::factory()->create();

        Asset::create([
            'asset_tag' => 'ASS-001',
            'name' => 'Assigned Asset',
            'assigned_to_user_id' => $user->id,
            'is_active' => true,
        ]);

        Asset::create([
            'asset_tag' => 'UNASS-001',
            'name' => 'Unassigned Asset',
            'is_active' => true,
        ]);

        $assignedAssets = Asset::assigned()->get();

        $this->assertCount(1, $assignedAssets);
        $this->assertEquals('ASS-001', $assignedAssets->first()->asset_tag);
    }

    /**
     * Test asset scope by asset type.
     */
    public function test_asset_by_asset_type_scope(): void
    {
        $laptopType = $this->getTaxonomyTerm('asset-type', 'Laptop');
        $serverType = $this->getTaxonomyTerm('asset-type', 'Server');

        Asset::create([
            'asset_tag' => 'LAP-010',
            'name' => 'Test Laptop',
            'asset_type_id' => $laptopType->id,
            'is_active' => true,
        ]);

        Asset::create([
            'asset_tag' => 'SRV-010',
            'name' => 'Test Server',
            'asset_type_id' => $serverType->id,
            'is_active' => true,
        ]);

        $laptops = Asset::byAssetType('Laptop')->get();

        $this->assertCount(1, $laptops);
        $this->assertEquals('LAP-010', $laptops->first()->asset_tag);
    }

    /**
     * Test asset scope by status.
     */
    public function test_asset_by_status_scope(): void
    {
        $inUseStatus = $this->getTaxonomyTerm('asset-status', 'In Use');
        $retiredStatus = $this->getTaxonomyTerm('asset-status', 'Retired');

        Asset::create([
            'asset_tag' => 'USE-001',
            'name' => 'In Use Asset',
            'status_id' => $inUseStatus->id,
            'is_active' => true,
        ]);

        Asset::create([
            'asset_tag' => 'RET-001',
            'name' => 'Retired Asset',
            'status_id' => $retiredStatus->id,
            'is_active' => false,
        ]);

        $inUseAssets = Asset::byStatus('In Use')->get();

        $this->assertCount(1, $inUseAssets);
        $this->assertEquals('USE-001', $inUseAssets->first()->asset_tag);
    }

    /**
     * Test asset hardware specifications are stored correctly.
     */
    public function test_asset_hardware_specifications_stored_correctly(): void
    {
        $laptopType = $this->getTaxonomyTerm('asset-type', 'Laptop');

        $asset = Asset::create([
            'asset_tag' => 'LAP-100',
            'name' => 'High-End Laptop',
            'asset_type_id' => $laptopType->id,
            'manufacturer' => 'Dell',
            'model' => 'XPS 15',
            'processor' => 'Intel Core i9-13900H',
            'ram_gb' => 32,
            'storage_type' => 'NVMe SSD',
            'storage_capacity_gb' => 1024,
            'screen_size' => 15.6,
            'operating_system' => 'Windows 11 Pro',
            'os_version' => '23H2',
            'is_active' => true,
        ]);

        $this->assertEquals('Dell', $asset->manufacturer);
        $this->assertEquals('XPS 15', $asset->model);
        $this->assertEquals('Intel Core i9-13900H', $asset->processor);
        $this->assertEquals(32, $asset->ram_gb);
        $this->assertEquals('NVMe SSD', $asset->storage_type);
        $this->assertEquals(1024, $asset->storage_capacity_gb);
        $this->assertEquals(15.6, $asset->screen_size);
    }

    /**
     * Test asset financial information is stored correctly.
     */
    public function test_asset_financial_information_stored_correctly(): void
    {
        $asset = Asset::create([
            'asset_tag' => 'FIN-001',
            'name' => 'Financial Test Asset',
            'purchase_date' => '2023-01-15',
            'purchase_price' => 2499.99,
            'current_value' => 1899.99,
            'warranty_start_date' => '2023-01-15',
            'warranty_end_date' => '2026-01-15',
            'is_active' => true,
        ]);

        $this->assertEquals('2023-01-15', $asset->purchase_date->format('Y-m-d'));
        $this->assertEquals(2499.99, $asset->purchase_price);
        $this->assertEquals(1899.99, $asset->current_value);
        $this->assertEquals('2026-01-15', $asset->warranty_end_date->format('Y-m-d'));
    }

    /**
     * Test multiple assets can be linked to same implementation.
     */
    public function test_multiple_assets_can_be_linked_to_same_implementation(): void
    {
        $serverType = $this->getTaxonomyTerm('asset-type', 'Server');

        $server1 = Asset::create([
            'asset_tag' => 'SRV-101',
            'name' => 'Web Server 1',
            'asset_type_id' => $serverType->id,
            'is_active' => true,
        ]);

        $server2 = Asset::create([
            'asset_tag' => 'SRV-102',
            'name' => 'Web Server 2',
            'asset_type_id' => $serverType->id,
            'is_active' => true,
        ]);

        $implementation = Implementation::factory()->create(['title' => 'SSL/TLS Configuration']);

        $server1->implementations()->attach($implementation->id);
        $server2->implementations()->attach($implementation->id);

        $this->assertTrue($server1->implementations->contains($implementation));
        $this->assertTrue($server2->implementations->contains($implementation));
        $this->assertCount(2, $implementation->assets);
    }

    /**
     * Test asset with software licensing fields.
     */
    public function test_asset_with_software_licensing_fields(): void
    {
        $softwareType = $this->getTaxonomyTerm('asset-type', 'Software License');

        $asset = Asset::create([
            'asset_tag' => 'SW-001',
            'name' => 'Microsoft 365 License',
            'asset_type_id' => $softwareType->id,
            'license_type' => 'Enterprise',
            'license_seats' => 100,
            'license_expiry_date' => '2025-12-31',
            'license_key' => 'XXXXX-XXXXX-XXXXX-XXXXX',
            'is_active' => true,
        ]);

        $this->assertEquals('Enterprise', $asset->license_type);
        $this->assertEquals(100, $asset->license_seats);
        $this->assertEquals('2025-12-31', $asset->license_expiry_date->format('Y-m-d'));
        $this->assertEquals('XXXXX-XXXXX-XXXXX-XXXXX', $asset->license_key);
    }

    /**
     * Test asset compliance information is stored correctly.
     */
    public function test_asset_compliance_information_stored_correctly(): void
    {
        $complianceStatus = $this->getTaxonomyTerm('compliance-status', 'Compliant');

        $asset = Asset::create([
            'asset_tag' => 'COMP-001',
            'name' => 'Compliant Asset',
            'compliance_status_id' => $complianceStatus->id,
            'last_audit_date' => '2025-01-15',
            'next_audit_date' => '2025-07-15',
            'is_active' => true,
        ]);

        $this->assertEquals($complianceStatus->id, $asset->compliance_status_id);
        $this->assertEquals('2025-01-15', $asset->last_audit_date->format('Y-m-d'));
        $this->assertEquals('2025-07-15', $asset->next_audit_date->format('Y-m-d'));
    }
}
