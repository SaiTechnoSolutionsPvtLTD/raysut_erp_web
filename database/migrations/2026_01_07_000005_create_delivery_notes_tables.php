<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->increments('delivery_note_id');
            $table->string('dn_no', 50)->unique();
            $table->unsignedInteger('sales_order_id');
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('warehouse_id');
            $table->unsignedInteger('dispatched_by')->nullable();
            $table->timestamp('dispatched_at')->nullable();
            $table->string('vehicle_no', 50)->nullable();
            $table->string('driver_name', 100)->nullable();
            $table->enum('status', ['DRAFT', 'DISPATCHED', 'CANCELLED'])->default('DRAFT');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('sales_order_id')->references('order_id')->on('sales_orders')->onDelete('restrict');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('restrict');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('dispatched_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->unsignedInteger('delivery_note_id');
            $table->unsignedInteger('order_item_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('batch_id');
            $table->decimal('quantity_dispatched', 12, 2);
            $table->timestamps();

            $table->foreign('delivery_note_id')->references('delivery_note_id')->on('delivery_notes')->onDelete('cascade');
            $table->foreign('order_item_id')->references('item_id')->on('sales_order_items')->onDelete('restrict');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delivery_note_items');
        Schema::dropIfExists('delivery_notes');
    }
};
