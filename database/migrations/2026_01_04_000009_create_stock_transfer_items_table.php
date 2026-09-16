<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->bigIncrements('transfer_item_id');
            $table->unsignedInteger('transfer_id');
            $table->unsignedInteger('batch_id');
            $table->decimal('quantity', 12, 2);

            $table->foreign('transfer_id')->references('transfer_id')->on('stock_transfers')->onDelete('cascade');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
    }
};
