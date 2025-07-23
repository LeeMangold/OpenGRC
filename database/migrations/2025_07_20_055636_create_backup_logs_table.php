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
        Schema::create('backup_logs', function (Blueprint $table) {
            $table->id();
            $table->string('backup_name');
            $table->enum('backup_type', ['full', 'database', 'files', 'incremental']);
            $table->enum('status', ['pending', 'running', 'completed', 'failed', 'cancelled']);
            $table->string('storage_driver'); // local, s3
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->bigInteger('file_size')->nullable(); // bytes
            $table->string('checksum')->nullable(); // for integrity verification
            $table->json('backup_config')->nullable(); // stores backup configuration
            $table->json('included_tables')->nullable(); // which tables were included
            $table->json('excluded_tables')->nullable(); // which tables were excluded
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration')->nullable(); // seconds
            $table->text('error_message')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_encrypted')->default(false);
            $table->boolean('is_compressed')->default(true);
            $table->boolean('verified')->default(false);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('expires_at')->nullable(); // for retention policy
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'backup_type']);
            $table->index(['created_at', 'expires_at']);
            $table->index('storage_driver');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('backup_logs');
    }
};
