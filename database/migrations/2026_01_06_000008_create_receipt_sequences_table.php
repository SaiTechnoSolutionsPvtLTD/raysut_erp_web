<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receipt_sequences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('branch_id');
            $table->date('sequence_date');
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            $table->unique(['branch_id', 'sequence_date'], 'receipt_seq_branch_date_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('receipt_sequences');
    }
};
