<?php

namespace Database\Seeders\Demo;

use Aliziodev\LaravelTaxonomy\Models\Taxonomy;
use App\Models\Asset;
use Illuminate\Database\Seeder;

class DemoAssetsSeeder extends Seeder
{
    public function __construct(private DemoContext $context) {}

    public function run(): void
    {
        // Get taxonomy IDs for asset types
        $assetTypeParent = Taxonomy::where('slug', 'asset-type')->where('type', 'asset')->first();
        $assetStatusParent = Taxonomy::where('slug', 'asset-status')->where('type', 'asset')->first();
        $assetConditionParent = Taxonomy::where('slug', 'asset-condition')->where('type', 'asset')->first();

        if (! $assetTypeParent || ! $assetStatusParent || ! $assetConditionParent) {
            return; // Skip if taxonomy not set up
        }

        $assetTypeIds = Taxonomy::where('parent_id', $assetTypeParent->id)->pluck('id', 'name');
        $assetStatusIds = Taxonomy::where('parent_id', $assetStatusParent->id)->pluck('id', 'name');
        $conditionIds = Taxonomy::where('parent_id', $assetConditionParent->id)->pluck('id', 'name');

        $assetsData = [
            // Laptops
            ['asset_tag' => 'LAP-001', 'name' => 'Dell Latitude 5540', 'asset_type_id' => $assetTypeIds->get('Laptop'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Good'), 'manufacturer' => 'Dell', 'model' => 'Latitude 5540', 'processor' => 'Intel Core i7-1365U', 'ram_gb' => 16, 'storage_capacity_gb' => 512, 'operating_system' => 'Windows 11 Pro'],
            ['asset_tag' => 'LAP-002', 'name' => 'MacBook Pro 14"', 'asset_type_id' => $assetTypeIds->get('Laptop'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Apple', 'model' => 'MacBook Pro 14-inch M3', 'processor' => 'Apple M3 Pro', 'ram_gb' => 32, 'storage_capacity_gb' => 1024, 'operating_system' => 'macOS Sonoma'],
            ['asset_tag' => 'LAP-003', 'name' => 'HP EliteBook 840 G10', 'asset_type_id' => $assetTypeIds->get('Laptop'), 'status_id' => $assetStatusIds->get('Available'), 'condition_id' => $conditionIds->get('Good'), 'manufacturer' => 'HP', 'model' => 'EliteBook 840 G10', 'processor' => 'Intel Core i5-1345U', 'ram_gb' => 16, 'storage_capacity_gb' => 256, 'operating_system' => 'Windows 11 Pro'],
            ['asset_tag' => 'LAP-004', 'name' => 'ThinkPad X1 Carbon Gen 11', 'asset_type_id' => $assetTypeIds->get('Laptop'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Lenovo', 'model' => 'ThinkPad X1 Carbon Gen 11', 'processor' => 'Intel Core i7-1365U', 'ram_gb' => 32, 'storage_capacity_gb' => 512, 'operating_system' => 'Windows 11 Pro'],

            // Servers
            ['asset_tag' => 'SRV-001', 'name' => 'Dell PowerEdge R760', 'asset_type_id' => $assetTypeIds->get('Server'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Dell', 'model' => 'PowerEdge R760', 'processor' => 'Dual Intel Xeon Gold 6438N', 'ram_gb' => 512, 'storage_capacity_gb' => 15360, 'operating_system' => 'VMware ESXi 8.0'],
            ['asset_tag' => 'SRV-002', 'name' => 'HPE ProLiant DL380 Gen11', 'asset_type_id' => $assetTypeIds->get('Server'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Good'), 'manufacturer' => 'HPE', 'model' => 'ProLiant DL380 Gen11', 'processor' => 'Dual Intel Xeon Silver 4416+', 'ram_gb' => 256, 'storage_capacity_gb' => 8192, 'operating_system' => 'Windows Server 2022'],

            // Network Equipment
            ['asset_tag' => 'NET-001', 'name' => 'Cisco Catalyst 9300', 'asset_type_id' => $assetTypeIds->get('Network Equipment'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Cisco', 'model' => 'Catalyst 9300-48P'],
            ['asset_tag' => 'NET-002', 'name' => 'Palo Alto PA-460', 'asset_type_id' => $assetTypeIds->get('Network Equipment'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Palo Alto Networks', 'model' => 'PA-460'],

            // Monitors
            ['asset_tag' => 'MON-001', 'name' => 'Dell UltraSharp U2723QE', 'asset_type_id' => $assetTypeIds->get('Monitor'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Good'), 'manufacturer' => 'Dell', 'model' => 'U2723QE', 'screen_size' => 27.0],
            ['asset_tag' => 'MON-002', 'name' => 'LG UltraFine 27UN850-W', 'asset_type_id' => $assetTypeIds->get('Monitor'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'LG', 'model' => '27UN850-W', 'screen_size' => 27.0],

            // Mobile Devices
            ['asset_tag' => 'PHN-001', 'name' => 'iPhone 15 Pro', 'asset_type_id' => $assetTypeIds->get('Phone'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Apple', 'model' => 'iPhone 15 Pro', 'storage_capacity_gb' => 256, 'operating_system' => 'iOS 17'],
            ['asset_tag' => 'TAB-001', 'name' => 'iPad Pro 12.9"', 'asset_type_id' => $assetTypeIds->get('Tablet'), 'status_id' => $assetStatusIds->get('In Use'), 'condition_id' => $conditionIds->get('Excellent'), 'manufacturer' => 'Apple', 'model' => 'iPad Pro 12.9-inch M2', 'storage_capacity_gb' => 512, 'operating_system' => 'iPadOS 17'],

            // Software Licenses
            ['asset_tag' => 'LIC-001', 'name' => 'Microsoft 365 E5', 'asset_type_id' => $assetTypeIds->get('Software License'), 'status_id' => $assetStatusIds->get('In Use'), 'license_type' => 'Per-user subscription', 'license_seats' => 150],
            ['asset_tag' => 'LIC-002', 'name' => 'Adobe Creative Cloud', 'asset_type_id' => $assetTypeIds->get('Software License'), 'status_id' => $assetStatusIds->get('In Use'), 'license_type' => 'Per-user subscription', 'license_seats' => 25],
        ];

        foreach ($assetsData as $assetData) {
            $asset = Asset::create(array_merge($assetData, [
                'serial_number' => strtoupper($this->context->faker->bothify('???###???###')),
                'assigned_to_user_id' => $assetData['status_id'] === $assetStatusIds->get('In Use') ? $this->context->users[array_rand($this->context->users)]->id : null,
                'assigned_at' => $assetData['status_id'] === $assetStatusIds->get('In Use') ? now()->subDays(rand(30, 365)) : null,
                'purchase_date' => now()->subDays(rand(100, 800)),
                'purchase_price' => rand(500, 15000),
                'is_active' => true,
            ]));
            $this->context->assets[] = $asset;
        }
    }
}
