<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->increments('order_id');
            $table->string('so_no', 50)->unique();
            $table->unsignedInteger('quotation_id')->nullable();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('warehouse_id');
            $table->enum('status', ['DRAFT', 'CONFIRMED', 'PARTIALLY_DISPATCHED', 'DISPATCHED', 'INVOICED', 'CANCELLED'])->default('DRAFT');
            $table->decimal('subtotal', 12, 3)->default(0);
            $table->decimal('vat_total', 12, 3)->default(0);
            $table->decimal('grand_total', 12, 3)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->enum('credit_check_status', ['PENDING', 'PASSED', 'OVERRIDDEN', 'BLOCKED'])->default('PENDING');
            $table->unsignedInteger('credit_override_by')->nullable();
            $table->timestamp('credit_override_at')->nullable();
            $table->text('credit_override_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('quotation_id')->references('quotation_id')->on('wholesale_quotations')->onDelete('set null');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('restrict');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('credit_override_by')->references('user_id')->on('users')->onDelete('set null');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('uom_id');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 3)->default(0);
            $table->decimal('line_total', 12, 3)->default(0);
            $table->decimal('quantity_reserved', 12, 2)->default(0);
            $table->decimal('quantity_dispatched', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('uom_id')->references('uom_id')->on('uom')->onDelete('restrict');
        });

        Schema::create('sales_order_reservations', function (Blueprint $table) {
            $table->increments('reservation_id');
            $table->unsignedInteger('order_id');
            $table->unsignedInteger('order_item_id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id');
            $table->decimal('quantity_reserved', 12, 2);
            $table->decimal('quantity_dispatched', 12, 2)->default(0);
            $table->timestamps();

            $table->foreign('order_id')->references('order_id')->on('sales_orders')->onDelete('cascade');
            $table->foreign('order_item_id')->references('item_id')->on('sales_order_items')->onDelete('cascade');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_reservations');
        Schema::dropIfExists('sales_order_items');
        Schema::dropIfExists('sales_orders');
    }
};
