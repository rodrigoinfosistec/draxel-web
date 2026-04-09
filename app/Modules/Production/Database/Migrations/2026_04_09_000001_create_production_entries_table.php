<?php

use App\Modules\Production\Enums\ProductionEntryStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_entries', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->constrained('warehouses')
                ->cascadeOnDelete();

            $table->string('number', 30);
            $table->date('entry_date');
            $table->string('status', 20)->default(ProductionEntryStatus::Draft->value);
            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('posted_at')->nullable();

            $table->foreignId('cancelled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancel_reason')->nullable();

            $table->timestamps();

            $table->unique(['company_id', 'number']);
            $table->index(['tenant_id', 'company_id', 'status']);
            $table->index(['tenant_id', 'company_id', 'entry_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_entries');
    }
};
