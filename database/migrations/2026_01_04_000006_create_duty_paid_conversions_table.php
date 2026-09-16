<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_paid_conversions', function (Blueprint $table) {
            $table->increments('conversion_id');
            $table->string('conversion_number', 30);
            $table->unsignedInteger('warehouse_id');
            $table->date('conversion_date');
            $table->unsignedInteger('assessment_id')->nullable();
            $table->unsignedInteger('approved_by')->nullable();
            $table->enum('status', ['DRAFT', 'APPROVED', 'POSTED'])->default('DRAFT');

            $table->foreign('warehouse_id')->references('warehouse_id')->on('warehouses')->onDelete('restrict');
            $table->foreign('assessment_id')->references('assessment_id')->on('duty_assessments')->onDelete('set null');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_paid_conversions');
    }
};
