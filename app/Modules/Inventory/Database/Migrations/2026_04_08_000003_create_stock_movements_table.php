<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->restrictOnDelete();

            $table->foreignId('product_id')
                ->constrained()
                ->restrictOnDelete();

            $table->string('type', 50);
            $table->string('source_type', 50)->default('manual');
            $table->unsignedBigInteger('source_id')->nullable();

            $table->decimal('quantity', 15, 3);
            $table->decimal('unit_cost', 15, 2)->nullable();

            $table->string('reference')->nullable();
            $table->text('notes')->nullable();

            $table->timestamp('moved_at');

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['warehouse_id', 'product_id']);
            $table->index(['source_type', 'source_id']);
            $table->index('moved_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
