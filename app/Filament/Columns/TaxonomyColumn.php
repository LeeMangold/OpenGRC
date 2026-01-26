<?php

namespace App\Filament\Columns;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;

class TaxonomyColumn extends TextColumn
{
    protected string $taxonomyType = '';

    protected string $notAssignedText = 'Not assigned';

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

            $term = $record->taxonomies()
                ->where('parent_id', $parent->id)
                ->first();

            return $term?->name ?? $this->notAssignedText;
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
        $taxonomy = Taxonomy::where('slug', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        $taxonomy = Taxonomy::where('slug', $type.'s')
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        $taxonomy = Taxonomy::where('type', $type)
            ->whereNull('parent_id')
            ->first();

        if ($taxonomy) {
            return $taxonomy;
        }

        return Taxonomy::where('type', $type.'s')
            ->whereNull('parent_id')
            ->first();
    }
}
