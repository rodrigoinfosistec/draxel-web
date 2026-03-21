<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_hour_entries', function (Blueprint $table) {
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

            $table->foreignId('bank_hour_account_id')
                ->constrained('bank_hour_accounts')
                ->cascadeOnDelete();

            $table->string('entry_type');
            $table->integer('minutes');
            $table->date('occurred_on');
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->nullableMorphs('source');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['employee_id', 'occurred_on']);
            $table->index(['entry_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_hour_entries');
    }
};
