<?php

namespace Modules\DataManager\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use ReflectionClass;
use ReflectionMethod;
use ReflectionNamedType;

class SchemaInspector
{
    /**
     * Get all fields for a model with their metadata.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getFields(string $modelClass): array
    {
        $model = new $modelClass;
        $table = $model->getTable();
        $columns = Schema::getColumns($table);
        $casts = $model->getCasts();
        $fillable = $model->getFillable();
        $hidden = $model->getHidden();

        return collect($columns)->map(function ($column) use ($casts, $fillable, $hidden) {
            $name = $column['name'];
            $castType = $casts[$name] ?? null;

            return [
                'name' => $name,
                'label' => $this->generateLabel($name),
                'db_type' => $column['type_name'] ?? $column['type'] ?? 'string',
                'field_type' => $this->mapColumnType($column['type_name'] ?? $column['type'] ?? 'string', $castType),
                'required' => ! ($column['nullable'] ?? true),
                'is_primary' => $name === 'id',
                'is_foreign_key' => str_ends_with($name, '_id') && $name !== 'id',
                'is_timestamp' => in_array($name, ['created_at', 'updated_at', 'deleted_at']),
                'is_fillable' => empty($fillable) || in_array($name, $fillable),
                'is_hidden' => in_array($name, $hidden),
                'cast' => $castType,
                'enum_class' => $this->getEnumClass($castType),
            ];
        })->keyBy('name')->toArray();
    }

    /**
     * Get exportable fields (excludes hidden and system fields by default).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getExportableFields(string $modelClass, bool $includeTimestamps = false): array
    {
        return collect($this->getFields($modelClass))
            ->filter(function ($field) use ($includeTimestamps) {
                // Always include
                if ($field['is_primary']) {
                    return true;
                }

                // Skip hidden fields
                if ($field['is_hidden']) {
                    return false;
                }

                // Optionally skip timestamps
                if (! $includeTimestamps && $field['is_timestamp']) {
                    return false;
                }

                return true;
            })
            ->toArray();
    }

    /**
     * Get importable fields (includes id for upsert, excludes timestamps).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getImportableFields(string $modelClass): array
    {
        return collect($this->getFields($modelClass))
            ->filter(function ($field) {
                // Include primary key for upsert operations
                if ($field['is_primary']) {
                    return true;
                }

                // Skip timestamps - auto-managed
                if ($field['is_timestamp']) {
                    return false;
                }

                // Include if fillable
                return $field['is_fillable'];
            })
            ->toArray();
    }

    /**
     * Get required fields for import validation.
     * Note: Primary key (id) is never required - it's optional for upsert.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getRequiredFields(string $modelClass): array
    {
        return collect($this->getImportableFields($modelClass))
            ->filter(fn ($field) => $field['required'] && ! $field['is_primary'])
            ->toArray();
    }

    /**
     * Get relationships for a model using reflection.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getRelationships(string $modelClass): array
    {
        $model = new $modelClass;
        $reflection = new ReflectionClass($model);
        $relationships = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Skip inherited methods from base Model
            if ($method->class !== $modelClass) {
                continue;
            }

            // Skip methods with parameters
            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            // Skip common non-relationship methods
            $skipMethods = ['getTable', 'getKey', 'getKeyName', 'getCasts', 'getFillable', 'getHidden', 'toArray', 'toJson'];
            if (in_array($method->name, $skipMethods)) {
                continue;
            }

            try {
                $returnType = $method->getReturnType();
                if ($returnType instanceof ReflectionNamedType) {
                    $typeName = $returnType->getName();

                    // Check for relationship return types
                    $relationTypes = [
                        'Illuminate\Database\Eloquent\Relations\BelongsTo' => 'belongsTo',
                        'Illuminate\Database\Eloquent\Relations\HasOne' => 'hasOne',
                        'Illuminate\Database\Eloquent\Relations\HasMany' => 'hasMany',
                        'Illuminate\Database\Eloquent\Relations\BelongsToMany' => 'belongsToMany',
                        'Illuminate\Database\Eloquent\Relations\MorphTo' => 'morphTo',
                        'Illuminate\Database\Eloquent\Relations\MorphOne' => 'morphOne',
                        'Illuminate\Database\Eloquent\Relations\MorphMany' => 'morphMany',
                        'Illuminate\Database\Eloquent\Relations\MorphToMany' => 'morphToMany',
                    ];

                    if (isset($relationTypes[$typeName])) {
                        $relationships[$method->name] = [
                            'name' => $method->name,
                            'type' => $relationTypes[$typeName],
                            'label' => $this->generateLabel($method->name),
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Skip methods that throw errors
                continue;
            }
        }

        return $relationships;
    }

    /**
     * Get BelongsToMany relationships that can be exported/imported.
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getManyToManyRelationships(string $modelClass): array
    {
        return collect($this->getRelationships($modelClass))
            ->filter(fn ($rel) => $rel['type'] === 'belongsToMany')
            ->toArray();
    }

    /**
     * Generate a human-readable label from a column name.
     */
    protected function generateLabel(string $name): string
    {
        return str($name)
            ->replace('_', ' ')
            ->replace('id', 'ID')
            ->title()
            ->toString();
    }

    /**
     * Map database column type to a field type.
     */
    protected function mapColumnType(string $dbType, ?string $cast): string
    {
        // Check cast first for enums
        if ($cast && $this->isEnumCast($cast)) {
            return 'enum';
        }

        // Check cast for arrays/json
        if (in_array($cast, ['array', 'json', 'collection'])) {
            return 'json';
        }

        // Map database types
        return match (strtolower($dbType)) {
            'tinyint', 'boolean', 'bool' => 'boolean',
            'int', 'integer', 'bigint', 'smallint', 'mediumint' => 'integer',
            'float', 'double', 'decimal', 'numeric', 'real' => 'decimal',
            'text', 'mediumtext', 'longtext' => 'text',
            'date' => 'date',
            'datetime', 'timestamp' => 'datetime',
            'time' => 'time',
            'json', 'jsonb' => 'json',
            default => 'string',
        };
    }

    /**
     * Check if a cast is an enum cast.
     */
    protected function isEnumCast(?string $cast): bool
    {
        if (! $cast) {
            return false;
        }

        // Check if it's a class that exists and is an enum
        if (class_exists($cast) || enum_exists($cast)) {
            return enum_exists($cast);
        }

        return false;
    }

    /**
     * Get the enum class from a cast if it's an enum.
     */
    protected function getEnumClass(?string $cast): ?string
    {
        if ($cast && enum_exists($cast)) {
            return $cast;
        }

        return null;
    }

    /**
     * Get field options for select dropdown (for mapping UI).
     *
     * @param  class-string<Model>  $modelClass
     */
    public function getFieldOptions(string $modelClass): array
    {
        return collect($this->getImportableFields($modelClass))
            ->mapWithKeys(fn ($field) => [$field['name'] => $field['label']])
            ->toArray();
    }
}
