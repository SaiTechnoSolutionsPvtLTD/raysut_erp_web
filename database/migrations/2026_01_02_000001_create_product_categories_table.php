<?php

/**
 * Module 02: Master Data
 * Table: product_categories
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->increments('category_id');
            $table->unsignedInteger('parent_category_id')->nullable();
            $table->string('category_name', 100);

            $table->foreign('parent_category_id')->references('category_id')->on('product_categories')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
