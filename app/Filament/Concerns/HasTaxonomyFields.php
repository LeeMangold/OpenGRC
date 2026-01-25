<?php

namespace App\Filament\Concerns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Filament\Forms\Components\Select;

/**
 * Trait for easily adding taxonomy selects to Filament forms using polymorphic relationships
 *
 * Usage in your Filament Resource:
 *
 * use App\Filament\Concerns\HasTaxonomyFields;
 *
 * class YourResource extends Resource
 * {
 *     use HasTaxonomyFields;
 *
 *     public static function form(Form $form): Form
 *     {
 *         return $form->schema([
 *             // Single selection using slug (recommended - won't break if name changes)
 *             self::taxonomySelect('Department', 'department', required: true),
 *
 *             // Multiple selection
 *             self::taxonomySelect('Scope', 'scope', multiple: true),
 *
 *             // Hierarchical display
 *             self::hierarchicalTaxonomySelect('Risk Level', 'risk-level'),
 *         ]);
 *     }
 * }
 */
trait HasTaxonomyFields
{
    /**
     * Get a parent taxonomy by type, trying multiple slug variations.
     * This handles cases where slugs might have been changed.
     *
     * @param  string  $type  The taxonomy type (e.g., 'department', 'scope')
     */
    protected static function getParentTaxonomy(string $type): ?Taxonomy
    {
        // Try exact slug match first
        $taxonomy = Taxonomy::where('slug', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        // Try plural version
        $taxonomy = Taxonomy::where('slug', $type.'s')
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        // Try by type field as fallback
        $taxonomy = Taxonomy::where('type', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        // Try plural type
        $taxonomy = Taxonomy::where('type', $type.'s')
            ->whereNull('parent_id')
            ->first();

        return $taxonomy;
    }

    /**
     * Create a select field for taxonomy terms using polymorphic relationships
     *
     * @param  string  $label  The display label (e.g., 'Department', 'Scope')
     * @param  string  $taxonomyType  The type identifier (e.g., 'department', 'scope')
     * @param  string  $fieldName  The form field name (defaults to taxonomy type)
     * @param  bool  $multiple  Allow multiple selections
     * @param  bool  $required  Is the field required
     */
    public static function taxonomySelect(
        string $label,
        string $taxonomyType,
        ?string $fieldName = null,
        bool $multiple = false,
        bool $required = false
    ): Select {
        $fieldName = $fieldName ?: $taxonomyType;

        $select = Select::make($fieldName)
            ->label($label)
            ->options(function () use ($taxonomyType) {
                // Find the root taxonomy
                $taxonomy = self::getParentTaxonomy($taxonomyType);

                if (! $taxonomy) {
                    return [];
                }

                // Get children (terms) of this taxonomy
                return Taxonomy::where('parent_id', $taxonomy->id)
                    ->orderBy('name')
                    ->pluck('name', 'id')
                    ->toArray();
            })
            ->afterStateHydrated(function (Select $component, $state, $record) use ($taxonomyType) {
                if (! $record) {
                    return;
                }

                // Find the taxonomy type
                $taxonomy = self::getParentTaxonomy($taxonomyType);

                if (! $taxonomy) {
                    return;
                }

                // Get the current taxonomy term for this type
                $currentTerm = $record->taxonomies()
                    ->where('parent_id', $taxonomy->id)
                    ->first();

                $component->state($currentTerm?->id);
            })
            ->saveRelationshipsUsing(function (Select $component, $state) use ($taxonomyType) {
                $record = $component->getRecord();
                if (! $record || ! $state) {
                    return;
                }

                // Find the taxonomy type
                $taxonomy = self::getParentTaxonomy($taxonomyType);

                if (! $taxonomy) {
                    return;
                }

                // Detach any existing terms of this taxonomy type
                $existingTermIds = Taxonomy::where('parent_id', $taxonomy->id)->pluck('id');
                $record->taxonomies()->detach($existingTermIds);

                // Attach the new term
                $record->taxonomies()->attach($state);
            })
            ->dehydrated(false)
            ->searchable()
            ->preload();

        if ($multiple) {
            $select->multiple();
        }

        if ($required) {
            $select->required();
        }

        return $select;
    }

    /**
     * Create a select field for hierarchical taxonomy terms using polymorphic relationships
     *
     * @param  string  $label  The display label
     * @param  string  $taxonomySlug  The slug of the parent taxonomy
     * @param  string  $fieldName  The form field name (defaults to taxonomy slug)
     * @param  bool  $multiple  Allow multiple selections
     * @param  bool  $required  Is the field required
     */
    public static function hierarchicalTaxonomySelect(
        string $label,
        string $taxonomyType,
        ?string $fieldName = null,
        bool $multiple = false,
        bool $required = false
    ): Select {
        $fieldName = $fieldName ?: $taxonomyType;

        $select = Select::make($fieldName)
            ->label($label)
            ->relationship(
                name: 'taxonomies',
                titleAttribute: 'name',
                modifyQueryUsing: function ($query) use ($taxonomyType) {
                    // Find the root taxonomy
                    $taxonomy = self::getParentTaxonomy($taxonomyType);

                    if (! $taxonomy) {
                        return $query->whereRaw('1 = 0'); // Return empty result
                    }

                    // Only show children (terms) of this taxonomy
                    return $query->where('parent_id', $taxonomy->id)
                        ->with('parent')
                        ->orderBy('name');
                }
            )
            ->getOptionLabelFromRecordUsing(function (Taxonomy $record) {
                // Show hierarchical format: Parent → Child
                return $record->parent
                    ? $record->parent->name.' → '.$record->name
                    : $record->name;
            })
            ->searchable()
            ->preload();

        if ($multiple) {
            $select->multiple();
        }

        if ($required) {
            $select->required();
        }

        return $select;
    }

    /**
     * Handle saving taxonomy relationships from form data
     *
     * @param  Model  $record  The model instance
     * @param  array  $data  The form data
     */
    public static function saveTaxonomyRelationships($record, array $data): void
    {
        // List of known taxonomy field names and their corresponding taxonomy types
        $taxonomyFields = [
            'department' => 'department',
            'scope' => 'scope',
            // Add more as needed
        ];

        foreach ($taxonomyFields as $fieldName => $taxonomyType) {
            if (! isset($data[$fieldName]) || ! $data[$fieldName]) {
                continue;
            }

            $value = $data[$fieldName];

            // Find the root taxonomy
            $taxonomy = self::getParentTaxonomy($taxonomyType);

            if (! $taxonomy) {
                continue;
            }

            // Detach any existing terms of this taxonomy type
            $existingTermIds = Taxonomy::where('parent_id', $taxonomy->id)->pluck('id');
            $record->taxonomies()->detach($existingTermIds);

            // Attach the new term(s)
            if (is_array($value)) {
                $record->taxonomies()->attach($value);
            } else {
                $record->taxonomies()->attach($value);
            }
        }
    }

    /**
     * Get a taxonomy term for a record by the parent taxonomy type
     *
     * @param  Model  $record  The model instance
     * @param  string  $taxonomyType  The type identifier of the parent taxonomy
     */
    public static function getTaxonomyTerm($record, string $taxonomyType): ?Taxonomy
    {
        $parent = self::getParentTaxonomy($taxonomyType);

        if (! $parent) {
            return null;
        }

        return $record->taxonomies()
            ->where('parent_id', $parent->id)
            ->first();
    }
}
