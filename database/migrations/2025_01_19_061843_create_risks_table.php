<?php

use App\Enums\MitigationType;
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
        Schema::create('risks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('status')->default(MitigationType::OPEN);
            $table->unsignedTinyInteger('inherent_likelihood')->default(3);
            $table->unsignedTinyInteger('inherent_impact')->default(3);
            $table->unsignedTinyInteger('residual_likelihood')->default(3);
            $table->unsignedTinyInteger('residual_impact')->default(3);
            $table->unsignedTinyInteger('inherent_risk')->default(0);
            $table->unsignedTinyInteger('residual_risk')->default(0);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
    }
};
