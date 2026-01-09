<?php

namespace App\Mcp\Tools;

use App\Mcp\EntityConfig;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Base class for entity management tools.
 *
 * Provides list, get, create, update, and delete operations for GRC entities.
 * Uses convention-over-configuration to derive tool name, description,
 * and entity type from the class name (e.g., ManageVendorTool → vendor).
 * Subclasses can override for customization.
 *
 * Authorization is enforced using OpenGRC's permission system (Spatie Laravel Permission).
 * Each action requires the corresponding permission (e.g., "List Policies", "Create Controls").
 */
abstract class BaseManageEntityTool extends Tool
{
    /**
     * Map of MCP actions to permission action names.
     */
    protected const ACTION_PERMISSION_MAP = [
        'list' => 'List',
        'get' => 'Read',
        'create' => 'Create',
        'update' => 'Update',
        'delete' => 'Delete',
    ];

    /**
     * The entity type this tool manages (e.g., 'policy', 'control').
     * Derived from class name by default (ManagePolicyTool → policy).
     */
    protected function entityType(): string
    {
        $className = class_basename(static::class);
        // ManageVendorTool → Vendor → vendor
        // ManageAuditItemTool → AuditItem → audit_item
        $entityName = Str::replaceLast('Tool', '', Str::replaceFirst('Manage', '', $className));

        return Str::snake($entityName);
    }

    /**
     * Get the tool name.
     * Derived from class name by default (ManagePolicyTool → ManagePolicy).
     */
    public function name(): string
    {
        if (isset($this->name) && $this->name !== '') {
            return $this->name;
        }

        $className = class_basename(static::class);

        // ManageVendorTool → ManageVendor
        return Str::replaceLast('Tool', '', $className);
    }

    /**
     * Get the tool description.
     * Derived from EntityConfig by default.
     */
    public function description(): string
    {
        if (isset($this->description) && $this->description !== '') {
            return $this->description;
        }

        $config = EntityConfig::get($this->entityType());
        $label = $config['label'] ?? Str::title(str_replace('_', ' ', $this->entityType()));
        $plural = $config['plural'] ?? Str::plural($label);

        return "Manages {$label} entities ({$plural}). Actions: list (paginated), get (by id), create (with data), update (id + data), delete (id + confirm=true).";
    }

    /**
     * Get the permission name for a given action.
     *
     * Builds permission strings like "List Policies", "Create Controls", etc.
     * Uses the entity's model class name to derive the plural form.
     */
    protected function getPermissionName(string $action): string
    {
        $config = EntityConfig::get($this->entityType());
        $modelClass = $config['model'];
        $className = class_basename($modelClass);
        $plural = Str::plural($className);

        return "{$action} {$plural}";
    }

