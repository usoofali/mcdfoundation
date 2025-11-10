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
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('entity_type', 100); // e.g., 'Loan', 'Claim', 'Registration'
            $table->unsignedBigInteger('entity_id');
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
            $table->string('role', 50); // Approver's role
            $table->integer('approval_level'); // Level (1=LG, 2=State, 3=Project)
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('remarks')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['entity_type', 'entity_id']);
            $table->index(['status', 'approval_level']);
            $table->index('approved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approvals');
    }
};
