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
        Schema::table('contributions', function (Blueprint $table) {
            // Add receipt upload fields
            $table->string('receipt_path')->nullable()->after('receipt_number');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->onDelete('set null')->after('receipt_path');
            $table->text('verification_notes')->nullable()->after('notes');
            $table->timestamp('verified_at')->nullable()->after('verification_notes');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null')->after('verified_at');

            // Make collected_by nullable to support pending contributions
            $table->foreignId('collected_by')->nullable()->change();

            // Add indexes for performance
            $table->index(['status', 'verified_at']);
            $table->index('uploaded_by');
            $table->index('verified_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contributions', function (Blueprint $table) {
            // Drop indexes
            $table->dropIndex(['status', 'verified_at']);
            $table->dropIndex(['uploaded_by']);
            $table->dropIndex(['verified_by']);

            // Drop foreign key constraints
            $table->dropForeign(['uploaded_by']);
            $table->dropForeign(['verified_by']);

            // Drop columns
            $table->dropColumn([
                'receipt_path',
                'uploaded_by',
                'verification_notes',
                'verified_at',
                'verified_by',
            ]);

            // Revert collected_by to not nullable
            $table->foreignId('collected_by')->nullable(false)->change();
        });
    }
};
