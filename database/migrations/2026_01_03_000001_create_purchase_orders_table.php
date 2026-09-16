<?php

/**
 * Module 03: Purchase & Import
 * Table: purchase_orders
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->increments('po_id');
            $table->string('po_number', 30)->unique();
            $table->unsignedInteger('supplier_id');
            $table->unsignedInteger('warehouse_id');
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            
            // FK to currencies.currency_code deferred to Phase 5
            $table->char('currency_code', 3)->default('OMR');
            
            $table->enum('status', ['DRAFT', 'APPROVED', 'SHIPPED', 'PARTIALLY_RECEIVED', 'COMPLETED', 'CANCELLED'])->default('DRAFT');
            $table->unsignedInteger('created_by')->nullable();
            $table->unsignedInteger('approved_by')->nullable();

            $table->foreign('supplier_id')->references('supplier_id')->on('suppliers')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('cascade');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
