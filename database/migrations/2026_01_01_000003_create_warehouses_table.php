<?php

/**
 * Module 01: System, Security & Setup
 * Table: warehouses
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->increments('warehouse_id');
            $table->unsignedInteger('company_id');
            $table->unsignedInteger('branch_id')->nullable();
            $table->string('warehouse_code', 20);
            $table->string('warehouse_name', 150);
            $table->string('warehouse_type', 50);
            $table->string('customs_bond_ref', 50)->nullable();
            $table->boolean('is_active')->default(true);

            $table->foreign('company_id')->references('company_id')->on('companies')->onDelete('cascade');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('warehouses');
    }
};
