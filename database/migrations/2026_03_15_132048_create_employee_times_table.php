<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_times', function (Blueprint $table) {
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

            $table->string('weekday', 20);

            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->time('break_duration')->nullable();

            $table->timestamps();

            $table->unique(['employee_id', 'weekday']);
            $table->index(['tenant_id', 'company_id', 'employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_times');
    }
};
