<?php

/**
 * Module 02: Master Data
 * Table: tax_vat_rates
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_vat_rates', function (Blueprint $table) {
            $table->increments('tax_rate_id');
            $table->string('tax_name', 100);
            $table->enum('tax_type', ['VAT', 'EXCISE', 'CUSTOMS_DUTY']);
            $table->decimal('rate_percent', 6, 3);
            $table->date('effective_from');
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_vat_rates');
    }
};
