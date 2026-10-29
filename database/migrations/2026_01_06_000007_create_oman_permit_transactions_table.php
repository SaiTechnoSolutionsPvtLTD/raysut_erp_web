<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oman_permit_transactions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('oman_permit_id');
            $table->unsignedInteger('retail_sale_id');
            $table->decimal('amount', 12, 3);
            $table->decimal('balance_after', 12, 3);
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('oman_permit_id')->references('permit_id')->on('oman_permits')->onDelete('cascade');
            $table->foreign('retail_sale_id')->references('id')->on('retail_sales')->onDelete('cascade');

            $table->index(['oman_permit_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oman_permit_transactions');
    }
};
