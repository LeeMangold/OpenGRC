<?php

namespace App\Mcp\Traits;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use ReflectionClass;
use ReflectionMethod;

/**
 * Trait for models to provide MCP (Model Context Protocol) support.
 *
 * This trait auto-discovers model configuration from:
 * - Database schema for available fields
 * - $casts array for field types
 * - Relationship methods via reflection
 * - Conventions for common patterns
 *
 * Models can override specific config by defining mcpConfig() method.
 */
trait HasMcpSupport
{
    /**
     * Get the complete MCP configuration for this model.
     *
     * @return array<string, mixed>
     */
    public static function getMcpConfig(): array
    {
        $instance = new static;
        $defaults = static::buildDefaultMcpConfig($instance);
        $overrides = method_exists(static::class, 'mcpConfig') ? static::mcpConfig() : [];

        return array_merge($defaults, $overrides);
    }

    /**
     * Build default MCP config from model introspection.
     *
     * @return array<string, mixed>
     */
    protected static function buildDefaultMcpConfig(self $instance): array
    {
        $className = class_basename(static::class);
        $columns = static::getTableColumns($instance);
        $casts = $instance->getCasts();

        return [
            'model' => static::class,
            'label' => static::deriveLabel($className),
            'plural' => static::derivePlural($className),
            'code_field' => static::deriveCodeField($columns),
            'name_field' => static::deriveNameField($columns),
            'search_fields' => static::deriveSearchFields($columns, $casts),
            'list_fields' => static::deriveListFields($columns, $casts),
            'list_relations' => static::deriveListRelations($instance),
            'list_counts' => static::deriveListCounts($instance),
            'detail_relations' => static::deriveDetailRelations($instance),
            'create_fields' => static::deriveCreateFields($columns, $casts),
            'update_fields' => static::deriveUpdateFields($columns),
            'field_descriptions' => static::deriveFieldDescriptions($columns, $casts),
            'url_path' => static::deriveUrlPath($className),
        ];
    }

    /**
     * Get all columns from the model's database table.
     *
     * @return array<string>
     */
    protected static function getTableColumns(self $instance): array
    {
        $table = $instance->getTable();
        $connection = $instance->getConnectionName();

        return Schema::connection($connection)->getColumnListing($table);
    }

    /**
     * Derive human-readable label from class name.
     */
    protected static function deriveLabel(string $className): string
    {
        // AuditItem -> Audit Item
        return implode(' ', preg_split('/(?=[A-Z])/', $className, -1, PREG_SPLIT_NO_EMPTY));
    }

    /**
     * Derive plural form for JSON keys.
     */
    protected static function derivePlural(string $className): string
    {
        return Str::snake(Str::pluralStudly($className));
    }

    /**
     * Derive code field if model has one.
     */
    protected static function deriveCodeField(array $columns): ?string
    {
        return in_array('code', $columns) ? 'code' : null;
    }

    /**
     * Derive name/title field.
     */
    protected static function deriveNameField(array $columns): ?string
    {
        foreach (['name', 'title'] as $field) {
            if (in_array($field, $columns)) {
                return $field;
            }
        }

        return null;
    }

    /**
     * Derive searchable fields from columns.
     */
    protected static function deriveSearchFields(array $columns, array $casts): array
    {
        $searchable = [];
        $textFields = ['name', 'title', 'code', 'description', 'details', 'notes', 'body', 'purpose'];

        foreach ($columns as $field) {
            // Include common text fields
            if (in_array($field, $textFields)) {
                $searchable[] = $field;

                continue;
            }

            // Skip ID fields and non-text casts
            if (Str::endsWith($field, '_id')) {
                continue;
            }
            if (isset($casts[$field]) && ! in_array($casts[$field], ['string', 'text'])) {
                continue;
            }
        }

        return $searchable;
    }

    /**
     * Derive list fields (subset of columns for list views).
     */
    protected static function deriveListFields(array $columns, array $casts): array
    {
        $listFields = ['id'];
        $priority = ['code', 'name', 'title', 'status', 'description', 'type', 'category'];

        // Add priority fields first
        foreach ($priority as $field) {
            if (in_array($field, $columns)) {
                $listFields[] = $field;
            }
        }

        // Add date fields
        foreach ($columns as $field) {
            if (Str::contains($field, 'date') && ! in_array($field, $listFields)) {
                $listFields[] = $field;
            }
        }

        return $listFields;
    }

