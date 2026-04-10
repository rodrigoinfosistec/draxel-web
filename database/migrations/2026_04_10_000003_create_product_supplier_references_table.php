<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_supplier_references', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            $table->foreignId('product_id')
                ->constrained('products')
                ->cascadeOnDelete();

            $table->string('supplier_product_code')->nullable();
            $table->string('supplier_product_description')->nullable();
            $table->string('barcode', 14)->nullable();
            $table->string('unit', 20)->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique(
                ['tenant_id', 'supplier_id', 'product_id'],
                'psr_tenant_supplier_product_unique'
            );

            $table->unique(
                ['tenant_id', 'supplier_id', 'supplier_product_code'],
                'psr_tenant_supplier_code_unique'
            );

            $table->index(['tenant_id', 'supplier_id']);
            $table->index(['tenant_id', 'product_id']);
            $table->index(['tenant_id', 'barcode']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_supplier_references');
    }
};
