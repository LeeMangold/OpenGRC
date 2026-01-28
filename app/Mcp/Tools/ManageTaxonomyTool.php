<?php

namespace App\Mcp\Tools;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Exception;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Tool;

/**
 * Tool for managing Taxonomy types and terms.
 *
 * Provides operations to:
 * - List taxonomy types (root taxonomies like "Department", "Scope", "Policy Status")
 * - List terms within a taxonomy type (e.g., all departments)
 * - Get a specific term by ID or by name within a type
 * - Create, update, and delete taxonomy terms
 *
 * This is essential for LLMs to discover valid values for taxonomy fields
 * (like department_id, scope_id, status_id) when creating/updating entities.
 */
class ManageTaxonomyTool extends Tool
{
    protected string $name = 'ManageTaxonomy';

    protected string $description = 'Manage taxonomy types and terms: list_types, list_terms, get, create, update, delete.';

    /**
     * Protected system taxonomy slugs that cannot be deleted.
     */
    protected const PROTECTED_TAXONOMY_SLUGS = [
        'scope',
        'department',
        'policy-status',
        'asset-type',
        'asset-status',
        'asset-condition',
        'compliance-status',
        'data-classification',
    ];

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $validated = $request->validate([
            'action' => 'required|string|in:list_types,list_terms,get,create,update,delete',
            'id' => 'nullable|integer',
            'type' => 'nullable|string',
            'name' => 'nullable|string',
            'data' => 'nullable|array',
            'confirm' => 'nullable|boolean',
        ]);

        $user = $request->user();
        if (! $user) {
            return $this->errorResponse('Authentication required.');
        }

