<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_quotations', function (Blueprint $table) {
            $table->increments('quotation_id');
            $table->string('quote_no', 50)->unique();
            $table->unsignedInteger('customer_id');
            $table->unsignedInteger('branch_id');
            $table->date('valid_until');
            $table->enum('status', ['DRAFT', 'SENT', 'ACCEPTED', 'REJECTED', 'EXPIRED', 'CONVERTED'])->default('DRAFT');
            $table->decimal('subtotal', 12, 3)->default(0);
            $table->decimal('vat_total', 12, 3)->default(0);
            $table->decimal('grand_total', 12, 3)->default(0);
            $table->unsignedInteger('payment_terms_days')->default(30);
            $table->text('notes')->nullable();
            $table->unsignedInteger('created_by')->nullable();
            $table->timestamps();

            $table->foreign('customer_id')->references('customer_id')->on('customers')->onDelete('restrict');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('restrict');
            $table->foreign('created_by')->references('user_id')->on('users')->onDelete('set null');
        });

        Schema::create('wholesale_quotation_items', function (Blueprint $table) {
            $table->increments('item_id');
            $table->unsignedInteger('quotation_id');
            $table->unsignedInteger('product_id');
            $table->unsignedInteger('uom_id');
            $table->decimal('quantity', 12, 2);
            $table->decimal('unit_price', 12, 3);
            $table->decimal('vat_rate', 5, 2)->default(0);
            $table->decimal('vat_amount', 12, 3)->default(0);
            $table->decimal('line_total', 12, 3)->default(0);
            $table->timestamps();

            $table->foreign('quotation_id')->references('quotation_id')->on('wholesale_quotations')->onDelete('cascade');
            $table->foreign('product_id')->references('product_id')->on('products')->onDelete('restrict');
            $table->foreign('uom_id')->references('uom_id')->on('uom')->onDelete('restrict');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_quotation_items');
        Schema::dropIfExists('wholesale_quotations');
    }
};
