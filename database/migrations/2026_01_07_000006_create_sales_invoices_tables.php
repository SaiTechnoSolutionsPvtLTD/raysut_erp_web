<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_invoices', function (Blueprint $table) {
            $table->increments('invoice_id');
            $table->string('invoice_no', 50)->unique();
            $table->unsignedInteger('sales_order_id');
            $table->unsignedInteger('delivery_note_id')->nullable();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('warehouse_id')->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->decimal('subtotal', 12, 3)->default(0);
            $table->decimal('vat_total', 12, 3)->default(0);
            $table->decimal('grand_total', 12, 3)->default(0);
            $table->decimal('amount_paid', 12, 3)->default(0);
            $table->enum('status', ['DRAFT', 'POSTED', 'PARTIALLY_PAID', 'PAID', 'VOIDED'])->default('DRAFT');
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            $table->text('void_reason')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('order_id')->on('sales_orders')->onDelete('restrict');
            $table->foreign('delivery_note_id')->references('delivery_note_id')->on('delivery_notes')->onDelete('set null');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('restrict');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('set null');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('sales_invoice_items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('order_item_id')->nullable();
            $table->unsignedInteger('delivery_note_item_id')->nullable();
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id');
            $table->unsignedInteger('uom_id');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 3)->default(0);
            $table->decimal('line_total', 12, 3)->default(0);
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('sales_invoices')->onDelete('cascade');
            $table->foreign('order_item_id')->references('item_id')->on('sales_order_items')->onDelete('set null');
            $table->foreign('delivery_note_item_id')->references('item_id')->on('delivery_note_items')->onDelete('set null');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
            $table->foreign('uom_id')->references('uom_id')->on('uom')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_invoice_items');
        Schema::dropIfExists('sales_invoices');
    }
};
