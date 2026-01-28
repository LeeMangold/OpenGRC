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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();

            // Polymorphic relationship to the approvable model
            $table->morphs('approvable');

            // The user who approved (nullable in case user is deleted)
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();

            // Static snapshot of approver details at time of approval
            // These are preserved even if the user is deleted
            $table->string('approver_name');
            $table->string('approver_email')->nullable();

            // The digital signature (typed name)
            $table->string('signature');

            // Optional approval notes
            $table->text('notes')->nullable();

            // When the approval occurred
            $table->timestamp('approved_at');

            $table->timestamps();

            // Index for efficient lookups
            $table->index(['approvable_type', 'approvable_id', 'approved_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
