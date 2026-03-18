<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clock_record_import_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('clock_record_import_id')
                ->constrained('clock_record_imports')
                ->cascadeOnDelete();

            $table->unsignedInteger('line_number');
            $table->text('raw_line');

            $table->string('employee_code')->nullable();

            $table->foreignId('employee_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->dateTime('recorded_at')->nullable();

            $table->string('status');
            $table->string('record_hash')->nullable();
            $table->string('divergence_reason')->nullable();

            $table->json('payload')->nullable();

            $table->foreignId('launched_clock_record_id')
                ->nullable()
                ->constrained('clock_records')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['clock_record_import_id', 'status']);
            $table->index(['employee_code']);
            $table->index(['employee_id', 'recorded_at']);
            $table->index(['record_hash']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clock_record_import_items');
    }
};
