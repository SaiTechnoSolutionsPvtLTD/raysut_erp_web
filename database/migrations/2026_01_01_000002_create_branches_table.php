<?php

/**
 * Module 01: System, Security & Setup
 * Table: branches
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->increments('branch_id');
            $table->unsignedInteger('company_id');
            $table->string('branch_code', 20);
            $table->string('branch_name', 150);
            $table->string('branch_type', 50)->default('RETAIL');
            $table->string('city', 100)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branches');
    }
};
