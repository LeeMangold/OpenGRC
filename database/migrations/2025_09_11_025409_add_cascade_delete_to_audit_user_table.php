<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the table exists first
        if (! Schema::hasTable('audit_user')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            // For MySQL, use raw SQL to handle foreign keys
            $database = DB::connection()->getDatabaseName();

            // Get all foreign keys for the audit_user table
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'audit_user' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database]);

            // Drop existing foreign keys
            foreach ($foreignKeys as $fk) {
                if (str_contains($fk->CONSTRAINT_NAME, 'audit_id') || str_contains($fk->CONSTRAINT_NAME, 'user_id')) {
                    DB::statement("ALTER TABLE audit_user DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            }

            // Add foreign keys with cascade delete
            DB::statement('ALTER TABLE audit_user ADD CONSTRAINT audit_user_audit_id_foreign FOREIGN KEY (audit_id) REFERENCES audits(id) ON DELETE CASCADE');
            DB::statement('ALTER TABLE audit_user ADD CONSTRAINT audit_user_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE');

        } else {
            // For SQLite and other databases, use Schema builder
            Schema::table('audit_user', function (Blueprint $table) {
                try {
                    $table->dropForeign(['audit_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                $table->foreign('audit_id')
                    ->references('id')
                    ->on('audits')
                    ->onDelete('cascade');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->onDelete('cascade');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('audit_user')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql') {
            $database = DB::connection()->getDatabaseName();

            // Get all foreign keys for the audit_user table
            $foreignKeys = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
                WHERE TABLE_SCHEMA = ? 
                AND TABLE_NAME = 'audit_user' 
                AND REFERENCED_TABLE_NAME IS NOT NULL
            ", [$database]);

            // Drop existing foreign keys
            foreach ($foreignKeys as $fk) {
                if (str_contains($fk->CONSTRAINT_NAME, 'audit_id') || str_contains($fk->CONSTRAINT_NAME, 'user_id')) {
                    DB::statement("ALTER TABLE audit_user DROP FOREIGN KEY {$fk->CONSTRAINT_NAME}");
                }
            }

            // Add foreign keys without cascade delete
            DB::statement('ALTER TABLE audit_user ADD CONSTRAINT audit_user_audit_id_foreign FOREIGN KEY (audit_id) REFERENCES audits(id)');
            DB::statement('ALTER TABLE audit_user ADD CONSTRAINT audit_user_user_id_foreign FOREIGN KEY (user_id) REFERENCES users(id)');

        } else {
            Schema::table('audit_user', function (Blueprint $table) {
                try {
                    $table->dropForeign(['audit_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                try {
                    $table->dropForeign(['user_id']);
                } catch (\Exception $e) {
                    // Foreign key might not exist
                }

                $table->foreign('audit_id')
                    ->references('id')
                    ->on('audits');

                $table->foreign('user_id')
                    ->references('id')
                    ->on('users');
            });
        }
    }
};
