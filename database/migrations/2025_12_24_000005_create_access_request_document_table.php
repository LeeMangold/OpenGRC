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
        Schema::create('access_request_document', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trust_center_access_request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('trust_center_document_id')->constrained()->cascadeOnDelete();
            $table->timestamp('downloaded_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['trust_center_access_request_id', 'trust_center_document_id'],
                'access_req_doc_unique'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('access_request_document');
    }
};
