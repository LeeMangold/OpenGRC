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
        Schema::table('assets', function (Blueprint $table) {
            $table->string('endpoint_agent_id')->nullable()->after('data_classification_id');
            $table->string('alternative_name')->nullable()->after('qr_code');
            $table->string('cloud_provider')->nullable()->after('department_id');
            $table->decimal('cost_per_hour', 10, 2)->nullable()->after('residual_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['endpoint_agent_id', 'alternative_name', 'cloud_provider', 'cost_per_hour']);
        });
    }
};
