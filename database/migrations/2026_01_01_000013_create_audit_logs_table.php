<?php

/**
 * Module 01: System, Security & Setup
 * Table: audit_logs
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->bigIncrements('audit_id');
            $table->unsignedInteger('user_id')->nullable();
            $table->string('module_name', 100);
            $table->string('action', 50);
            $table->string('record_table', 100)->nullable();
            $table->unsignedBigInteger('record_id')->nullable();
            $table->json('details_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
