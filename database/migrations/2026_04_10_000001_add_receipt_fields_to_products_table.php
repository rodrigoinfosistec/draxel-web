<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('barcode', 14)->nullable()->after('sku');
            $table->string('ncm_code', 8)->nullable()->after('barcode');
            $table->string('purchase_description')->nullable()->after('description');

            $table->unique(['tenant_id', 'barcode']);
            $table->index(['tenant_id', 'ncm_code']);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropUnique('products_tenant_id_barcode_unique');
            $table->dropIndex('products_tenant_id_ncm_code_index');

            $table->dropColumn([
                'barcode',
                'ncm_code',
                'purchase_description',
            ]);
        });
    }
};
