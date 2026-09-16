<?php

/**
 * Module 01: System, Security & Setup
 * Table: user_branch_access
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_branch_access', function (Blueprint $table) {
            $table->unsignedInteger('user_id');
            $table->unsignedInteger('branch_id');

            $table->primary(['user_id', 'branch_id']);
            $table->foreign('user_id')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_branch_access');
    }
};
