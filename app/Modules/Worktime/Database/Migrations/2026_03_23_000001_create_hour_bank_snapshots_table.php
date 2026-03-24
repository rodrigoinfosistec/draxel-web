<?php

use App\Modules\Worktime\Enums\HourBankSnapshotStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_bank_snapshots', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('name');
            $table->date('period_start');
            $table->date('period_end');

            $table->string('status')
                ->default(HourBankSnapshotStatus::DRAFT->value);

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('consolidated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('consolidated_at')->nullable();

            $table->foreignId('reversed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('reversed_at')->nullable();
            $table->text('reversal_reason')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id', 'status']);
            $table->index(['tenant_id', 'company_id', 'period_start', 'period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_bank_snapshots');
    }
};
