<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            if (! Schema::hasColumn('customers', 'credit_limit')) {
                $table->decimal('credit_limit', 12, 3)->default(0)->after('oman_card_no');
            }
            if (! Schema::hasColumn('customers', 'payment_terms_days')) {
                $table->unsignedInteger('payment_terms_days')->default(30)->after('credit_limit');
            }
            if (! Schema::hasColumn('customers', 'credit_status')) {
                $table->enum('credit_status', ['ACTIVE', 'ON_HOLD'])->default('ACTIVE')->after('payment_terms_days');
            }
            if (! Schema::hasColumn('customers', 'customer_type')) {
                $table->enum('customer_type', ['RETAIL', 'WHOLESALE'])->default('WHOLESALE')->after('credit_status');
            }
            if (! Schema::hasColumn('customers', 'vat_number')) {
                $table->string('vat_number', 50)->nullable()->after('customer_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['credit_limit', 'payment_terms_days', 'credit_status', 'customer_type', 'vat_number']);
        });
    }
};
