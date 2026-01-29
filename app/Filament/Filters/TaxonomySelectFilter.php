<?php

namespace App\Filament\Filters;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Filament\Tables\Filters\SelectFilter;

class TaxonomySelectFilter extends SelectFilter
{
    protected string $taxonomyType = '';

    /**
     * Runtime cache for parent taxonomy lookups within a single request.
     *
     * @var array<string, Taxonomy|null>
     */
    protected static array $parentTaxonomyCache = [];

    public static function make(?string $name = null): static
    {
        $taxonomyType = $name ?? '';

        $filter = parent::make($name);

        $filter->taxonomyType = $taxonomyType;
        $filter->label(ucfirst($taxonomyType));

        return $filter;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->configureTaxonomyOptions();
        $this->configureTaxonomyQuery();
    }

    protected function configureTaxonomyOptions(): void
    {
        $this->options(function () {
            $taxonomy = $this->getParentTaxonomy($this->taxonomyType);

            if (! $taxonomy) {
                return [];
            }

            return Taxonomy::where('parent_id', $taxonomy->id)
                ->orderBy('name')
                ->pluck('name', 'id')
                ->toArray();
        });
    }

    protected function configureTaxonomyQuery(): void
    {
        $this->query(function ($query, array $data) {
            if (! $data['value']) {
                return;
            }

            $query->whereHas('taxonomies', function ($query) use ($data) {
                $query->where('taxonomy_id', $data['value']);
            });
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
