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
        Schema::table('contribution_plans', function (Blueprint $table) {
            if (! Schema::hasColumn('contribution_plans', 'frequency')) {
                $table->string('frequency')->default('monthly')->after('amount');
            }

            if (! Schema::hasColumn('contribution_plans', 'display_name')) {
                $table->string('display_name')->nullable()->after('name');
            }

            if (! Schema::hasColumn('contribution_plans', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('frequency');
            }
        });

        DB::table('contribution_plans')->update([
            'is_active' => DB::raw('COALESCE(is_active, COALESCE(active, 1))'),
            'display_name' => DB::raw('COALESCE(display_name, name)'),
        ]);

        if (Schema::hasColumn('contribution_plans', 'active')) {
            if (DB::getDriverName() !== 'sqlite') {
                DB::statement('ALTER TABLE contribution_plans DROP COLUMN active');
            } else {
                Schema::table('contribution_plans', function (Blueprint $table) {
                    $table->dropColumn('active');
                });
            }
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE contribution_plans MODIFY COLUMN name VARCHAR(255) NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasColumn('contribution_plans', 'active')) {
            Schema::table('contribution_plans', function (Blueprint $table) {
                $table->boolean('active')->default(true)->after('frequency');
            });
        }

        DB::table('contribution_plans')->update([
            'active' => DB::raw('COALESCE(active, COALESCE(is_active, 1))'),
        ]);

        Schema::table('contribution_plans', function (Blueprint $table) {
            if (Schema::hasColumn('contribution_plans', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('contribution_plans', 'frequency')) {
                $table->dropColumn('frequency');
            }

            if (Schema::hasColumn('contribution_plans', 'display_name')) {
                $table->dropColumn('display_name');
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE contribution_plans MODIFY COLUMN name ENUM('daily','weekly','monthly','quarterly','annual') NOT NULL");
            DB::statement('ALTER TABLE contribution_plans CHANGE COLUMN active active TINYINT(1) NOT NULL DEFAULT 1');
        }
    }
};
