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
        Schema::table('survey_templates', function (Blueprint $table) {
            $table->foreignId('default_assignee_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('recurrence_frequency')->nullable();
            $table->unsignedInteger('recurrence_interval')->nullable()->default(1);
            $table->unsignedTinyInteger('recurrence_day_of_week')->nullable();
            $table->unsignedTinyInteger('recurrence_day_of_month')->nullable();
            $table->timestamp('last_checklist_generated_at')->nullable();
            $table->timestamp('next_checklist_due_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('survey_templates', function (Blueprint $table) {
            $table->dropForeign(['default_assignee_id']);
            $table->dropColumn([
                'default_assignee_id',
                'recurrence_frequency',
                'recurrence_interval',
                'recurrence_day_of_week',
                'recurrence_day_of_month',
                'last_checklist_generated_at',
                'next_checklist_due_at',
            ]);
        });
    }
};
