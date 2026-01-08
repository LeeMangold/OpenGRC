<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Remove cascade delete from controls.standard_id foreign key.
     * This allows the application to control which controls are deleted
     * when a standard is deleted (only orphaned controls without relationships).
     */
    public function up(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            // Drop the existing foreign key
            $table->dropForeign(['standard_id']);

            // Re-add without cascade delete (set null on delete)
            $table->foreign('standard_id')
                ->references('id')
                ->on('standards')
                ->onDelete('set null');
        });

        // Make standard_id nullable to support set null
        Schema::table('controls', function (Blueprint $table) {
            $table->unsignedBigInteger('standard_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Make standard_id not nullable again
        Schema::table('controls', function (Blueprint $table) {
            $table->unsignedBigInteger('standard_id')->nullable(false)->change();
        });

        Schema::table('controls', function (Blueprint $table) {
            // Drop the modified foreign key
            $table->dropForeign(['standard_id']);

            // Re-add with cascade delete
            $table->foreign('standard_id')
                ->references('id')
                ->on('standards')
                ->onDelete('cascade');
        });
    }
};
