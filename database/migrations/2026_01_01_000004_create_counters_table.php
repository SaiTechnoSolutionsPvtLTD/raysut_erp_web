<?php

/**
 * Module 01: System, Security & Setup
 * Table: counters
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('counters', function (Blueprint $table) {
            $table->increments('counter_id');
            $table->unsignedInteger('branch_id');
            $table->string('counter_code', 20);
            $table->string('counter_name', 100);
            $table->string('terminal_id', 50)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('counters');
    }
};
