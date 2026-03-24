<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_bank_snapshot_employees', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('hour_bank_snapshot_id')
                ->constrained('hour_bank_snapshots')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('employee_name');
            $table->string('employee_registration')->nullable();

            $table->integer('justified_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('extra_minutes')->default(0);
            $table->integer('absence_minutes')->default(0);
            $table->integer('suspension_minutes')->default(0);
            $table->integer('balance_minutes')->default(0);

            $table->boolean('has_divergence')->default(false);
            $table->text('divergence_summary')->nullable();

            $table->timestamp('captured_at');

            $table->foreignId('captured_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(
                ['hour_bank_snapshot_id', 'employee_id'],
                'hour_bank_snapshot_employees_snapshot_employee_unique'
            );

            $table->index(['tenant_id', 'company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_bank_snapshot_employees');
    }
};
