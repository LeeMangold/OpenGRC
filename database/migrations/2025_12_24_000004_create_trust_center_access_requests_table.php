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
        Schema::create('trust_center_access_requests', function (Blueprint $table) {
            $table->id();
            $table->string('requester_name');
            $table->string('requester_email');
            $table->string('requester_company');
            $table->text('reason')->nullable();
            $table->boolean('nda_agreed')->default(false);
            $table->string('status')->default('pending');

            // Approval workflow
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();

            // Magic link tracking
            $table->string('access_token')->unique()->nullable();
            $table->timestamp('access_expires_at')->nullable();
            $table->timestamp('last_accessed_at')->nullable();
            $table->integer('access_count')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('requester_email');
            $table->index('status');
            $table->index('access_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trust_center_access_requests');
    }
};
