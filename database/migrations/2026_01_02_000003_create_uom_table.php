<?php

/**
 * Module 02: Master Data
 * Table: uom
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uom', function (Blueprint $table) {
            $table->increments('uom_id');
            $table->string('uom_code', 15);
            $table->string('uom_name', 50);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uom');
    }
};
