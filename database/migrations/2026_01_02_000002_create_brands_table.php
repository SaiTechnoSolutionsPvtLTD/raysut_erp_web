<?php

/**
 * Module 02: Master Data
 * Table: brands
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('brands', function (Blueprint $table) {
            $table->increments('brand_id');
            $table->string('brand_name', 100);
            $table->string('country_of_origin', 60)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('brands');
    }
};
