<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_paid_conversion_items', function (Blueprint $table) {
            $table->bigIncrements('conversion_item_id');
            $table->unsignedInteger('conversion_id');
            $table->unsignedInteger('batch_id');
            $table->decimal('quantity_converted', 12, 2);
            $table->decimal('duty_paid_unit_cost', 14, 3);

            $table->foreign('conversion_id')->references('conversion_id')->on('duty_paid_conversions')->onDelete('cascade');
            $table->foreign('batch_id')->references('batch_id')->on('stock_batches')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_paid_conversion_items');
    }
};
