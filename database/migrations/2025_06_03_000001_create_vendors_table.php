<?php

use App\Enums\VendorRiskRating;
use App\Enums\VendorStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendors', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->longText('description')->nullable();
            $table->string('url', 512)->nullable();
            $table->string('logo', 512)->nullable();
            $table->foreignId('vendor_manager_id')->constrained('users');
            $table->enum('status', array_column(VendorStatus::cases(), 'value'))->default(VendorStatus::PENDING->value);
            $table->enum('risk_rating', array_column(VendorRiskRating::cases(), 'value'))->default(VendorRiskRating::MEDIUM->value);
            $table->longText('notes')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendors');
    }
}; 