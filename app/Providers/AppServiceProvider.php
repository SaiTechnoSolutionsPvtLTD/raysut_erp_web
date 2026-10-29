<?php

namespace App\Providers;

use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\OmanPermit;
use App\Models\RetailSale;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\WholesaleQuotation;
use App\Policies\BranchPolicy;
use App\Policies\CashierShiftPolicy;
use App\Policies\CounterPolicy;
use App\Policies\CustomerPaymentPolicy;
use App\Policies\DeliveryNotePolicy;
use App\Policies\OmanPermitPolicy;
use App\Policies\RetailSalePolicy;
use App\Policies\SalesInvoicePolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\WholesaleQuotationPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(\App\Services\Permit\PermitScreenshotExtractor::class, function ($app) {
            $driver = config('rmsc.permit_extraction.driver', 'tesseract');
            return match ($driver) {
                'tesseract' => new \App\Services\Permit\TesseractExtractor(
                    parser: $app->make(\App\Services\Permit\PermitTextParser::class)
                ),
                'gemini' => new \App\Services\Permit\Drivers\GeminiPermitExtractor(),
                'anthropic' => new \App\Services\Permit\Drivers\AnthropicPermitExtractor(),
                'fake' => new \App\Services\Permit\Drivers\FakePermitExtractor(),
                default => new \App\Services\Permit\Drivers\NullPermitExtractor(),
            };
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(CashierShift::class, CashierShiftPolicy::class);
        Gate::policy(Branch::class, BranchPolicy::class);
        Gate::policy(Counter::class, CounterPolicy::class);
        Gate::policy(RetailSale::class, RetailSalePolicy::class);
        Gate::policy(OmanPermit::class, OmanPermitPolicy::class);
        Gate::policy(\App\Models\PermitVerification::class, \App\Policies\PermitVerificationPolicy::class);

        // Sprint 3.3 Wholesale & Credit Policies
        Gate::policy(\App\Models\Customer::class, \App\Policies\CustomerPolicy::class);
        Gate::policy(WholesaleQuotation::class, WholesaleQuotationPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(DeliveryNote::class, DeliveryNotePolicy::class);
        Gate::policy(SalesInvoice::class, SalesInvoicePolicy::class);
        Gate::policy(CustomerPayment::class, CustomerPaymentPolicy::class);
    }
}
