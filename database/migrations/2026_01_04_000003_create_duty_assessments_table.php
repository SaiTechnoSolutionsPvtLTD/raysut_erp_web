<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_assessments', function (Blueprint $table) {
            $table->increments('assessment_id');
            $table->unsignedInteger('shipment_id');
            $table->decimal('cif_value', 14, 3);
            $table->decimal('duty_rate_percent', 6, 3);
            $table->decimal('duty_amount', 14, 3);
            $table->decimal('other_charges', 14, 3)->default(0);
            $table->decimal('total_landed_cost', 14, 3);
            $table->unsignedInteger('assessed_by')->nullable();
            $table->date('assessed_date');

            $table->foreign('shipment_id')->references('shipment_id')->on('import_shipments')->onDelete('cascade');
            $table->foreign('assessed_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_assessments');
    }
};
