<?php

/**
 * Module 02: Master Data
 * Table: customer_credit_terms
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_credit_terms', function (Blueprint $table) {
            $table->unsignedInteger('customer_id')->primary();
            $table->decimal('credit_limit', 12, 3)->default(0.000);
            $table->unsignedInteger('payment_terms_days')->default(0);
            $table->decimal('outstanding_balance', 12, 3)->default(0.000);

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_credit_terms');
    }
};
