<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_companies', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained('tenants')
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('company_id')
                ->constrained('companies')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['user_id', 'company_id']);

            $table->index(['tenant_id', 'user_id']);
            $table->index(['tenant_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_companies');
    }
};
