<?php

/**
 * Module 02: Master Data
 * Table: uom_conversions
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom_conversions', function (Blueprint $table) {
            $table->increments('conversion_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('from_uom_id');
            $table->unsignedInteger('to_uom_id');
            $table->decimal('conversion_factor', 12, 6);

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('cascade');
            $table->foreign('from_uom_id')->references('uom_id')->on('uom')->onDelete('cascade');
            $table->foreign('to_uom_id')->references('uom_id')->on('uom')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom_conversions');
    }
};
