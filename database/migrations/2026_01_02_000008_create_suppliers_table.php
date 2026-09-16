<?php

/**
 * Module 02: Master Data
 * Table: suppliers
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->increments('supplier_id');
            $table->string('supplier_code', 20)->unique();
            $table->string('supplier_name', 150);
            $table->string('country', 60)->nullable();
            
            // FK to currencies.currency_code deferred to Phase 5 (Finance)
            $table->char('currency_code', 3)->default('OMR');
            
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->string('import_license_no', 50)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
