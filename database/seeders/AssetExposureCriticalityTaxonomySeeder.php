<?php

namespace Database\Seeders;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetExposureCriticalityTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAssetExposureTaxonomy();
        $this->createAssetCriticalityTaxonomy();
    }

    /**
     * Create Asset Exposure taxonomy with hierarchical terms.
     */
    private function createAssetExposureTaxonomy(): void
    {
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-exposure',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Exposure',
                'description' => 'Network exposure level of assets',
                'sort_order' => 6,
            ]
        );

        $exposures = [
            ['name' => 'External', 'description' => 'Asset is exposed to external networks'],
            ['name' => 'Internal', 'description' => 'Asset is only accessible on internal networks'],
        ];

        foreach ($exposures as $index => $data) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => 'exposure-'.Str::slug($data['name']),
                    'type' => 'asset',
                    'parent_id' => $parent->id,
                ],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    /**
     * Create Asset Criticality taxonomy with hierarchical terms.
     */
    private function createAssetCriticalityTaxonomy(): void
    {
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-criticality',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Criticality',
                'description' => 'Business criticality level of assets',
                'sort_order' => 7,
            ]
        );

        $levels = [
            ['name' => 'Low', 'description' => 'Low business criticality'],
            ['name' => 'Medium', 'description' => 'Medium business criticality'],
            ['name' => 'High', 'description' => 'High business criticality'],
            ['name' => 'Critical', 'description' => 'Critical to business operations'],
        ];

        foreach ($levels as $index => $data) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => 'criticality-'.Str::slug($data['name']),
                    'type' => 'asset',
                    'parent_id' => $parent->id,
                ],
                [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'sort_order' => $index + 1,
                ]
            );
        }
    }
}
