<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_batches', function (Blueprint $table) {
            $table->increments('batch_id');
            $table->string('batch_number', 50);
            $table->unsignedInteger('product_id');
            $table->date('manufacture_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedInteger('source_grn_id')->nullable();
            $table->enum('duty_status', ['DUTY_FREE_BONDED', 'DUTY_PAID'])->default('DUTY_FREE_BONDED');

            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('source_grn_id')->references('grn_id')->on('goods_receipt_notes')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_batches');
    }
};
