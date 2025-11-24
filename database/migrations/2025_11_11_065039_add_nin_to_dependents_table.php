<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('dependents', function (Blueprint $table) {
            $table->string('nin', 11)->nullable()->after('name');
            $table->unique('nin');
        });

        DB::table('dependents')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($dependents) {
                foreach ($dependents as $dependent) {
                    DB::table('dependents')
                        ->where('id', $dependent->id)
                        ->update([
                            'nin' => sprintf('%011d', 10000000000 + (int) $dependent->id),
                            'updated_at' => now(),
                        ]);
                }
            });

        // Ensure all records now have a value before enforcing NOT NULL
        Schema::table('dependents', function (Blueprint $table) {
            $table->string('nin', 11)->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dependents', function (Blueprint $table) {
            $table->dropUnique(['nin']);
            $table->dropColumn('nin');
        });
    }
};
