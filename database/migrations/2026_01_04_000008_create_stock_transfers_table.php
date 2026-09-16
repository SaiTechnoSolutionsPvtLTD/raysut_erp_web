<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->increments('transfer_id');
            $table->string('transfer_number', 30);
            $table->unsignedInteger('source_warehouse_id');
            $table->unsignedInteger('destination_warehouse_id');
            $table->date('transfer_date');
            $table->enum('status', ['DRAFT', 'IN_TRANSIT', 'RECEIVED', 'CANCELLED'])->default('DRAFT');
            $table->unsignedInteger('requested_by')->nullable();

            $table->foreign('source_warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('destination_warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('requested_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfers');
    }
};
