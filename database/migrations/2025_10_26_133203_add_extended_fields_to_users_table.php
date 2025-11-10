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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('role_id')->nullable()->constrained()->onDelete('set null');
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->foreignId('state_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('lga_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('status', ['active', 'inactive', 'suspended'])->default('active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['lga_id']);
            $table->dropColumn(['role_id', 'phone', 'address', 'state_id', 'lga_id', 'status']);
        });
    }
};
