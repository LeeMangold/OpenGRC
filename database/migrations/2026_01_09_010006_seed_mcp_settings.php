<?php

use Database\Seeders\McpSettingsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Seed MCP settings for in-place upgrades (uses updateOrInsert, safe to run always)
        $seeder = new McpSettingsSeeder;
        $seeder->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Don't delete settings on rollback - they may have been customized
    }
};
