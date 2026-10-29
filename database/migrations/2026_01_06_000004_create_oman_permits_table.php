<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oman_permits', function (Blueprint $table) {
            $table->increments('permit_id');
            $table->string('civil_id', 50)->unique();
            $table->string('holder_name', 150);
            $table->string('phone', 50)->nullable();
            $table->string('permit_no', 50)->unique();
            $table->decimal('annual_limit', 12, 3)->default(500.000);
            $table->decimal('used_quota', 12, 3)->default(0.000);
            $table->date('valid_from');
            $table->date('valid_to');
            $table->string('status', 20)->default('ACTIVE'); // ACTIVE, SUSPENDED, EXPIRED
            $table->timestamps();

            $table->index(['civil_id', 'status']);
            $table->index(['permit_no', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oman_permits');
    }
};
