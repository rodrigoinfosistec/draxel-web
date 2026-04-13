<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipt_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('purchase_receipt_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('subtotal', 15, 2);

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->unique(['purchase_receipt_id', 'product_id'], 'purchase_receipt_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipt_items');
    }
};
