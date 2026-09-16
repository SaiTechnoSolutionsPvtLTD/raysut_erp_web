<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_shipments', function (Blueprint $table) {
            $table->increments('shipment_id');
            $table->string('shipment_ref', 30);
            $table->unsignedInteger('po_id');
            $table->string('bl_container_no', 50)->nullable();
            $table->string('port_of_entry', 100)->nullable();
            $table->string('bayan_declaration_no', 50)->nullable();
            $table->enum('stage', ['ORDERED', 'IN_TRANSIT', 'CUSTOMS_CLEARANCE', 'DUTY_ASSESSMENT', 'RECEIVED'])->default('ORDERED');
            $table->date('eta_date')->nullable();
            $table->date('actual_arrival_date')->nullable();

            $table->foreign('po_id')->references('po_id')->on('purchase_orders')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_shipments');
    }
};
