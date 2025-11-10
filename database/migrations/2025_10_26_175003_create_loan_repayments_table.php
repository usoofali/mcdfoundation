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
        Schema::create('loan_repayments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('loan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->date('payment_date');
            $table->enum('payment_method', ['cash', 'transfer', 'bank_deposit', 'mobile_money']);
            $table->string('reference')->nullable();
            $table->foreignId('received_by')->constrained('users')->onDelete('cascade');
            $table->text('remarks')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['loan_id', 'payment_date']);
            $table->index('payment_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loan_repayments');
    }
};
