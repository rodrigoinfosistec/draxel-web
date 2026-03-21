<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bank_hour_accounts', function (Blueprint $table) {
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

            $table->integer('current_balance_minutes')->default(0);

            $table->timestamps();

            $table->unique(['tenant_id', 'company_id', 'employee_id']);
            $table->index(['tenant_id', 'company_id']);
            $table->index(['employee_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_hour_accounts');
    }
};
