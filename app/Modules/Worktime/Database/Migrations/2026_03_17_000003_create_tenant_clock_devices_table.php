<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenant_clock_devices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('tenant_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('clock_device_id')
                ->constrained('clock_devices')
                ->cascadeOnDelete();

            $table->timestamps();

            $table->unique(['tenant_id', 'clock_device_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_clock_devices');
    }
};
