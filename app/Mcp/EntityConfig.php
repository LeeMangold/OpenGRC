<?php

namespace App\Mcp;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Central configuration for MCP entity operations.
 *
 * This class discovers models that use the HasMcpSupport trait
 * and builds configuration from them automatically.
 */
class EntityConfig
{
    /**
     * Cached configurations.
     *
     * @var array<string, array<string, mixed>>|null
     */
    protected static ?array $cache = null;

    /**
     * Get all supported entity types.
     *
     * @return array<string>
     */
    public static function types(): array
    {
        return array_keys(self::configs());
    }

    /**
     * Get configuration for a specific entity type.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $type): ?array
    {
        return self::configs()[$type] ?? null;
    }

    /**
     * Get the model class for an entity type.
     *
     * @return class-string|null
     */
    public static function model(string $type): ?string
    {
        return self::get($type)['model'] ?? null;
    }

    /**
     * Clear the configuration cache (useful for testing).
     */
    public static function clearCache(): void
    {
        self::$cache = null;
    }

    /**
     * Discover and build entity configurations from models.
     *
     * @return array<string, array<string, mixed>>
     */
    protected static function configs(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        // Scan models directory for classes with HasMcpSupport trait
        $modelsPath = app_path('Models');
        $files = File::files($modelsPath);

        foreach ($files as $file) {
            $className = 'App\\Models\\'.pathinfo($file->getFilename(), PATHINFO_FILENAME);

            if (! class_exists($className)) {
                continue;
            }

            // Check if model uses the HasMcpSupport trait
            if (! in_array(Traits\HasMcpSupport::class, class_uses_recursive($className))) {
                continue;
            }

            // Get the entity type key (snake_case of class name)
            $type = Str::snake(class_basename($className));

            // Get config from the model
            self::$cache[$type] = $className::getMcpConfig();
        }

        // Sort by type name for consistent ordering
        ksort(self::$cache);

        return self::$cache;
    }

    /**
     * Get a human-readable description of all entity types for tool documentation.
     */
    public static function typeDescriptions(): string
    {
        $descriptions = [];
        foreach (self::configs() as $type => $config) {
            $descriptions[] = "- `{$type}`: {$config['label']}";
        }

        return implode("\n", $descriptions);
    }

    /**
     * Build validation rules for create operation.
     *
     * @return array<string, string>
     */
    public static function createValidationRules(string $type): array
    {
        $config = self::get($type);
        if (! $config) {
            return [];
        }

        $rules = [];
        foreach ($config['create_fields'] ?? [] as $field => $fieldConfig) {
            $fieldRules = [];

            if ($fieldConfig['required'] ?? false) {
                $fieldRules[] = 'required';
            } else {
                $fieldRules[] = 'nullable';
            }

            switch ($fieldConfig['type'] ?? 'string') {
                case 'string':
                    $fieldRules[] = 'string';
                    if (isset($fieldConfig['max'])) {
                        $fieldRules[] = 'max:'.$fieldConfig['max'];
                    }
                    break;
                case 'text':
                    $fieldRules[] = 'string';
                    break;
                case 'integer':
                    $fieldRules[] = 'integer';
                    if (isset($fieldConfig['min'])) {
                        $fieldRules[] = 'min:'.$fieldConfig['min'];
                    }
                    if (isset($fieldConfig['max'])) {
                        $fieldRules[] = 'max:'.$fieldConfig['max'];
                    }
                    break;
                case 'number':
                    $fieldRules[] = 'numeric';
                    break;
                case 'boolean':
                    $fieldRules[] = 'boolean';
                    break;
                case 'date':
                    $fieldRules[] = 'date';
                    break;
                case 'email':
                    $fieldRules[] = 'email';
                    break;
                case 'url':
                    $fieldRules[] = 'url';
                    break;
                case 'array':
                    $fieldRules[] = 'array';
                    break;
                case 'enum':
                    if (isset($fieldConfig['values'])) {
                        $fieldRules[] = 'in:'.implode(',', $fieldConfig['values']);
                    }
                    break;
            }

            if (isset($fieldConfig['exists'])) {
                $fieldRules[] = 'exists:'.$fieldConfig['exists'];
            }

            $rules[$field] = implode('|', $fieldRules);
        }

        return $rules;
    }
}
