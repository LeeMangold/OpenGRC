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
        Schema::create('audit_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('audit_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('auditable_id')->nullable();
            $table->string('auditable_type')->nullable();
            //            $table->unsignedBigInteger('control_id')->nullable();
            //            $table->unsignedBigInteger('implementation_id')->nullable();
            $table->longText('auditor_notes')->nullable();
            $table->string('status')->default('Not Tested');
            $table->string('effectiveness')->default('Unknown');
            $table->string('applicability')->default('Unknown');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('audit_id')->references('id')->on('audits')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_items');
    }
};
