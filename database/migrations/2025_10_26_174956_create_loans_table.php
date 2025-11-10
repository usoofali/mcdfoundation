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
        Schema::create('loans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->enum('loan_type', ['cash', 'item']);
            $table->string('item_description')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('repayment_mode', ['installments', 'full']);
            $table->decimal('installment_amount', 12, 2)->nullable();
            $table->string('repayment_period', 50); // e.g., '6 months', '12 months'
            $table->date('start_date');
            $table->text('security_description')->nullable(); // Collateral description
            $table->string('guarantor_name', 150)->nullable();
            $table->string('guarantor_contact', 100)->nullable();
            $table->enum('status', ['pending', 'approved', 'disbursed', 'repaid', 'defaulted'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('set null');
            $table->date('approval_date')->nullable();
            $table->date('disbursement_date')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['member_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('loan_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
