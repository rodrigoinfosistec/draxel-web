<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entry_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('production_entry_id')
                ->constrained('production_entries')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->nullable();
            $table->decimal('total_cost', 15, 4)->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(['production_entry_id', 'product_id']);
            $table->index(['tenant_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entry_items');
    }
};
