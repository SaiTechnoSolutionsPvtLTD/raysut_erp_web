<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_payments', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->unsignedInteger('invoice_id');
            $table->unsignedInteger('customer_id');
            $table->decimal('amount', 12, 3);
            $table->string('method', 50)->default('BANK_TRANSFER'); // CASH, CARD, BANK_TRANSFER, CHEQUE
            $table->string('reference', 100)->nullable();
            $table->unsignedInteger('received_by')->nullable();
            $table->timestamp('received_at')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('invoice_id')->on('sales_invoices')->onDelete('cascade');
            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('restrict');
            $table->foreign('received_by')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_payments');
    }
};
