<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateControlImplementationTable extends Migration
{
    public function up()
    {
        Schema::create('control_implementation', function (Blueprint $table) {
            $table->foreignId('control_id')->constrained()->onDelete('cascade');
            $table->foreignId('implementation_id')->constrained()->onDelete('cascade');
            //            $table->integer('percentage')->nullable(); // Adding the percentage field
            //            $table->string('status')->nullable(); // Adding the status field
            $table->primary(['control_id', 'implementation_id']);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('control_implementation');
    }
}
