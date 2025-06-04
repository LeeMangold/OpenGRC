<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            $table->unsignedBigInteger('control_owner_id')->nullable()->after('standard_id');
            $table->foreign('control_owner_id')->references('id')->on('users')->nullOnDelete();
        });
        Schema::table('implementations', function (Blueprint $table) {
            $table->unsignedBigInteger('implementation_owner_id')->nullable()->after('id');
            $table->foreign('implementation_owner_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('controls', function (Blueprint $table) {
            $table->dropForeign(['control_owner_id']);
            $table->dropColumn('control_owner_id');
         });
        Schema::table('implementations', function (Blueprint $table) {
            $table->dropForeign(['implementation_owner_id']);
            $table->dropColumn('implementation_owner_id');
        });
    }
}; 