<?php

use App\Enums\SupportTicketStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('support_tickets', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();

            $table->foreignId('created_by')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('assigned_to')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->string('code')->unique();
            $table->string('subject');
            $table->text('description');

            $table->string('status')->default(SupportTicketStatus::OPEN->value);
            $table->boolean('is_open')->default(true);

            $table->timestamp('last_interaction_at')->nullable();
            $table->timestamp('closed_at')->nullable();

            $table->timestamps();

            $table->index(['tenant_id', 'company_id']);
            $table->index(['tenant_id', 'company_id', 'status']);
            $table->index(['tenant_id', 'company_id', 'is_open']);
            $table->index(['created_by']);
            $table->index(['assigned_to']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('support_tickets');
    }
};
