<?php

/**
 * Module 02: Master Data
 * Table: supplier_products
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_products', function (Blueprint $table) {
            $table->unsignedInteger('supplier_id');
            $table->unsignedInteger('product_id');
            $table->string('supplier_sku', 50)->nullable();
            $table->decimal('last_purchase_cost', 12, 3)->nullable();

            $table->primary(['supplier_id', 'product_id']);
            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_products');
    }
};
