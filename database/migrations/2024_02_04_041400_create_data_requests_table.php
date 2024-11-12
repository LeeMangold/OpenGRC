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
        Schema::create('data_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by_id')->constrained('users');
            $table->foreignId('assigned_to_id')->constrained('users');
            $table->foreignId('audit_item_id')->constrained();
            $table->foreignId('audit_id')->constrained();
            $table->string('status')->default(\App\Enums\ResponseStatus::PENDING);
            $table->longText('details')->nullable();
            $table->longText('response')->nullable();
            $table->text('files')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_requests');
    }
};
