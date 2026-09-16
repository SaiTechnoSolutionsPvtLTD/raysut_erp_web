<?php

/**
 * Module 02: Master Data
 * Table: customers
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->increments('customer_id');
            $table->string('customer_code', 20)->unique();
            $table->string('customer_name', 150);
            $table->enum('customer_segment', ['RETAIL', 'WHOLESALE', 'HORECA', 'HARBOR', 'ECOMMERCE'])->default('RETAIL');
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->string('oman_card_no', 50)->nullable();
            $table->boolean('is_active')->default(true);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
