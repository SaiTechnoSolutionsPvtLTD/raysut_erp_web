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
        Schema::create('permit_verifications', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedBigInteger('retail_sale_id')->nullable()->unique();
            $table->unsignedInteger('branch_id');
            $table->unsignedInteger('counter_id')->nullable();
            $table->unsignedInteger('cashier_shift_id')->nullable();
            $table->unsignedInteger('entered_by');
            $table->text('civil_id'); // Encrypted cast
            $table->string('civil_id_hash', 64)->index(); // HMAC-SHA256 for search & duplicate checks
            $table->string('civil_id_last4', 4)->nullable()->index(); // Last 4 digits for rapid searching
            $table->string('holder_name', 150);
            $table->string('permit_no', 100)->nullable();
            $table->decimal('annual_limit', 12, 3)->nullable();
            $table->decimal('used_amount', 12, 3)->nullable();
            $table->decimal('remaining_amount', 12, 3)->nullable();
            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();
            $table->dateTime('verified_at');
            $table->string('entry_method', 20)->default('MANUAL'); // MANUAL | SCREENSHOT
            $table->string('screenshot_path', 255)->nullable();
            $table->decimal('extraction_confidence', 5, 4)->nullable();
            $table->json('extraction_raw')->nullable();
            $table->json('fields_edited_after_extraction')->nullable();
            $table->decimal('controlled_total', 12, 3)->default(0.000);
            $table->string('status', 20)->default('PENDING'); // PENDING | USED | VOIDED
            $table->dateTime('attested_at')->nullable();
            $table->unsignedInteger('attested_by')->nullable();
            $table->text('attestation_text')->nullable();
            $table->timestamps();

            $table->foreign('retail_sale_id')->references('id')->on('retail_sales')->onDelete('set null');
            $table->foreign('branch_id')->references('branch_id')->on('branches')->onDelete('cascade');
            $table->foreign('counter_id')->references('counter_id')->on('counters')->onDelete('set null');
            $table->foreign('cashier_shift_id')->references('shift_id')->on('cashier_shifts')->onDelete('set null');
            $table->foreign('entered_by')->references('user_id')->on('users')->onDelete('cascade');
            $table->foreign('attested_by')->references('user_id')->on('users')->onDelete('set null');

            $table->index(['branch_id', 'status']);
            $table->index(['verified_at']);
            $table->index(['entry_method']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permit_verifications');
    }
};
