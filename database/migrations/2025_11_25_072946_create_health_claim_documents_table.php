<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('health_claim_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('health_claim_id')->constrained()->onDelete('cascade');
            $table->enum('document_type', ['bill', 'receipt', 'prescription', 'medical_report', 'other'])->default('other');
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedInteger('file_size'); // in bytes
            $table->string('mime_type', 100);
            $table->foreignId('uploaded_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index('health_claim_id');
            $table->index('document_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_claim_documents');
    }
};