    /**
     * Check if the current user is authorized to perform the given action.
     *
     * Returns an error Response if authorization fails, or null if authorized.
     */
    protected function authorize(Request $request, string $action): ?Response
    {
        $user = $request->user();

        if (! $user) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Authentication required.',
            ], JSON_PRETTY_PRINT));
        }

        $permission = $this->getPermissionName($action);

        if (! $user->can($permission)) {
            $actionLower = strtolower($action);

            return Response::text(json_encode([
                'success' => false,
                'error' => "You do not have permission to {$actionLower} this entity.",
                'required_permission' => $permission,
            ], JSON_PRETTY_PRINT));
        }

        return null;
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => 'required|string|in:list,get,create,update,delete',
            'id' => 'nullable|integer',
            'page' => 'nullable|integer|min:1',
            'data' => 'nullable|array',
            'confirm' => 'nullable|boolean',
        ]);

        $action = $validated['action'];

        // Map MCP action to permission action and check authorization
        $permissionAction = self::ACTION_PERMISSION_MAP[$action];
        if ($error = $this->authorize($request, $permissionAction)) {
            return $error;
        }

        $type = $this->entityType();
        $config = EntityConfig::get($type);

        if (! $config) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Unknown entity type: {$type}",
            ], JSON_PRETTY_PRINT));
        }

        return match ($action) {
            'list' => $this->handleList($validated, $config),
            'get' => $this->handleGet($validated, $config),
            'create' => $this->handleCreate($validated, $config),
            'update' => $this->handleUpdate($validated, $config),
            'delete' => $this->handleDelete($validated, $config),
        };
    }

    /**
     * Handle list action - returns paginated list of entities.
     */
    protected function handleList(array $validated, array $config): Response
    {
        $modelClass = $config['model'];
        $query = $modelClass::query();

        // Load relations for list view
        if (! empty($config['list_relations'])) {
            $query->with($config['list_relations']);
        }

        // Add counts
        if (! empty($config['list_counts'])) {
            $query->withCount($config['list_counts']);
        }

        // Order by code if available, otherwise by name/title, otherwise by id
        if ($config['code_field']) {
            $query->orderBy($config['code_field']);
        } elseif ($config['name_field']) {
            $query->orderBy($config['name_field']);
        } else {
            $query->orderBy('id');
        }

        // Paginate with default of 50 items per page
        $page = $validated['page'] ?? 1;
        $results = $query->paginate(50, ['*'], 'page', $page);

        // Format results
        $items = $results->map(function ($item) use ($config) {
            return $this->formatListItem($item, $config);
        })->toArray();

        $result = [
            'success' => true,
            'action' => 'list',
            'type' => $this->entityType(),
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            $config['plural'] => $items,
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Handle get action - returns a single entity by ID.
     */
    protected function handleGet(array $validated, array $config): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for get action.',
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $query = $modelClass::query();

        // Load relations for detail view
        if (! empty($config['detail_relations'])) {
            $query->with($config['detail_relations']);
        }

        $entity = $query->find($validated['id']);

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $result = [
            'success' => true,
            'action' => 'get',
            'type' => $this->entityType(),
            Str::singular($config['plural']) => $this->formatDetailItem($entity, $config),
        ];

        return Response::text(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Handle create action.
     */
    protected function handleCreate(array $validated, array $config): Response
    {
        if (empty($validated['data'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Data is required for create action.',
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];

        // Check if child class wants to auto-generate code
        $needsAutoCode = $this->shouldAutoGenerateCode($data);

        // Build and apply validation rules for the data
        $rules = EntityConfig::createValidationRules($this->entityType());
        $prefixedRules = [];
        foreach ($rules as $field => $rule) {
            // Skip code validation if we're auto-generating it
            if ($field === 'code' && $needsAutoCode) {
                continue;
            }
            $prefixedRules["data.{$field}"] = $rule;
        }

        try {
            validator($validated, $prefixedRules)->validate();
        } catch (\Illuminate\Validation\ValidationException $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Validation failed',
                'validation_errors' => $e->errors(),
            ], JSON_PRETTY_PRINT));
        }

        try {
            return DB::transaction(function () use ($config, $data, $needsAutoCode) {
                $modelClass = $config['model'];

                // Auto-generate code inside transaction with locking to prevent race conditions
                if ($needsAutoCode) {
                    $data['code'] = $this->generateUniqueCode($modelClass, $this->getCodePrefix());
                }

                // Check for duplicate code if code field exists (for user-provided codes)
                if (! $needsAutoCode && $config['code_field'] && ! empty($data[$config['code_field']])) {
                    $code = $data[$config['code_field']];
                    if ($modelClass::where($config['code_field'], $code)->exists()) {
                        return Response::text(json_encode([
                            'success' => false,
                            'error' => "A {$config['label']} with code '{$code}' already exists.",
                        ], JSON_PRETTY_PRINT));
                    }
                }

                // Allow child classes to set defaults before creation
                $data = $this->prepareCreateData($data);

                // Filter data to only include allowed create fields
                $allowedFields = array_keys($config['create_fields'] ?? []);
                $filteredData = array_intersect_key($data, array_flip($allowedFields));

                // Create the entity
                $entity = $modelClass::create($filteredData);

                // Load relations for response
                if (! empty($config['detail_relations'])) {
                    $entity->load($config['detail_relations']);
                }

                $nameField = $config['name_field'] ?? 'name';
                $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";

                return Response::text(json_encode([
                    'success' => true,
                    'action' => 'created',
                    'message' => "{$config['label']} '{$name}' created successfully.",
                    Str::singular($config['plural']) => $this->formatItem($entity, $config),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to create {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Handle update action.
     */
    protected function handleUpdate(array $validated, array $config): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for update action.',
            ], JSON_PRETTY_PRINT));
        }

        if (empty($validated['data'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Data is required for update action.',
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $entity = $modelClass::find($validated['id']);

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        $data = $validated['data'];

        try {
            return DB::transaction(function () use ($entity, $config, $data) {
                // Filter data to only include allowed update fields
                $allowedFields = $config['update_fields'] ?? [];
                $filteredData = array_intersect_key($data, array_flip($allowedFields));

                if (empty($filteredData)) {
                    return Response::text(json_encode([
                        'success' => false,
                        'error' => 'No valid fields to update. Allowed fields: '.implode(', ', $allowedFields),
                    ], JSON_PRETTY_PRINT));
                }

                // Check for duplicate code if updating code field
                if ($config['code_field'] && isset($filteredData[$config['code_field']])) {
                    $newCode = $filteredData[$config['code_field']];
                    $modelClass = $config['model'];
                    $existing = $modelClass::where($config['code_field'], $newCode)
                        ->where('id', '!=', $entity->id)
                        ->exists();
                    if ($existing) {
                        return Response::text(json_encode([
                            'success' => false,
                            'error' => "A {$config['label']} with code '{$newCode}' already exists.",
                        ], JSON_PRETTY_PRINT));
                    }
                }

                // Update the entity
                $entity->update($filteredData);

                // Reload with relations
                if (! empty($config['detail_relations'])) {
                    $entity->load($config['detail_relations']);
                }

                $nameField = $config['name_field'] ?? 'name';
                $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";

                return Response::text(json_encode([
                    'success' => true,
                    'action' => 'updated',
                    'message' => "{$config['label']} '{$name}' updated successfully.",
                    'updated_fields' => array_keys($filteredData),
                    Str::singular($config['plural']) => $this->formatItem($entity, $config),
                ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            });
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to update {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Handle delete action.
     */
    protected function handleDelete(array $validated, array $config): Response
    {
        if (empty($validated['id'])) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'ID is required for delete action.',
            ], JSON_PRETTY_PRINT));
        }

        if (empty($validated['confirm']) || $validated['confirm'] !== true) {
            return Response::text(json_encode([
                'success' => false,
                'error' => 'Delete operation not confirmed. Set confirm: true to proceed.',
            ], JSON_PRETTY_PRINT));
        }

        $modelClass = $config['model'];
        $entity = $modelClass::find($validated['id']);

        if (! $entity) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "{$config['label']} with ID {$validated['id']} not found.",
            ], JSON_PRETTY_PRINT));
        }

        // Get identifying info before deletion
        $nameField = $config['name_field'] ?? 'name';
        $name = $entity->{$nameField} ?? $entity->{$config['code_field']} ?? "#{$entity->id}";
        $entityId = $entity->id;

        try {
            // Check if model uses soft deletes
            $usesSoftDeletes = in_array(
                \Illuminate\Database\Eloquent\SoftDeletes::class,
                class_uses_recursive($modelClass)
            );

            $entity->delete();

            return Response::text(json_encode([
                'success' => true,
                'action' => 'deleted',
                'message' => "{$config['label']} '{$name}' (ID: {$entityId}) has been deleted.",
                'soft_deleted' => $usesSoftDeletes,
                'restorable' => $usesSoftDeletes,
            ], JSON_PRETTY_PRINT));
        } catch (\Exception $e) {
            return Response::text(json_encode([
                'success' => false,
                'error' => "Failed to delete {$config['label']}: ".$e->getMessage(),
            ], JSON_PRETTY_PRINT));
        }
    }

    /**
     * Determine if code should be auto-generated for this entity.
     * Override in child classes to enable auto-generation.
     */
    protected function shouldAutoGenerateCode(array $data): bool
    {
        return false;
    }

    /**
     * Get the prefix for auto-generated codes (e.g., 'POL', 'CTL').
     * Override in child classes that support auto-generation.
     */
    protected function getCodePrefix(): string
    {
        return '';
    }

    /**
     * Prepare data before entity creation.
     * Override in child classes to set defaults or transform data.
     */
    protected function prepareCreateData(array $data): array
    {
        return $data;
    }

    /**
     * Generate a unique code for an entity.
     * Must be called within a transaction for proper locking.
     */
    protected function generateUniqueCode(string $modelClass, string $prefix): string
    {
        $pattern = $prefix.'-%';

        // Use lockForUpdate to prevent race conditions when multiple requests
        // try to generate codes simultaneously within concurrent transactions.
        // Include soft-deleted records to avoid unique constraint violations.
        $query = $modelClass::where('code', 'like', $pattern)->lockForUpdate();

        // Include soft-deleted records if the model uses SoftDeletes
        if (method_exists($modelClass, 'withTrashed')) {
            $query = $modelClass::withTrashed()->where('code', 'like', $pattern)->lockForUpdate();
        }

        // Fetch all matching codes and find the max number in PHP
        // This is database-agnostic (works with SQLite, MySQL, PostgreSQL)
        $codes = $query->pluck('code');

        $maxNumber = 0;
        $patternRegex = '/^'.preg_quote($prefix, '/').'-(\d+)$/';

        foreach ($codes as $code) {
            if (preg_match($patternRegex, $code, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxNumber) {
                    $maxNumber = $num;
                }
            }
        }

        return sprintf('%s-%03d', $prefix, $maxNumber + 1);
    }

    /**
     * Format item for response.
     *
     * @return array<string, mixed>
     */
    protected function formatItem(mixed $item, array $config): array
    {
        $output = ['id' => $item->id];

        // Add code if available
        if ($config['code_field'] && isset($item->{$config['code_field']})) {
            $output[$config['code_field']] = $item->{$config['code_field']};
        }

        // Add name/title
        if ($config['name_field'] && isset($item->{$config['name_field']})) {
            $output[$config['name_field']] = $item->{$config['name_field']};
        }

        // Add other fields
        foreach (array_keys($config['create_fields'] ?? []) as $field) {
            if (! isset($output[$field]) && isset($item->{$field})) {
                $value = $item->{$field};

                // Handle enums
                if (is_object($value) && method_exists($value, 'value')) {
                    $value = $value->value;
                }

                // Handle dates
                if ($value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d');
                }

                $output[$field] = $value;
            }
        }

        // Add timestamps
        if (isset($item->created_at)) {
            $output['created_at'] = $item->created_at->toIso8601String();
        }
        if (isset($item->updated_at)) {
            $output['updated_at'] = $item->updated_at->toIso8601String();
        }

        // Add URL
        $output['url'] = url($config['url_path'].'/'.$item->id);

        return $output;
    }

    /**
     * Format a single item for list output.
     *
     * @return array<string, mixed>
     */
    protected function formatListItem(mixed $item, array $config): array
    {
        $output = [];

        // Add base fields
        foreach ($config['list_fields'] as $field) {
            $value = $item->{$field};

            // Handle enums
            if (is_object($value) && method_exists($value, 'value')) {
                $value = $value->value;
            }

            // Handle dates
            if ($value instanceof \DateTimeInterface) {
                $value = $value->format('Y-m-d');
            }

            // Truncate long text fields
            if (is_string($value) && strlen($value) > 300) {
                $value = Str::limit(strip_tags($value), 300);
            }

            $output[$field] = $value;
        }

        // Add relation data
        foreach ($config['list_relations'] as $relation) {
            $related = $item->{$relation};
            if ($related) {
                $relatedName = Str::snake($relation);
                if ($related instanceof \Illuminate\Database\Eloquent\Model) {
                    $output[$relatedName] = [
                        'id' => $related->id,
                        'name' => $related->name ?? $related->title ?? $related->code ?? null,
                    ];
                }
            }
        }

        // Add counts
        foreach ($config['list_counts'] as $countRelation) {
            $countField = Str::snake($countRelation).'_count';
            $output[$countField] = $item->{$countField} ?? 0;
        }

        // Add URL
        $output['url'] = url($config['url_path'].'/'.$item->id);

        return $output;
    }

    /**
     * Format a single item for detail output.
     *
     * @return array<string, mixed>
     */
    protected function formatDetailItem(mixed $item, array $config): array
    {
        $output = [];

        // Get all model attributes (from the loaded data)
        $attributes = array_keys($item->getAttributes());

        foreach ($attributes as $field) {
            $value = $item->{$field};

            // Handle enums
            if (is_object($value) && method_exists($value, 'value')) {
                $value = $value->value;
            }

            // Handle dates
            if ($value instanceof \DateTimeInterface) {
                $value = $value->toIso8601String();
            }

            $output[$field] = $value;
        }

        // Add relation data
        foreach ($config['detail_relations'] as $relation) {
            $related = $item->{$relation};
            $relatedName = Str::snake($relation);

            if ($related === null) {
                continue;
            }

            if ($related instanceof \Illuminate\Database\Eloquent\Collection) {
                $output[$relatedName] = $related->map(function ($r) {
                    return $this->formatRelatedItem($r);
                })->toArray();
            } elseif ($related instanceof \Illuminate\Database\Eloquent\Model) {
                $output[$relatedName] = [
                    'id' => $related->id,
                    'name' => $related->name ?? $related->title ?? $related->code ?? null,
                ];
            }
        }

        // Add URL
        $output['url'] = url($config['url_path'].'/'.$item->id);

        return $output;
    }

    /**
     * Format a related item for output.
     *
     * @return array<string, mixed>
     */
    protected function formatRelatedItem(mixed $item): array
    {
        $output = [
            'id' => $item->id,
        ];

        // Try common name fields
        foreach (['name', 'title', 'code'] as $field) {
            if (isset($item->{$field})) {
                $output[$field] = $item->{$field};
                break;
            }
        }

        // Add URL if we can determine the path
        $className = class_basename($item);
        $urlPath = Str::plural(Str::kebab($className));
        $output['url'] = url("/app/{$urlPath}/{$item->id}");

        return $output;
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, \Illuminate\Contracts\JsonSchema\JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        $config = EntityConfig::get($this->entityType());
        $createFields = $config['create_fields'] ?? [];
        $updateFields = $config['update_fields'] ?? [];
        $fieldDescriptions = $config['field_descriptions'] ?? [];

        // Build field descriptions for the data object
        $fieldDocs = [];
        foreach ($createFields as $field => $fieldConfig) {
            $desc = $fieldDescriptions[$field] ?? ucfirst(str_replace('_', ' ', $field));
            $required = ($fieldConfig['required'] ?? false) ? ' (required for create)' : '';
            $fieldDocs[] = "- {$field}: {$desc}{$required}";
        }

        $dataDescription = "The entity data. Available fields:\n".implode("\n", $fieldDocs);

        return [
            'action' => $schema->string()
                ->enum(['list', 'get', 'create', 'update', 'delete'])
                ->description('The action to perform: list (paginated), get (by id), create, update, or delete.')
                ->required(),

            'id' => $schema->integer()
                ->description('The database ID of the entity (required for get, update, and delete actions).'),

            'page' => $schema->integer()
                ->description('Page number for list action (default: 1). Each page contains 50 items.'),

            'data' => $schema->object()
                ->description($dataDescription),

            'confirm' => $schema->boolean()
                ->description('Must be set to true to confirm delete action.'),
        ];
    }
}
