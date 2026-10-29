<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_sale_items', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('retail_sale_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('uom_id');
            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('vat_rate', 12, 3)->default(0.000);
            $table->decimal('vat_amount', 12, 3)->default(0.000);
            $table->decimal('line_total', 12, 3);
            $table->timestamps();

            $table->foreign('retail_sale_id')->references('id')->on('retail_sales')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('cascade');
            $table->foreign('uom_id')->references('uom_id')->on('uom')->onDelete('cascade');

            $table->index(['retail_sale_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_sale_items');
    }
};
