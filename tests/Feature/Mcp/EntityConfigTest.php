<?php

namespace Tests\Feature\Mcp;

use App\Mcp\EntityConfig;
use App\Models\Application;
use App\Models\Asset;
use App\Models\Audit;
use App\Models\AuditItem;
use App\Models\Control;
use App\Models\Implementation;
use App\Models\Policy;
use App\Models\Program;
use App\Models\Risk;
use App\Models\Standard;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EntityConfigTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        EntityConfig::clearCache();
    }

    /**
     * Test that EntityConfig returns all expected entity types.
     */
    public function test_returns_all_expected_entity_types(): void
    {
        $types = EntityConfig::types();

        $expectedTypes = [
            'application',
            'asset',
            'audit',
            'audit_item',
            'control',
            'implementation',
            'policy',
            'program',
            'risk',
            'standard',
            'vendor',
        ];

        foreach ($expectedTypes as $expectedType) {
            $this->assertContains($expectedType, $types, "Expected type '{$expectedType}' not found in types");
        }
    }

    /**
     * Test that types are sorted alphabetically.
     */
    public function test_types_are_sorted_alphabetically(): void
    {
        $types = EntityConfig::types();
        $sortedTypes = $types;
        sort($sortedTypes);

        $this->assertEquals($sortedTypes, $types);
    }

    /**
     * Test that get() returns config for valid entity type.
     */
    public function test_get_returns_config_for_valid_type(): void
    {
        $config = EntityConfig::get('vendor');

        $this->assertNotNull($config);
        $this->assertIsArray($config);
        $this->assertEquals(Vendor::class, $config['model']);
        $this->assertEquals('Vendor', $config['label']);
        $this->assertEquals('vendors', $config['plural']);
    }

    /**
     * Test that get() returns null for invalid entity type.
     */
    public function test_get_returns_null_for_invalid_type(): void
    {
        $config = EntityConfig::get('nonexistent_type');

        $this->assertNull($config);
    }

    /**
     * Test that model() returns correct model class.
     */
    public function test_model_returns_correct_class(): void
    {
        $this->assertEquals(Standard::class, EntityConfig::model('standard'));
        $this->assertEquals(Control::class, EntityConfig::model('control'));
        $this->assertEquals(Implementation::class, EntityConfig::model('implementation'));
        $this->assertEquals(Policy::class, EntityConfig::model('policy'));
        $this->assertEquals(Risk::class, EntityConfig::model('risk'));
        $this->assertEquals(Program::class, EntityConfig::model('program'));
        $this->assertEquals(Audit::class, EntityConfig::model('audit'));
        $this->assertEquals(AuditItem::class, EntityConfig::model('audit_item'));
        $this->assertEquals(Vendor::class, EntityConfig::model('vendor'));
        $this->assertEquals(Application::class, EntityConfig::model('application'));
        $this->assertEquals(Asset::class, EntityConfig::model('asset'));
    }

    /**
     * Test that model() returns null for invalid type.
     */
    public function test_model_returns_null_for_invalid_type(): void
    {
        $this->assertNull(EntityConfig::model('invalid_type'));
    }

    /**
     * Test that configs are cached after first call.
     */
    public function test_configs_are_cached(): void
    {
        // First call populates cache
        $types1 = EntityConfig::types();
        // Second call should use cache
        $types2 = EntityConfig::types();

        $this->assertEquals($types1, $types2);
    }

    /**
     * Test that clearCache() actually clears the cache.
     */
    public function test_clear_cache_works(): void
    {
        // Populate cache
        EntityConfig::types();

        // Clear cache
        EntityConfig::clearCache();

        // Should still work after clearing
        $types = EntityConfig::types();
        $this->assertNotEmpty($types);
    }

    /**
     * Test that config includes required fields for each type.
     */
    public function test_config_includes_required_fields(): void
    {
        $requiredFields = [
            'model',
            'label',
            'plural',
            'code_field',
            'name_field',
            'search_fields',
            'list_fields',
            'list_relations',
            'list_counts',
            'detail_relations',
            'create_fields',
            'update_fields',
            'url_path',
        ];

        foreach (EntityConfig::types() as $type) {
            $config = EntityConfig::get($type);
            foreach ($requiredFields as $field) {
                $this->assertArrayHasKey($field, $config, "Config for '{$type}' missing field '{$field}'");
            }
        }
    }

    /**
     * Test that Vendor config has expected values (Vendor has $fillable defined).
     */
    public function test_vendor_config_has_expected_values(): void
    {
        $config = EntityConfig::get('vendor');

        // Vendor has no code field but has name field
        $this->assertNull($config['code_field']);
        $this->assertEquals('name', $config['name_field']);
        $this->assertContains('name', $config['search_fields']);
        $this->assertEquals('/app/vendors', $config['url_path']);
    }

    /**
     * Test that Policy config has expected values.
     */
    public function test_policy_config_has_expected_values(): void
    {
        $config = EntityConfig::get('policy');

        $this->assertEquals('code', $config['code_field']);
        $this->assertEquals('name', $config['name_field']);
        $this->assertContains('name', $config['search_fields']);
        $this->assertContains('code', $config['search_fields']);
    }

    /**
     * Test that Policy config includes custom overrides.
     */
    public function test_policy_config_includes_custom_overrides(): void
    {
        $config = EntityConfig::get('policy');

        // Policy has custom list_relations including taxonomy relations
        $this->assertContains('status', $config['list_relations']);
        $this->assertContains('scope', $config['list_relations']);
        $this->assertContains('department', $config['list_relations']);
        $this->assertContains('owner', $config['list_relations']);
    }

    /**
     * Test typeDescriptions returns formatted string.
     */
    public function test_type_descriptions_returns_formatted_string(): void
    {
        $descriptions = EntityConfig::typeDescriptions();

        $this->assertIsString($descriptions);
        $this->assertStringContainsString('standard', $descriptions);
        $this->assertStringContainsString('control', $descriptions);
        $this->assertStringContainsString('policy', $descriptions);
    }

    /**
     * Test createValidationRules returns rules for valid type with $fillable.
     */
    public function test_create_validation_rules_returns_rules(): void
    {
        // Use vendor which has $fillable defined
        $rules = EntityConfig::createValidationRules('vendor');

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertStringContainsString('required', $rules['name']);
    }

    /**
     * Test createValidationRules returns empty array for invalid type.
     */
    public function test_create_validation_rules_returns_empty_for_invalid_type(): void
    {
        $rules = EntityConfig::createValidationRules('invalid_type');

        $this->assertIsArray($rules);
        $this->assertEmpty($rules);
    }

    /**
     * Test that foreign key fields have exists validation.
     */
    public function test_foreign_key_fields_have_exists_validation(): void
    {
        // Use vendor which has vendor_manager_id field
        $rules = EntityConfig::createValidationRules('vendor');

        $this->assertArrayHasKey('vendor_manager_id', $rules);
        // FK fields should have an exists rule (exact table may vary based on config)
        $this->assertStringContainsString('exists:', $rules['vendor_manager_id']);
    }

    /**
     * Test that text fields have correct validation type.
     */
    public function test_text_fields_have_correct_validation(): void
    {
        $rules = EntityConfig::createValidationRules('policy');

        // Body is a text field
        if (isset($rules['body'])) {
            $this->assertStringContainsString('string', $rules['body']);
        }
    }
}
