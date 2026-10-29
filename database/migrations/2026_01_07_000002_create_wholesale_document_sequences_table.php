<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wholesale_document_sequences', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('branch_id');
            $table->string('doc_type', 10); // WQ, SO, DN, INV
            $table->string('sequence_year', 4); // YYYY
            $table->unsignedInteger('last_sequence')->default(0);
            $table->timestamps();

            $table->unique(['branch_id', 'doc_type', 'sequence_year'], 'ws_doc_seq_branch_type_year_uniq');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wholesale_document_sequences');
    }
};
