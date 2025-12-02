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
        Schema::table('health_claims', function (Blueprint $table) {
            $table->date('approval_date')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approval_date')->constrained('users')->onDelete('set null');
            $table->date('rejection_date')->nullable()->after('rejected_by');

            // Add index for rejected_by
            $table->index('rejected_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('health_claims', function (Blueprint $table) {
            $table->dropForeign(['rejected_by']);
            $table->dropIndex(['rejected_by']);
            $table->dropColumn(['approval_date', 'rejected_by', 'rejection_date']);
        });
    }
};
