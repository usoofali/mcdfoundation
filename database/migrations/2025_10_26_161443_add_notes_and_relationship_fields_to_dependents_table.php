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
        Schema::table('dependents', function (Blueprint $table) {
            // Add notes field if it doesn't exist
            if (! Schema::hasColumn('dependents', 'notes')) {
                $table->text('notes')->nullable()->after('eligible');
            }
        });

        // Drop foreign key constraint first
        Schema::table('dependents', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('dependents', function (Blueprint $table) {
            // Drop the index that references relationship column
            if (Schema::hasIndex('dependents', 'dependents_member_id_relationship_index')) {
                $table->dropIndex(['member_id', 'relationship']);
            }

            // Update relationship enum to include parent and sibling
            $table->dropColumn('relationship');
        });

        Schema::table('dependents', function (Blueprint $table) {
            $table->enum('relationship', ['spouse', 'child', 'parent', 'sibling', 'other'])->after('date_of_birth');

            // Recreate the index
            $table->index(['member_id', 'relationship']);
        });

        // Recreate foreign key constraint
        Schema::table('dependents', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop foreign key constraint first
        Schema::table('dependents', function (Blueprint $table) {
            $table->dropForeign(['member_id']);
        });

        Schema::table('dependents', function (Blueprint $table) {
            // Drop the index
            if (Schema::hasIndex('dependents', 'dependents_member_id_relationship_index')) {
                $table->dropIndex(['member_id', 'relationship']);
            }

            $table->dropColumn('notes');
            $table->dropColumn('relationship');
        });

        Schema::table('dependents', function (Blueprint $table) {
            $table->enum('relationship', ['spouse', 'child', 'other'])->after('date_of_birth');

            // Recreate the original index
            $table->index(['member_id', 'relationship']);
        });

        // Recreate foreign key constraint
        Schema::table('dependents', function (Blueprint $table) {
            $table->foreign('member_id')->references('id')->on('members')->onDelete('cascade');
        });
    }
};
