<?php

use App\Enums\RiskStatus;
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
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('status')->default(RiskStatus::NOT_ASSESSED);
            $table->unsignedTinyInteger('inherent_likelihood')->default(3);
            $table->unsignedTinyInteger('inherent_impact')->default(3);
            $table->unsignedTinyInteger('residual_likelihood')->default(3);
            $table->unsignedTinyInteger('residual_impact')->default(3);
            $table->float('inherent_risk')->default(0.0);
            $table->float('residual_risk')->default(0.0);

            $table->timestamps();
        });

        Schema::create('implementation_risk', function (Blueprint $table) {
            $table->id();
            $table->foreignId('implementation_id')->constrained();
            $table->foreignId('risk_id')->constrained();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('risks');
        Schema::dropIfExists('implementation_risk');
    }
};
