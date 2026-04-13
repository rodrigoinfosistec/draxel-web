<?php

use App\Modules\PurchaseReceipt\Enums\PurchaseReceiptStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_receipts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('supplier_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('received_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('number', 100)->nullable();
            $table->string('invoice_number', 100)->nullable();
            $table->date('issue_date')->nullable();
            $table->date('receipt_date');
            $table->string('status', 30)->default(PurchaseReceiptStatus::Draft->value);
            $table->text('notes')->nullable();

            $table->decimal('total_amount', 15, 2)->default(0);

            $table->timestamp('received_at')->nullable();
            $table->timestamp('canceled_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['supplier_id']);
            $table->index(['warehouse_id']);
            $table->index(['status']);
            $table->index(['receipt_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_receipts');
    }
};
