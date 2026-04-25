<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed taxonomy types first so valid IDs exist
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\AssetCategoryLocationSupplierTaxonomySeeder',
            '--force' => true,
        ]);

        // Null out any orphaned values that don't reference valid taxonomy IDs
        $validIds = DB::table('taxonomies')->pluck('id');

        foreach (['category_id', 'location_id', 'department_id', 'supplier_id'] as $column) {
            if ($validIds->isNotEmpty()) {
                DB::table('assets')
                    ->whereNotNull($column)
                    ->whereNotIn($column, $validIds)
                    ->update([$column => null]);
            }
        }

        // Add FK constraints
        Schema::table('assets', function (Blueprint $table) {
            $table->foreign('category_id')->references('id')->on('taxonomies')->nullOnDelete();
            $table->foreign('location_id')->references('id')->on('taxonomies')->nullOnDelete();
            $table->foreign('department_id')->references('id')->on('taxonomies')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('taxonomies')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['location_id']);
            $table->dropForeign(['department_id']);
            $table->dropForeign(['supplier_id']);
        });
    }
};
