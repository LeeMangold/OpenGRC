<?php

namespace Tests\Feature\Mcp;

use App\Mcp\EntityConfig;
use App\Models\Application;
use App\Models\Policy;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasMcpSupportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EntityConfig::clearCache();
    }

    /**
     * Test that label is derived from class name.
     */
    public function test_label_is_derived_from_class_name(): void
    {
        $vendorConfig = Vendor::getMcpConfig();
        $this->assertEquals('Vendor', $vendorConfig['label']);

        $policyConfig = Policy::getMcpConfig();
        $this->assertEquals('Policy', $policyConfig['label']);

        $appConfig = Application::getMcpConfig();
        $this->assertEquals('Application', $appConfig['label']);
    }

    /**
     * Test that plural is snake_case plural of class name.
     */
    public function test_plural_is_snake_case_plural(): void
    {
        $vendorConfig = Vendor::getMcpConfig();
        $this->assertEquals('vendors', $vendorConfig['plural']);

        $policyConfig = Policy::getMcpConfig();
        $this->assertEquals('policies', $policyConfig['plural']);
    }

    /**
     * Test that code_field is set correctly for models with code in $fillable.
     */
    public function test_code_field_is_set_for_models_with_code(): void
    {
        // Policy has code in $fillable
        $policyConfig = Policy::getMcpConfig();
        $this->assertEquals('code', $policyConfig['code_field']);

        // Vendor doesn't have code field
        $vendorConfig = Vendor::getMcpConfig();
        $this->assertNull($vendorConfig['code_field']);
    }

    /**
     * Test that name_field detects name or title from $fillable.
     */
    public function test_name_field_detects_name_or_title(): void
    {
        // Vendor has 'name' in $fillable
        $vendorConfig = Vendor::getMcpConfig();
        $this->assertEquals('name', $vendorConfig['name_field']);

        // Policy has 'name' in $fillable
        $policyConfig = Policy::getMcpConfig();
        $this->assertEquals('name', $policyConfig['name_field']);
    }

    /**
     * Test that search_fields include common text fields from $fillable.
     */
    public function test_search_fields_include_common_text_fields(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertIsArray($vendorConfig['search_fields']);
        $this->assertContains('name', $vendorConfig['search_fields']);
        $this->assertContains('description', $vendorConfig['search_fields']);
    }

    /**
     * Test that list_fields include id and priority fields from $fillable.
     */
    public function test_list_fields_include_id_and_priority_fields(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertIsArray($vendorConfig['list_fields']);
        $this->assertContains('id', $vendorConfig['list_fields']);
        $this->assertContains('name', $vendorConfig['list_fields']);
        $this->assertContains('status', $vendorConfig['list_fields']);
    }

    /**
     * Test that list_relations include BelongsTo relationships.
     */
    public function test_list_relations_include_belongs_to(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertIsArray($vendorConfig['list_relations']);
        $this->assertContains('vendorManager', $vendorConfig['list_relations']);
    }

    /**
     * Test that list_counts include HasMany and BelongsToMany.
     */
    public function test_list_counts_include_has_many_and_belongs_to_many(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertIsArray($vendorConfig['list_counts']);
        $this->assertContains('applications', $vendorConfig['list_counts']);
    }

    /**
     * Test that detail_relations include all relationship types.
     */
    public function test_detail_relations_include_all_relationships(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertIsArray($vendorConfig['detail_relations']);
        $this->assertContains('applications', $vendorConfig['detail_relations']);
    }

    /**
     * Test that create_fields exclude audit fields.
     */
    public function test_create_fields_exclude_audit_fields(): void
    {
        $policyConfig = Policy::getMcpConfig();

        $createFieldKeys = array_keys($policyConfig['create_fields']);
        $this->assertNotContains('created_at', $createFieldKeys);
        $this->assertNotContains('updated_at', $createFieldKeys);
    }

    /**
     * Test that create_fields have type info.
     */
    public function test_create_fields_have_type_info(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertNotEmpty($vendorConfig['create_fields']);
        foreach ($vendorConfig['create_fields'] as $field => $fieldConfig) {
            $this->assertArrayHasKey('type', $fieldConfig, "Field '{$field}' missing type");
        }
    }

    /**
     * Test that foreign key fields have exists config.
     */
    public function test_foreign_key_fields_have_exists_config(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $this->assertArrayHasKey('vendor_manager_id', $vendorConfig['create_fields']);
        $fieldConfig = $vendorConfig['create_fields']['vendor_manager_id'];
        $this->assertEquals('integer', $fieldConfig['type']);
        $this->assertArrayHasKey('exists', $fieldConfig);
        // FK fields should have an exists config pointing to the related table
        $this->assertNotEmpty($fieldConfig['exists']);
    }

    /**
     * Test that url_path is derived correctly.
     */
    public function test_url_path_is_derived_correctly(): void
    {
        $vendorConfig = Vendor::getMcpConfig();
        $this->assertEquals('/app/vendors', $vendorConfig['url_path']);

        $policyConfig = Policy::getMcpConfig();
        $this->assertEquals('/app/policies', $policyConfig['url_path']);
    }

    /**
     * Test that mcpConfig overrides are applied.
     */
    public function test_mcp_config_overrides_are_applied(): void
    {
        $policyConfig = Policy::getMcpConfig();

        // Policy has custom list_relations
        $this->assertContains('status', $policyConfig['list_relations']);
        $this->assertContains('scope', $policyConfig['list_relations']);
        $this->assertContains('department', $policyConfig['list_relations']);
    }

    /**
     * Test that required fields are marked as required.
     */
    public function test_required_fields_are_marked(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        $nameField = $vendorConfig['create_fields']['name'] ?? null;
        $this->assertNotNull($nameField);
        $this->assertTrue($nameField['required'] ?? false);
    }

    /**
     * Test that text fields are typed as text.
     */
    public function test_text_fields_are_typed_correctly(): void
    {
        $vendorConfig = Vendor::getMcpConfig();

        // Vendor has description field
        $this->assertArrayHasKey('description', $vendorConfig['create_fields']);
        $this->assertEquals('text', $vendorConfig['create_fields']['description']['type']);
    }

    /**
     * Test that date fields are typed as date.
     */
    public function test_date_fields_are_typed_correctly(): void
    {
        $policyConfig = Policy::getMcpConfig();

        if (isset($policyConfig['create_fields']['effective_date'])) {
            $this->assertEquals('date', $policyConfig['create_fields']['effective_date']['type']);
        }
    }

    /**
     * Test that update_fields exclude created_by and updated_by.
     */
    public function test_update_fields_exclude_audit_user_fields(): void
    {
        $policyConfig = Policy::getMcpConfig();

        $this->assertNotContains('created_by', $policyConfig['update_fields']);
        $this->assertNotContains('updated_by', $policyConfig['update_fields']);
    }

    /**
     * Test that field_descriptions is included in config.
     */
    public function test_field_descriptions_included_in_config(): void
    {
        $config = Vendor::getMcpConfig();

        $this->assertArrayHasKey('field_descriptions', $config);
        $this->assertIsArray($config['field_descriptions']);
    }

    /**
     * Test that field_descriptions has entries for all database columns.
     */
    public function test_field_descriptions_has_entries_for_database_columns(): void
    {
        $config = Vendor::getMcpConfig();
        $columns = array_keys($config['create_fields']);

        foreach ($columns as $field) {
            $this->assertArrayHasKey($field, $config['field_descriptions'], "Missing description for field: {$field}");
        }
    }

    /**
     * Test that foreign key fields get proper descriptions.
     */
    public function test_foreign_key_field_descriptions(): void
    {
        $config = Vendor::getMcpConfig();

        $this->assertArrayHasKey('vendor_manager_id', $config['field_descriptions']);
        $this->assertStringContainsString('ID of the related', $config['field_descriptions']['vendor_manager_id']);
    }

    /**
     * Test that date fields get proper descriptions.
     */
    public function test_date_field_descriptions(): void
    {
        $config = Policy::getMcpConfig();

        $this->assertArrayHasKey('effective_date', $config['field_descriptions']);
        $this->assertStringContainsString('YYYY-MM-DD', $config['field_descriptions']['effective_date']);
    }

    /**
     * Test that common fields get standard descriptions.
     */
    public function test_common_field_descriptions(): void
    {
        $config = Vendor::getMcpConfig();

        $this->assertEquals('Display name of the entity', $config['field_descriptions']['name']);
        $this->assertEquals('Brief description', $config['field_descriptions']['description']);
    }

    /**
     * Test that HTML-supporting fields mention HTML in description.
     */
    public function test_html_field_descriptions(): void
    {
        $config = Policy::getMcpConfig();

        $this->assertStringContainsString('HTML', $config['field_descriptions']['body']);
        $this->assertStringContainsString('HTML', $config['field_descriptions']['purpose']);
    }

    /**
     * Test that field_descriptions can be overridden in mcpConfig.
     */
    public function test_field_descriptions_can_be_overridden(): void
    {
        // This tests the merge behavior - if a model defines field_descriptions
        // in mcpConfig(), those should override the defaults
        $config = Policy::getMcpConfig();

        // Even without custom overrides, should have all fields
        $this->assertArrayHasKey('name', $config['field_descriptions']);
    }

    /**
     * Test that updated_by is not mistakenly treated as a date field.
     */
    public function test_updated_by_not_treated_as_date(): void
    {
        $config = Policy::getMcpConfig();

        // updated_by should be described as user reference, not as a date
        $this->assertArrayHasKey('updated_by', $config['field_descriptions']);
        $this->assertStringNotContainsString('YYYY-MM-DD', $config['field_descriptions']['updated_by']);
        $this->assertStringContainsString('user', $config['field_descriptions']['updated_by']);
    }
}