        return match ($validated['action']) {
            'list_types' => $this->handleListTypes(),
            'list_terms' => $this->handleListTerms($validated),
            'get' => $this->handleGet($validated),
            'create' => $this->handleCreate($validated, $user),
            'update' => $this->handleUpdate($validated, $user),
            'delete' => $this->handleDelete($validated, $user),
        };
    }

    /**
     * List all taxonomy types (root taxonomies where parent_id is null).
     */
    protected function handleListTypes(): Response
    {
        $types = Taxonomy::whereNull('parent_id')
            ->orderBy('name')
            ->get();

        $formattedTypes = $types->map(function ($type) {
            $termsCount = Taxonomy::where('parent_id', $type->id)->count();

            return [
                'id' => $type->id,
                'name' => $type->name,
                'slug' => $type->slug,
                'description' => $type->description,
                'terms_count' => $termsCount,
                'is_protected' => in_array($type->slug, self::PROTECTED_TAXONOMY_SLUGS),
            ];
        })->toArray();

        return $this->successResponse([
            'action' => 'list_types',
            'total' => count($formattedTypes),
            'taxonomy_types' => $formattedTypes,
            'hint' => 'Use action="list_terms" with type="<slug>" to see terms within a taxonomy type.',
        ]);
    }

    /**
     * List terms within a specific taxonomy type.
     */
    protected function handleListTerms(array $validated): Response
    {
        if (empty($validated['type'])) {
            return $this->errorResponse('The "type" parameter is required for list_terms action. Use the slug from list_types (e.g., "department", "scope", "policy-status").');
        }

        $typeSlug = $validated['type'];

        // Find the parent taxonomy type
        $parentType = Taxonomy::whereNull('parent_id')
            ->where(function ($q) use ($typeSlug) {
                $q->where('slug', $typeSlug)
                    ->orWhere('name', $typeSlug);
            })
            ->first();

        if (! $parentType) {
            $availableTypes = Taxonomy::whereNull('parent_id')
                ->pluck('slug')
                ->toArray();

            return $this->errorResponse(
                "Taxonomy type '{$typeSlug}' not found.",
                ['available_types' => $availableTypes]
            );
        }

        $terms = Taxonomy::where('parent_id', $parentType->id)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $formattedTerms = $terms->map(function ($term) {
            return [
                'id' => $term->id,
                'name' => $term->name,
                'slug' => $term->slug,
                'description' => $term->description,
                'sort_order' => $term->sort_order,
            ];
        })->toArray();

        return $this->successResponse([
            'action' => 'list_terms',
            'taxonomy_type' => [
                'id' => $parentType->id,
                'name' => $parentType->name,
                'slug' => $parentType->slug,
            ],
            'total' => count($formattedTerms),
            'terms' => $formattedTerms,
            'hint' => 'Use the term "id" when setting fields like department_id, scope_id, or status_id.',
        ]);
    }

    /**
     * Get a specific taxonomy term by ID or by type+name.
     */
    protected function handleGet(array $validated): Response
    {
        // Get by ID
        if (! empty($validated['id'])) {
            $term = Taxonomy::with('parent')->find($validated['id']);

            if (! $term) {
                return $this->errorResponse("Taxonomy term with ID {$validated['id']} not found.");
            }

            return $this->successResponse([
                'action' => 'get',
                'term' => $this->formatTerm($term),
            ]);
        }

        // Get by type + name
        if (! empty($validated['type']) && ! empty($validated['name'])) {
            $typeSlug = $validated['type'];
            $termName = $validated['name'];

            $parentType = Taxonomy::whereNull('parent_id')
                ->where(function ($q) use ($typeSlug) {
                    $q->where('slug', $typeSlug)
                        ->orWhere('name', $typeSlug);
                })
                ->first();

            if (! $parentType) {
                return $this->errorResponse("Taxonomy type '{$typeSlug}' not found.");
            }

            $term = Taxonomy::where('parent_id', $parentType->id)
                ->where(function ($q) use ($termName) {
                    $q->where('name', $termName)
                        ->orWhere('slug', Str::slug($termName));
                })
                ->first();

            if (! $term) {
                // Provide helpful suggestions
                $availableTerms = Taxonomy::where('parent_id', $parentType->id)
                    ->pluck('name')
                    ->toArray();

                return $this->errorResponse(
                    "Term '{$termName}' not found in taxonomy type '{$parentType->name}'.",
                    ['available_terms' => $availableTerms]
                );
            }

            $term->setRelation('parent', $parentType);

            return $this->successResponse([
                'action' => 'get',
                'term' => $this->formatTerm($term),
                'hint' => "Use id={$term->id} when setting fields like department_id, scope_id, or status_id.",
            ]);
        }

        return $this->errorResponse('Either "id" or both "type" and "name" are required for get action.');
    }

    /**
     * Create a new taxonomy term.
     */
    protected function handleCreate(array $validated, $user): Response
    {
        if (! $user->can('Create Taxonomies')) {
            return $this->errorResponse('You do not have permission to create taxonomy terms.', [
                'required_permission' => 'Create Taxonomies',
            ]);
        }

        if (empty($validated['type'])) {
            return $this->errorResponse('The "type" parameter is required for create action. Use the slug of the parent taxonomy type.');
        }

        if (empty($validated['data']) || empty($validated['data']['name'])) {
            return $this->errorResponse('The data.name field is required for create action.');
        }

        $typeSlug = $validated['type'];
        $data = $validated['data'];

        // Find parent taxonomy type
        $parentType = Taxonomy::whereNull('parent_id')
            ->where(function ($q) use ($typeSlug) {
                $q->where('slug', $typeSlug)
                    ->orWhere('name', $typeSlug);
            })
            ->first();

        if (! $parentType) {
            return $this->errorResponse("Taxonomy type '{$typeSlug}' not found.");
        }

        // Check for duplicate name within the same parent
        $existingTerm = Taxonomy::where('parent_id', $parentType->id)
            ->where('name', $data['name'])
            ->first();

        if ($existingTerm) {
            return $this->errorResponse(
                "A term with name '{$data['name']}' already exists in '{$parentType->name}'.",
                ['existing_term_id' => $existingTerm->id]
            );
        }

        try {
            $term = DB::transaction(function () use ($parentType, $data) {
                // Get the max sort order for terms under this parent
                $maxSortOrder = Taxonomy::where('parent_id', $parentType->id)->max('sort_order') ?? 0;

                return Taxonomy::create([
                    'name' => $data['name'],
                    'slug' => $data['slug'] ?? Str::slug($data['name']),
                    'description' => $data['description'] ?? null,
                    'parent_id' => $parentType->id,
                    'sort_order' => $data['sort_order'] ?? ($maxSortOrder + 1),
                ]);
            });

            $term->setRelation('parent', $parentType);

            return $this->successResponse([
                'action' => 'created',
                'message' => "Taxonomy term '{$term->name}' created successfully in '{$parentType->name}'.",
                'term' => $this->formatTerm($term),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to create taxonomy term: '.$e->getMessage());
        }
    }

    /**
     * Update an existing taxonomy term.
     */
    protected function handleUpdate(array $validated, $user): Response
    {
        if (! $user->can('Update Taxonomies')) {
            return $this->errorResponse('You do not have permission to update taxonomy terms.', [
                'required_permission' => 'Update Taxonomies',
            ]);
        }

        if (empty($validated['id'])) {
            return $this->errorResponse('The "id" parameter is required for update action.');
        }

        if (empty($validated['data'])) {
            return $this->errorResponse('The "data" parameter is required for update action.');
        }

        $term = Taxonomy::with('parent')->find($validated['id']);

        if (! $term) {
            return $this->errorResponse("Taxonomy term with ID {$validated['id']} not found.");
        }

        // Prevent updating protected root taxonomy types
        if ($term->parent_id === null && in_array($term->slug, self::PROTECTED_TAXONOMY_SLUGS)) {
            return $this->errorResponse(
                "Cannot update protected system taxonomy type '{$term->name}'.",
                ['is_protected' => true]
            );
        }

        $data = $validated['data'];
        $allowedFields = ['name', 'description', 'sort_order'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            return $this->errorResponse(
                'No valid fields to update.',
                ['allowed_fields' => $allowedFields]
            );
        }

        // Check for duplicate name if updating name
        if (isset($updateData['name']) && $term->parent_id !== null) {
            $existingTerm = Taxonomy::where('parent_id', $term->parent_id)
                ->where('name', $updateData['name'])
                ->where('id', '!=', $term->id)
                ->first();

            if ($existingTerm) {
                return $this->errorResponse(
                    "A term with name '{$updateData['name']}' already exists in this taxonomy type.",
                    ['existing_term_id' => $existingTerm->id]
                );
            }
        }

        try {
            $term->update($updateData);
            $term->refresh();
            $term->load('parent');

            return $this->successResponse([
                'action' => 'updated',
                'message' => "Taxonomy term '{$term->name}' updated successfully.",
                'updated_fields' => array_keys($updateData),
                'term' => $this->formatTerm($term),
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to update taxonomy term: '.$e->getMessage());
        }
    }

    /**
     * Delete a taxonomy term.
     */
    protected function handleDelete(array $validated, $user): Response
    {
        if (! $user->can('Delete Taxonomies')) {
            return $this->errorResponse('You do not have permission to delete taxonomy terms.', [
                'required_permission' => 'Delete Taxonomies',
            ]);
        }

        if (empty($validated['id'])) {
            return $this->errorResponse('The "id" parameter is required for delete action.');
        }

        if (empty($validated['confirm']) || $validated['confirm'] !== true) {
            return $this->errorResponse('Delete operation not confirmed. Set confirm: true to proceed.');
        }

        $term = Taxonomy::with('parent', 'children')->find($validated['id']);

        if (! $term) {
            return $this->errorResponse("Taxonomy term with ID {$validated['id']} not found.");
        }

        // Prevent deleting protected taxonomy types
        if (in_array($term->slug, self::PROTECTED_TAXONOMY_SLUGS)) {
            return $this->errorResponse(
                "Cannot delete protected system taxonomy '{$term->name}'.",
                ['is_protected' => true]
            );
        }

        // Prevent deleting taxonomy types (root terms) that have children
        if ($term->parent_id === null && $term->children->isNotEmpty()) {
            return $this->errorResponse(
                "Cannot delete taxonomy type '{$term->name}' because it has {$term->children->count()} terms. Delete the terms first.",
                ['children_count' => $term->children->count()]
            );
        }

        $termName = $term->name;
        $termId = $term->id;
        $parentName = $term->parent?->name ?? 'root';

        try {
            $term->delete();

            return $this->successResponse([
                'action' => 'deleted',
                'message' => "Taxonomy term '{$termName}' (ID: {$termId}) has been deleted from '{$parentName}'.",
                'soft_deleted' => true,
                'restorable' => true,
            ]);
        } catch (Exception $e) {
            return $this->errorResponse('Failed to delete taxonomy term: '.$e->getMessage());
        }
    }

    /**
     * Format a taxonomy term for response.
     *
     * @return array<string, mixed>
     */
    protected function formatTerm(Taxonomy $term): array
    {
        $output = [
            'id' => $term->id,
            'name' => $term->name,
            'slug' => $term->slug,
            'description' => $term->description,
            'sort_order' => $term->sort_order,
        ];

        if ($term->parent) {
            $output['taxonomy_type'] = [
                'id' => $term->parent->id,
                'name' => $term->parent->name,
                'slug' => $term->parent->slug,
            ];
        } else {
            $output['is_root_type'] = true;
            $output['is_protected'] = in_array($term->slug, self::PROTECTED_TAXONOMY_SLUGS);
        }

        $output['created_at'] = $term->created_at?->toIso8601String();
        $output['updated_at'] = $term->updated_at?->toIso8601String();

        return $output;
    }

    /**
     * Return a success response.
     *
     * @param  array<string, mixed>  $data
     */
    protected function successResponse(array $data): Response
    {
        return Response::text(json_encode(
            array_merge(['success' => true], $data),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
    }

    /**
     * Return an error response.
     *
     * @param  array<string, mixed>  $extra
     */
    protected function errorResponse(string $message, array $extra = []): Response
    {
        return Response::text(json_encode(
            array_merge(['success' => false, 'error' => $message], $extra),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ));
    }

    /**
     * Get the tool's input schema.
     *
     * @return array<string, JsonSchema>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'action' => $schema->string()
                ->enum(['list_types', 'list_terms', 'get', 'create', 'update', 'delete'])
                ->description('The action to perform: list_types (see taxonomy types like Department, Scope), list_terms (see terms within a type), get (find specific term by id or by type+name), create (add new term), update, or delete.')
                ->required(),

            'id' => $schema->integer()
                ->description('The taxonomy term ID. Required for get (by ID), update, and delete actions.'),

            'type' => $schema->string()
                ->description('The taxonomy type slug (e.g., "department", "scope", "policy-status"). Required for list_terms, get (by name), and create actions.'),

            'name' => $schema->string()
                ->description('The term name to look up. Used with "type" for get action to find a term by name.'),

            'data' => $schema->object()
                ->description('Term data for create/update. Fields: name (required for create), description (optional), sort_order (optional).'),

            'confirm' => $schema->boolean()
                ->description('Must be set to true to confirm delete action.'),
        ];
    }
}
