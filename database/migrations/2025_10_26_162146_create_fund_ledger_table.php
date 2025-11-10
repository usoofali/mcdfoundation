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
        Schema::create('fund_ledger', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['inflow', 'outflow']);
            $table->foreignId('member_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source', 50); // contribution, loan_repayment, donation, claim_payment, loan_disbursement, etc.
            $table->decimal('amount', 14, 2);
            $table->text('description')->nullable();
            $table->date('transaction_date');
            $table->string('reference')->nullable(); // receipt_number, loan_id, claim_id, etc.
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            // Indexes for performance
            $table->index(['type', 'transaction_date']);
            $table->index(['member_id', 'transaction_date']);
            $table->index(['source', 'transaction_date']);
            $table->index('reference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fund_ledger');
    }
};
