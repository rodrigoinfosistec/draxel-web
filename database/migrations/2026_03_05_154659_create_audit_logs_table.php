<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('company_id')->nullable();

            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('event', 40);

            $table->string('subject_type', 180)->nullable();
            $table->string('subject_id', 64)->nullable();

            $table->string('route', 255)->nullable();
            $table->string('method', 10)->nullable();

            $table->string('ip', 64)->nullable();
            $table->text('user_agent')->nullable();

            $table->jsonb('properties')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['user_id']);
            $table->index(['subject_type', 'subject_id']);
            $table->index(['event']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
