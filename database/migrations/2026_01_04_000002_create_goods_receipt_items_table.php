<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->bigIncrements('grn_item_id');
            $table->unsignedInteger('grn_id');
            $table->unsignedBigInteger('po_item_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id')->nullable();
            $table->decimal('quantity_received', 12, 2);
            $table->decimal('quantity_ordered', 12, 2);

            $table->foreign('grn_id')->references('grn_id')->on('goods_receipt_notes')->onDelete('cascade');
            $table->foreign('po_item_id')->references('po_item_id')->on('purchase_order_items')->onDelete('restrict');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
    }
};
