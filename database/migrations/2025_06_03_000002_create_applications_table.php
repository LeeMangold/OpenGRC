<?php

use App\Enums\ApplicationType;
use App\Enums\ApplicationStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('logo', 512)->nullable();
            $table->foreignId('owner_id')->constrained('users');
            $table->enum('type', array_column(ApplicationType::cases(), 'value'))->default(ApplicationType::OTHER->value);
            $table->longText('description')->nullable();
            $table->enum('status', array_column(ApplicationStatus::cases(), 'value'))->default(ApplicationStatus::APPROVED->value);
            $table->string('url', 512)->nullable();
            $table->longText('notes')->nullable();
            $table->foreignId('vendor_id')->constrained('vendors');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
}; 