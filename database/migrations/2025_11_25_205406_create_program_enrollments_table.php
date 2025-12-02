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
        Schema::create('program_enrollments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->date('enrolled_at');
            $table->date('completed_at')->nullable();
            $table->enum('status', ['enrolled', 'completed', 'withdrawn'])->default('enrolled');
            $table->boolean('certificate_issued')->default(false);
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Prevent duplicate enrollments
            $table->unique(['member_id', 'program_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('program_enrollments');
    }
};
