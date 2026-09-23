<?php

use App\Models\User;
use Database\Seeders\VendorSurveyTemplatesSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Seed the built-in vendor survey templates on installs that never received them.
     *
     * The original seed migrations skip when no user exists, and the installer
     * did not run VendorSurveyTemplatesSeeder, so installs created with
     * `opengrc:install` ended up without either template. The seeder only adds
     * templates that are missing (including soft-deleted ones).
     */
    public function up(): void
    {
        if (! User::exists()) {
            return;
        }

        (new VendorSurveyTemplatesSeeder)->run();
    }

    public function down(): void
    {
        // Templates may be in use by surveys; leave them in place.
    }
};
