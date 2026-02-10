<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('assets', 'asset_exposure_id')) {
            Schema::table('assets', function (Blueprint $table) {
                $table->foreignId('asset_exposure_id')->nullable()->after('data_classification_id')->constrained('taxonomies')->nullOnDelete();
                $table->foreignId('asset_criticality_id')->nullable()->after('asset_exposure_id')->constrained('taxonomies')->nullOnDelete();
            });
        }

        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\AssetExposureCriticalityTaxonomySeeder',
            '--force' => true,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('asset_exposure_id');
            $table->dropConstrainedForeignId('asset_criticality_id');
        });
    }
};
