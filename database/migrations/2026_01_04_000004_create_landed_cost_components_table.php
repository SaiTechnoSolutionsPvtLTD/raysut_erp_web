<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landed_cost_components', function (Blueprint $table) {
            $table->increments('component_id');
            $table->unsignedInteger('assessment_id');
            $table->string('charge_type', 50);
            $table->decimal('amount', 14, 3);

            $table->foreign('assessment_id')->references('assessment_id')->on('duty_assessments')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landed_cost_components');
    }
};
