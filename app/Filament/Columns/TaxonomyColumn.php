<?php

namespace App\Filament\Columns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class TaxonomyColumn extends TextColumn
{
    protected string $taxonomyType = '';

    protected string $notAssignedText = 'Not assigned';

    /**
     * Runtime cache for parent taxonomy lookups within a single request.
     *
     * @var array<string, Taxonomy|null>
     */
    protected static array $parentTaxonomyCache = [];

    public static function make(?string $name = null): static
    {
        $taxonomyType = $name ?? '';
        // Use a unique column name to avoid conflicts with filters
        $columnName = 'tax_col_'.$taxonomyType;

        $column = parent::make($columnName);

        $column->taxonomyType = $taxonomyType;
        $column->label(ucfirst($taxonomyType));

        return $column;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTaxonomyDisplay();
        $this->configureTaxonomySorting();
        $this->configureTaxonomySearching();
    }

    public function notAssignedText(string $text): static
    {
        $this->notAssignedText = $text;

        return $this;
    }

    protected function configureTaxonomyDisplay(): void
    {
        $this->getStateUsing(function (Model $record): string {
            $parent = $this->getParentTaxonomy($this->taxonomyType);

            if (! $parent) {
                return $this->notAssignedText;
            }

            // Use the already-loaded taxonomies relation if available (eager loaded)
            // to avoid N+1 queries
            if ($record->relationLoaded('taxonomies')) {
                $term = $record->taxonomies->firstWhere('parent_id', $parent->id);
            } else {
                $term = $record->taxonomies()
                    ->where('parent_id', $parent->id)
                    ->first();
            }

            return $term?->name ?? $this->notAssignedText;
        });
    }

    protected function configureTaxonomySearching(): void
    {
        $this->searchable(query: function ($query, string $search) {
            $parentTaxonomy = $this->getParentTaxonomy($this->taxonomyType);

            if (! $parentTaxonomy) {
                return $query;
            }

            return $query->whereHas('taxonomies', function ($q) use ($parentTaxonomy, $search) {
                $q->where('parent_id', $parentTaxonomy->id)
                    ->where('name', 'like', "%{$search}%");
            });
        });
    }

    protected function configureTaxonomySorting(): void
    {
        $this->sortable(query: function ($query, string $direction) {
            // Sanitize direction to prevent SQL injection
            $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';

            $parentTaxonomy = $this->getParentTaxonomy($this->taxonomyType);

            if (! $parentTaxonomy) {
                return $query;
            }

            $table = $this->getTable();
            $model = $table->getModel();
            $tableName = (new $model)->getTable();

            $taxonomablesAlias = $this->taxonomyType.'_taxonomables';
            $taxonomiesAlias = $this->taxonomyType.'_taxonomies';

            return $query->reorder()
                ->leftJoin("taxonomables as {$taxonomablesAlias}", function ($join) use ($tableName, $taxonomablesAlias, $model) {
                    $join->on("{$tableName}.id", '=', "{$taxonomablesAlias}.taxonomable_id")
                        ->where("{$taxonomablesAlias}.taxonomable_type", '=', $model);
                })
                ->leftJoin("taxonomies as {$taxonomiesAlias}", function ($join) use ($taxonomablesAlias, $taxonomiesAlias, $parentTaxonomy) {
                    $join->on("{$taxonomablesAlias}.taxonomy_id", '=', "{$taxonomiesAlias}.id")
                        ->where("{$taxonomiesAlias}.parent_id", '=', $parentTaxonomy->id);
                })
                ->groupBy("{$tableName}.id")
                ->orderByRaw("MAX({$taxonomiesAlias}.name) {$direction}")
                ->select("{$tableName}.*");
        });
    }

    protected function getParentTaxonomy(string $type): ?Taxonomy
    {
        // Check runtime cache first
        if (array_key_exists($type, static::$parentTaxonomyCache)) {
            return static::$parentTaxonomyCache[$type];
        }

        $taxonomy = Taxonomy::where('slug', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return static::$parentTaxonomyCache[$type] = $taxonomy;
        }

        $taxonomy = Taxonomy::where('slug', $type.'s')
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return static::$parentTaxonomyCache[$type] = $taxonomy;
        }

        $taxonomy = Taxonomy::where('type', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return static::$parentTaxonomyCache[$type] = $taxonomy;
        }

        $taxonomy = Taxonomy::where('type', $type.'s')
            ->whereNull('parent_id')
            ->first();

        return static::$parentTaxonomyCache[$type] = $taxonomy;
    }
}
