<?php

namespace Database\Seeders;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AssetCategoryLocationSupplierTaxonomySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->createAssetCategoryTaxonomy();
        $this->createAssetLocationTaxonomy();
        $this->createAssetSupplierTaxonomy();
    }

    /**
     * Create Asset Category taxonomy with hierarchical terms.
     */
    private function createAssetCategoryTaxonomy(): void
    {
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-category',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Category',
                'description' => 'Category classification of assets',
                'sort_order' => 8,
            ]
        );

        $categories = [
            ['name' => 'Hardware', 'description' => 'Physical computing hardware'],
            ['name' => 'Software', 'description' => 'Software applications and licenses'],
            ['name' => 'Network Equipment', 'description' => 'Networking devices and infrastructure'],
            ['name' => 'Peripheral', 'description' => 'Peripheral devices and accessories'],
            ['name' => 'Office Equipment', 'description' => 'General office equipment'],
            ['name' => 'Other', 'description' => 'Other asset categories'],
        ];

        foreach ($categories as $index => $data) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => 'asset-category-'.Str::slug($data['name']),
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
     * Create Asset Location taxonomy with hierarchical terms.
     */
    private function createAssetLocationTaxonomy(): void
    {
        $parent = Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-location',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Location',
                'description' => 'Physical or logical location of assets',
                'sort_order' => 9,
            ]
        );

        $locations = [
            ['name' => 'Main Office', 'description' => 'Primary office location'],
            ['name' => 'Branch Office', 'description' => 'Branch or satellite office'],
            ['name' => 'Data Center', 'description' => 'Data center facility'],
            ['name' => 'Remote', 'description' => 'Remote or off-site location'],
            ['name' => 'Warehouse', 'description' => 'Storage or warehouse facility'],
            ['name' => 'Cloud', 'description' => 'Cloud-hosted or virtual location'],
        ];

        foreach ($locations as $index => $data) {
            Taxonomy::firstOrCreate(
                [
                    'slug' => 'asset-location-'.Str::slug($data['name']),
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
     * Create Asset Supplier taxonomy (parent only, children are org-specific).
     */
    private function createAssetSupplierTaxonomy(): void
    {
        Taxonomy::firstOrCreate(
            [
                'slug' => 'asset-supplier',
                'type' => 'asset',
            ],
            [
                'name' => 'Asset Supplier',
                'description' => 'Suppliers and vendors for assets',
                'sort_order' => 10,
            ]
        );
    }
}
