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
        Schema::create('expected_contributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->cascadeOnDelete();
            $table->foreignId('contribution_plan_id')->constrained();
            $table->foreignId('actual_contribution_id')->nullable()->constrained('contributions')->nullOnDelete();

            $table->decimal('expected_amount', 10, 2);
            $table->decimal('fine_amount', 10, 2)->default(0);

            $table->date('due_date');
            $table->date('period_start');
            $table->date('period_end');

            $table->enum('status', ['pending', 'paid', 'overdue', 'waived'])->default('pending');

            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('overdue_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['member_id', 'status']);
            $table->index(['due_date', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('expected_contributions');
    }
};
