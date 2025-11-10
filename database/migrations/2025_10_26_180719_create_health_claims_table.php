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
        Schema::create('health_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('healthcare_provider_id')->constrained()->onDelete('cascade');
            $table->enum('claim_type', ['outpatient', 'inpatient', 'surgery', 'maternity']);
            $table->decimal('billed_amount', 12, 2);
            $table->decimal('coverage_percent', 5, 2)->default(90.00); // 90% coverage
            $table->decimal('covered_amount', 12, 2);
            $table->decimal('copay_amount', 12, 2); // 10% copay
            $table->date('claim_date');
            $table->enum('status', ['submitted', 'approved', 'rejected', 'paid'])->default('submitted');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->foreignId('paid_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('paid_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('claim_number', 50)->unique();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['member_id', 'status']);
            $table->index(['status', 'claim_date']);
            $table->index('claim_type');
            $table->index('healthcare_provider_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('health_claims');
    }
};
