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
        Schema::create('dependents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')->constrained()->onDelete('cascade');
            $table->string('name', 150);
            $table->date('date_of_birth');
            $table->enum('relationship', ['spouse', 'child', 'parent', 'sibling', 'other']);
            $table->string('document_path')->nullable();
            $table->boolean('eligible')->default(false); // Auto-calculated based on age and relationship
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index(['member_id', 'relationship']);
            $table->index('eligible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dependents');
    }
};
