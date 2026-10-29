<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retail_sales', function (Blueprint $table) {
            $table->increments('id');
            $table->string('receipt_no', 60)->unique();
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('counter_id');
            $table->unsignedInteger('cashier_shift_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('oman_permit_id')->nullable();
            $table->string('customer_civil_id', 50)->nullable();
            $table->decimal('subtotal', 12, 3);
            $table->decimal('vat_total', 12, 3);
            $table->decimal('grand_total', 12, 3);
            $table->string('payment_method', 20)->default('CASH'); // CASH, CARD, SPLIT
            $table->decimal('cash_amount', 12, 3)->default(0.000);
            $table->decimal('card_amount', 12, 3)->default(0.000);
            $table->string('card_reference', 100)->nullable();
            $table->decimal('change_given', 12, 3)->default(0.000);
            $table->string('status', 20)->default('COMPLETED'); // COMPLETED, VOIDED
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            $table->foreign('counter_id')->references('counter_id')->on('counters')->onDelete('cascade');
            $table->foreign('cashier_shift_id')->references('shift_id')->on('cashier_shifts')->onDelete('cascade');
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('oman_permit_id')->references('permit_id')->on('oman_permits')->onDelete('set null');

            $table->index(['branch_id', 'status']);
            $table->index(['counter_id', 'status']);
            $table->index(['cashier_shift_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retail_sales');
    }
};
