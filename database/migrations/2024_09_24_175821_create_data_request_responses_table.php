<?php

use App\Enums\ResponseStatus;
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
        Schema::create('data_request_responses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('data_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('requestee_id')->constrained('users')->onDelete('cascade');

            $table->enum('status', array_column(ResponseStatus::cases(), 'value'))->default(ResponseStatus::PENDING->value);
            $table->text('response')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('data_request_responses');
    }
};
