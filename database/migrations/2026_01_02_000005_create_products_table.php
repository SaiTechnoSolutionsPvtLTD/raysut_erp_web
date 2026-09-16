<?php

/**
 * Module 02: Master Data
 * Table: products
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->increments('product_id');
            $table->string('sku_code', 50)->unique();
            $table->string('barcode', 50)->nullable();
            $table->string('product_name', 200);
            $table->unsignedInteger('category_id');
            $table->unsignedInteger('brand_id')->nullable();
            $table->unsignedInteger('base_uom_id');
            $table->unsignedInteger('tax_rate_id')->nullable();
            $table->string('hs_code', 20)->nullable();
            $table->string('pack_size', 50)->nullable();
            $table->decimal('reorder_level', 12, 2)->default(0.00);
            $table->boolean('is_duty_free_eligible')->default(false);
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('category_id')->references('category_id')->on('product_categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('brand_id')->on('brands')->onDelete('set null');
            $table->foreign('base_uom_id')->references('uom_id')->on('uom')->onDelete('cascade');
            $table->foreign('tax_rate_id')->references('tax_rate_id')->on('tax_vat_rates')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
