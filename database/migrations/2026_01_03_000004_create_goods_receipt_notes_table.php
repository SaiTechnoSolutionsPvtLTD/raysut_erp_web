<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_receipt_notes', function (Blueprint $table) {
            $table->increments('grn_id');
            $table->string('grn_number', 30);
            $table->unsignedInteger('po_id');
            $table->unsignedInteger('shipment_id')->nullable();
            $table->unsignedInteger('warehouse_id');
            $table->date('received_date');
            $table->unsignedInteger('received_by')->nullable();
            $table->boolean('has_discrepancy')->default(false);

            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->onDelete('restrict');
            $table->foreign('shipment_id')->references('shipment_id')->on('import_shipments')->onDelete('set null');
            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('received_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_notes');
    }
};
