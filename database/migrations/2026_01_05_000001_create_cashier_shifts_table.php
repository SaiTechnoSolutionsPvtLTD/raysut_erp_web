<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cashier_shifts', function (Blueprint $table) {
            $table->increments('shift_id');
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('counter_id');
            $table->unsignedInteger('branch_id');
            $table->timestamp('opened_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->decimal('opening_float', 12, 3)->default(0.000);
            $table->decimal('expected_cash', 12, 3)->default(0.000);
            $table->decimal('counted_cash', 12, 3)->nullable();
            $table->decimal('card_total', 12, 3)->default(0.000);
            $table->decimal('variance', 12, 3)->default(0.000);
            $table->string('variance_status', 30)->default('NONE');
            $table->unsignedInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('OPEN');
            $table->timestamps();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('counter_id')->references('counter_id')->on('counters')->onDelete('cascade');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            $table->foreign('approved_by')->references('user_id')->on('users')->onDelete('set null');

            $table->index(['user_id', 'status']);
            $table->index(['counter_id', 'status']);
            $table->index(['branch_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashier_shifts');
    }
};