    /**
     * Derive relations to load for list views (belongsTo only).
     */
    protected static function deriveListRelations(self $instance): array
    {
        $relations = [];
        $reflection = new ReflectionClass($instance);

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            $typeName = $returnType->getName();

            // Only include BelongsTo for list views (parent references)
            if ($typeName === 'Illuminate\Database\Eloquent\Relations\BelongsTo') {
                $methodName = $method->getName();
                // Exclude common audit fields
                if (! in_array($methodName, ['creator', 'updater', 'createdBy', 'updatedBy'])) {
                    $relations[] = $methodName;
                }
            }
        }

        return $relations;
    }

    /**
     * Derive relations to count for list views (hasMany, belongsToMany).
     */
    protected static function deriveListCounts(self $instance): array
    {
        $counts = [];
        $reflection = new ReflectionClass($instance);
        $countableTypes = [
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            if (in_array($returnType->getName(), $countableTypes)) {
                $counts[] = $method->getName();
            }
        }

        return $counts;
    }

    /**
     * Derive relations to load for detail views (all relations).
     */
    protected static function deriveDetailRelations(self $instance): array
    {
        $relations = [];
        $reflection = new ReflectionClass($instance);
        $relationTypes = [
            'Illuminate\Database\Eloquent\Relations\BelongsTo',
            'Illuminate\Database\Eloquent\Relations\HasMany',
            'Illuminate\Database\Eloquent\Relations\HasOne',
            'Illuminate\Database\Eloquent\Relations\BelongsToMany',
            'Illuminate\Database\Eloquent\Relations\MorphMany',
            'Illuminate\Database\Eloquent\Relations\MorphTo',
        ];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if ($method->class !== static::class) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (! $returnType) {
                continue;
            }

            if (in_array($returnType->getName(), $relationTypes)) {
                $relations[] = $method->getName();
            }
        }

        return $relations;
    }

    /**
     * Derive create fields with type info from database columns and casts.
     */
    protected static function deriveCreateFields(array $columns, array $casts): array
    {
        $fields = [];
        $requiredFields = ['name', 'title', 'code'];
        // Exclude auto-managed fields
        $excludeFromCreate = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

        foreach ($columns as $field) {
            if (in_array($field, $excludeFromCreate)) {
                continue;
            }

            $fieldConfig = static::deriveFieldType($field, $casts[$field] ?? null);

            // Mark common required fields
            if (in_array($field, $requiredFields)) {
                $fieldConfig['required'] = true;
            }

            $fields[$field] = $fieldConfig;
        }

        return $fields;
    }

    /**
     * Derive field type configuration.
     *
     * @return array<string, mixed>
     */
    protected static function deriveFieldType(string $field, ?string $cast): array
    {
        // Handle _id fields as integer references
        if (Str::endsWith($field, '_id')) {
            $table = static::resolveRelatedTable($field);

            return [
                'type' => 'integer',
                'exists' => "{$table},id",
            ];
        }

        // Handle based on cast
        $type = match ($cast) {
            'integer', 'int' => 'integer',
            'float', 'double', 'decimal' => 'number',
            'boolean', 'bool' => 'boolean',
            'date', 'datetime', 'immutable_date', 'immutable_datetime' => 'date',
            'array', 'json', 'object', 'collection' => 'array',
            default => null,
        };

        if ($type) {
            return ['type' => $type];
        }

        // Handle based on field name patterns
        if (Str::contains($field, ['email'])) {
            return ['type' => 'email'];
        }

        if (Str::contains($field, ['url', 'website', 'link'])) {
            return ['type' => 'url'];
        }

        if (Str::contains($field, ['phone', 'fax', 'mobile'])) {
            return ['type' => 'string', 'max' => 50];
        }

        if (Str::contains($field, ['body', 'description', 'details', 'notes', 'content', 'purpose', 'scope'])) {
            return ['type' => 'text'];
        }

        // Default to string with max length
        return ['type' => 'string', 'max' => 255];
    }

    /**
     * Derive update fields (all columns minus auto-managed fields).
     */
    protected static function deriveUpdateFields(array $columns): array
    {
        $exclude = ['id', 'created_by', 'updated_by', 'created_at', 'updated_at', 'deleted_at'];

        return array_values(array_diff($columns, $exclude));
    }

    /**
     * Derive URL path from class name.
     */
    protected static function deriveUrlPath(string $className): string
    {
        return '/app/' . Str::kebab(Str::pluralStudly($className));
    }

    /**
     * Derive field descriptions from field names and casts.
     *
     * Models can override specific descriptions in mcpConfig():
     * return ['field_descriptions' => ['my_field' => 'Custom description']];
     *
     * @return array<string, string>
     */
    protected static function deriveFieldDescriptions(array $columns, array $casts): array
    {
        $descriptions = [];

        foreach ($columns as $field) {
            $descriptions[$field] = static::deriveFieldDescription($field, $casts[$field] ?? null);
        }

        return $descriptions;
    }

    /**
     * Derive a description for a single field based on its name and cast.
     */
    protected static function deriveFieldDescription(string $field, ?string $cast): string
    {
        // Handle foreign key fields
        if (Str::endsWith($field, '_id')) {
            $relation = Str::beforeLast($field, '_id');
            $humanName = Str::title(str_replace('_', ' ', $relation));

            return "ID of the related {$humanName}";
        }

        // Handle date fields (but not fields that just contain 'date' like 'updated_by')
        if (Str::endsWith($field, '_date')) {
            $humanName = Str::title(str_replace('_', ' ', str_replace('_date', '', $field)));

            return "{$humanName} date (YYYY-MM-DD format)";
        }

        // Handle common field name patterns
        $commonDescriptions = [
            'name' => 'Display name of the entity',
            'title' => 'Title of the entity',
            'code' => 'Unique identifier code',
            'description' => 'Brief description',
            'details' => 'Detailed information (supports HTML)',
            'notes' => 'Additional notes or comments',
            'body' => 'Main content body (supports HTML)',
            'purpose' => 'Purpose or objective (supports HTML)',
            'policy_scope' => 'Scope of the policy (supports HTML)',
            'status' => 'Current status',
            'type' => 'Type classification',
            'category' => 'Category classification',
            'likelihood' => 'Likelihood rating (typically 1-5)',
            'impact' => 'Impact rating (typically 1-5)',
            'action' => 'Action to be taken',
            'response' => 'Response or finding',
            'observation' => 'Observation or comment',
            'recommendation' => 'Recommended action',
            'email' => 'Email address',
            'phone' => 'Phone number',
            'url' => 'URL or web address',
            'website' => 'Website URL',
            'address' => 'Physical address',
            'city' => 'City name',
            'state' => 'State or province',
            'country' => 'Country name',
            'zip' => 'ZIP or postal code',
            'contact_name' => 'Contact person name',
            'contact_email' => 'Contact email address',
            'contact_phone' => 'Contact phone number',
            'document_path' => 'Path to associated document file',
            'revision_history' => 'History of revisions (JSON array)',
            'created_by' => 'ID of the user who created this entity',
            'updated_by' => 'ID of the user who last updated this entity',
        ];

        if (isset($commonDescriptions[$field])) {
            return $commonDescriptions[$field];
        }

        // Handle based on cast type
        if ($cast) {
            if (class_exists($cast) && enum_exists($cast)) {
                $enumName = class_basename($cast);

                return "{$enumName} value";
            }

            return match ($cast) {
                'boolean', 'bool' => 'Boolean (true/false)',
                'integer', 'int' => 'Integer value',
                'float', 'double', 'decimal' => 'Numeric value',
                'array', 'json', 'collection' => 'JSON array or object',
                'date' => 'Date (YYYY-MM-DD format)',
                'datetime' => 'Date and time (ISO 8601 format)',
                default => Str::title(str_replace('_', ' ', $field)),
            };
        }

        // Generate description from field name
        return Str::title(str_replace('_', ' ', $field));
    }

    /**
     * Resolve the related table name for a foreign key field.
     *
     * Looks up the relationship method to get the actual related model's table.
     * Falls back to deriving from field name if relationship not found.
     */
    protected static function resolveRelatedTable(string $field): string
    {
        $instance = new static;
        $relationName = Str::camel(Str::beforeLast($field, '_id'));

        // Try to find a relationship method matching the field
        if (method_exists($instance, $relationName)) {
            try {
                $relation = $instance->{$relationName}();
                if (method_exists($relation, 'getRelated')) {
                    return $relation->getRelated()->getTable();
                }
            } catch (\Throwable $e) {
                // Fall through to default behavior
            }
        }

        // Fallback: derive table name from field name
        return Str::plural(Str::beforeLast($field, '_id'));
    }
}
