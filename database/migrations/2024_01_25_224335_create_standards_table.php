<?php

use App\Enums\StandardStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('standards', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->longText('description');
            $table->string('code', 255);
            $table->string('authority', 255);
            $table->string('reference_url', 512)->nullable();
            $table->enum('status', array_column(StandardStatus::cases(), 'value'))->default(StandardStatus::DRAFT->value);
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('standards');
    }
};
