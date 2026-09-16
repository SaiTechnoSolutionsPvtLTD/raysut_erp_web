<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_adjustments', function (Blueprint $table) {
            $table->increments('adjustment_id');
            $table->string('adjustment_number', 30);
            $table->unsignedInteger('warehouse_id');
            $table->date('count_date');
            $table->enum('reason_code', ['BREAKAGE', 'THEFT', 'EXPIRY_WRITE_OFF', 'RECOUNT', 'OTHER'])->default('OTHER');
            $table->unsignedInteger('approved_by')->nullable();

            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_adjustments');
    }
};
