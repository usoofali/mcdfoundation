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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('registration_no')->unique(); // MCDF/00001
            $table->string('full_name');
            $table->string('family_name');
            $table->date('date_of_birth');
            $table->enum('marital_status', ['single', 'married', 'divorced']);
            $table->string('nin')->unique(); // National ID Number
            $table->string('occupation');
            $table->string('workplace');
            $table->text('address');
            $table->string('hometown');
            $table->foreignId('lga_id')->constrained()->onDelete('cascade');
            $table->foreignId('state_id')->constrained()->onDelete('cascade');
            $table->string('country')->default('Nigeria');
            $table->foreignId('healthcare_provider_id')->nullable()->constrained()->onDelete('set null');
            $table->text('health_status')->nullable();
            $table->foreignId('contribution_plan_id')->nullable()->constrained()->onDelete('set null');
            $table->date('registration_date');
            $table->enum('status', ['pre_registered', 'pending', 'active', 'inactive', 'suspended', 'terminated'])->default('pre_registered');
            $table->date('eligibility_start_date')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->boolean('is_complete')->default(false);
            $table->string('photo_path')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['status', 'registration_date']);
            $table->index(['state_id', 'lga_id']);
            $table->index('registration_no');
            $table->index('nin');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
