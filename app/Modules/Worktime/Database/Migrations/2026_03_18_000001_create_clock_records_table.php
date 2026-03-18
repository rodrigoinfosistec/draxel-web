<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clock_records', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_clock_device_id')
                ->nullable()
                ->constrained('tenant_clock_devices')
                ->nullOnDelete();

            $table->string('source_type');
            $table->string('source_hash');

            $table->dateTime('recorded_at');

            $table->text('notes')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'source_hash']);
            $table->index(['tenant_id', 'company_id']);
            $table->index(['company_id', 'employee_id']);
            $table->index(['employee_id', 'recorded_at']);
            $table->index(['source_type']);
            $table->index(['recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clock_records');
    }
};
