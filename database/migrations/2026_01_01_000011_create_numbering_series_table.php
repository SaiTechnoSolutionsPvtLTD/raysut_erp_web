<?php

/**
 * Module 01: System, Security & Setup
 * Table: numbering_series
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('numbering_series', function (Blueprint $table) {
            $table->increments('series_id');
            $table->string('document_type', 50);
            $table->string('prefix', 20)->nullable();
            $table->unsignedBigInteger('next_number')->default(1);
            $table->string('fiscal_year', 9)->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('numbering_series');
    }
};
