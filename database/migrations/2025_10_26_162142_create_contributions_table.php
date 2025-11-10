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
        Schema::create('contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->foreignId('contribution_plan_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->enum('payment_method', ['cash', 'transfer', 'bank_deposit', 'mobile_money']);
            $table->string('payment_reference')->nullable();
            $table->date('payment_date');
            $table->date('period_start');
            $table->date('period_end');
            $table->enum('status', ['paid', 'pending', 'overdue', 'cancelled'])->default('pending');
            $table->foreignId('collected_by')->constrained('users')->onDelete('cascade');
            $table->decimal('fine_amount', 10, 2)->default(0);
            $table->string('receipt_number')->unique();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['member_id', 'status']);
            $table->index(['payment_date', 'status']);
            $table->index(['period_start', 'period_end']);
            $table->index('receipt_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contributions');
    }
};
