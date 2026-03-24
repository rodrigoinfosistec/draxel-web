<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hour_bank_snapshot_employee_days', function (Blueprint $table) {
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

            $table->foreignId('hour_bank_snapshot_employee_id')
                ->constrained('hour_bank_snapshot_employees')
                ->cascadeOnDelete();

            $table->foreignId('employee_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->date('work_date');

            $table->string('weekday', 20);
            $table->string('weekday_label', 5);

            $table->string('expected_start_time')->nullable();
            $table->string('expected_end_time')->nullable();
            $table->string('expected_break_duration')->nullable();

            $table->json('records')->nullable();

            $table->integer('justified_minutes')->default(0);
            $table->integer('late_minutes')->default(0);
            $table->integer('extra_minutes')->default(0);
            $table->integer('absence_minutes')->default(0);
            $table->integer('suspension_minutes')->default(0);
            $table->integer('balance_minutes')->default(0);

            $table->boolean('has_divergence')->default(false);
            $table->text('divergence_reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->unique(
                ['hour_bank_snapshot_employee_id', 'work_date'],
                'hour_bank_snapshot_employee_days_snapshot_employee_date_unique'
            );

            $table->index(['tenant_id', 'company_id', 'employee_id', 'work_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hour_bank_snapshot_employee_days');
    }
};
