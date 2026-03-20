<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('clock_record_imports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('tenant_clock_device_id')
                ->constrained('tenant_clock_devices')
                ->cascadeOnDelete();

            $table->string('source_type');
            $table->string('status');

            $table->string('original_filename');
            $table->string('stored_path');
            $table->string('file_hash');

            $table->unsignedInteger('total_items')->default(0);
            $table->unsignedInteger('valid_items')->default(0);
            $table->unsignedInteger('invalid_items')->default(0);

            $table->text('notes')->nullable();

            $table->foreignId('imported_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('processed_at')->nullable();
            $table->timestamp('launched_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->unique(['tenant_id', 'company_id', 'file_hash']);
            $table->index(['status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clock_record_imports');
    }
};
