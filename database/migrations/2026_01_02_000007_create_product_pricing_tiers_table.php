<?php

/**
 * Module 02: Master Data
 * Table: product_pricing_tiers
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_pricing_tiers', function (Blueprint $table) {
            $table->increments('price_id');
            $table->unsignedInteger('product_id');
            $table->enum('price_tier', ['RETAIL', 'WHOLESALE', 'HARBOR', 'ECOMMERCE']);
            $table->unsignedInteger('min_quantity')->default(1);
            $table->decimal('unit_price', 12, 3);
            $table->date('effective_from');
            $table->date('effective_to')->nullable();

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_pricing_tiers');
    }
};
