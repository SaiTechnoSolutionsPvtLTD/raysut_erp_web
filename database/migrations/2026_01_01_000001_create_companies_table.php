<?php

/**
 * Module 01: System, Security & Setup
 * Table: companies
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->increments('company_id');
            $table->string('company_name', 150);
            $table->string('trade_license_no', 50)->nullable();
            $table->string('liquor_license_no', 50)->nullable();
            
            // FK to currencies.currency_code deferred to Phase 5 (Finance) as currencies table does not exist yet
            $table->char('base_currency_code', 3)->default('OMR');
            
            $table->string('address', 255)->nullable();
            $table->string('logo_path', 255)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
