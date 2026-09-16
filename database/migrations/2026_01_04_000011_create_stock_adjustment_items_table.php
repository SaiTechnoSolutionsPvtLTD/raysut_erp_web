<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->bigIncrements('adjustment_item_id');
            $table->unsignedInteger('adjustment_id');
            $table->unsignedInteger('batch_id');
            $table->decimal('system_quantity', 12, 2);
            $table->decimal('counted_quantity', 12, 2);
            $table->decimal('variance_quantity', 12, 2);

            $table->foreign('adjustment_id')->references('adjustment_id')->on('stock_adjustments')->onDelete('cascade');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
