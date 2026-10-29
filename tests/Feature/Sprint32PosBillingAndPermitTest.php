<?php

namespace Tests\Feature;

use App\Livewire\Sales\Retail\PermitManagement;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\PermitVerification;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPricingTier;
use App\Models\RetailSale;
use App\Models\Role;
use App\Models\StockBatch;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\Permit\Drivers\AnthropicPermitExtractor;
use App\Services\Permit\Drivers\FakePermitExtractor;
use App\Services\Permit\PermitExtractionResult;
use App\Services\Permit\PermitScreenshotExtractor;
use App\Services\Permit\PermitVerificationService;
use App\Services\PosCheckoutService;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Sprint32PosBillingAndPermitTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashierMct;
    protected User $cashierSll;
    protected Branch $branchMct;
    protected Branch $branchSll;
    protected Counter $counterMct;
    protected Counter $counterSll;
    protected Warehouse $warehouseMct;
    protected Warehouse $warehouseSll;
    protected Product $whiskyProduct;
    protected Product $nonControlledProduct;
    protected StockBatch $dutyPaidBatch;
    protected StockBatch $dutySuspendedBatch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('username', 'admin')->first();
        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->attach($superAdminRole->role_id);
        }

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->first();
        $this->branchSll = Branch::where('branch_code', 'BR-SLL')->first();

        $this->counterMct = Counter::firstOrCreate(
            ['counter_code' => 'CNT-MCT-01'],
            [
                'branch_id' => $this->branchMct->branch_id,
                'counter_name' => 'Muscat POS 01',
                'is_active' => true,
            ]
        );

        $this->counterSll = Counter::firstOrCreate(
            ['counter_code' => 'CNT-SLL-01'],
            [
                'branch_id' => $this->branchSll->branch_id,
                'counter_name' => 'Salalah POS 01',
                'is_active' => true,
            ]
        );

        $this->warehouseMct = Warehouse::where('branch_id', $this->branchMct->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchMct->company_id,
                'branch_id' => $this->branchMct->branch_id,
                'warehouse_code' => 'WH-MCT-MAIN',
                'warehouse_name' => 'Muscat Main Store',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]);

        $this->warehouseSll = Warehouse::where('branch_id', $this->branchSll->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchSll->company_id,
                'branch_id' => $this->branchSll->branch_id,
                'warehouse_code' => 'WH-SLL-MAIN',
                'warehouse_name' => 'Salalah Main Store',
                'warehouse_type' => 'MAIN',
                'is_active' => true,
            ]);

        $cashierRole = Role::where('role_name', 'Cashier')->first();

        $this->cashierMct = User::where('username', 'cashier01')->first() ?? User::factory()->create([
            'username' => 'cashier_mct',
            'full_name' => 'Muscat Cashier',
            'email' => 'cashier_mct@rmsc.om',
            'default_branch_id' => $this->branchMct->branch_id,
        ]);
        if ($cashierRole && ! $this->cashierMct->hasRole('Cashier')) {
            $this->cashierMct->roles()->attach($cashierRole->role_id);
        }

        $this->cashierSll = User::factory()->create([
            'username' => 'cashier_sll',
            'full_name' => 'Salalah Cashier',
            'email' => 'cashier_sll@rmsc.om',
            'default_branch_id' => $this->branchSll->branch_id,
        ]);
        if ($cashierRole && ! $this->cashierSll->hasRole('Cashier')) {
            $this->cashierSll->roles()->attach($cashierRole->role_id);
        }

        $this->cashierMct->load('roles.permissions');
        $this->cashierSll->load('roles.permissions');

        // Controlled Product (Whisky)
        $this->whiskyProduct = Product::where('sku_code', 'SKU-JW-BLK-750')->first();
        if (! $this->whiskyProduct) {
            $whiskyCat = ProductCategory::firstOrCreate(['category_name' => 'Scotch Whisky']);
            $uomBtl = Uom::firstOrCreate(['uom_code' => 'BTL'], ['uom_name' => 'Bottle']);
            $vat5 = TaxVatRate::where('rate_percent', 5.000)->first();

            $this->whiskyProduct = Product::create([
                'sku_code' => 'SKU-JW-BLK-750',
                'barcode' => '5000267014005',
                'product_name' => 'Johnnie Walker Black Label 750ml',
                'category_id' => $whiskyCat->category_id,
                'base_uom_id' => $uomBtl->uom_id,
                'tax_rate_id' => $vat5->tax_rate_id,
                'is_permit_required' => true,
                'is_active' => true,
            ]);
        }
        $this->whiskyProduct->update(['is_permit_required' => true]);

        ProductPricingTier::updateOrCreate([
            'product_id' => $this->whiskyProduct->product_id,
            'price_tier' => 'RETAIL',
        ], [
            'min_quantity' => 1.00,
            'unit_price' => 10.000,
            'effective_from' => '2026-01-01',
        ]);

        // Non-controlled Product (Tumbler glass)
        $generalCat = ProductCategory::firstOrCreate(['category_name' => 'Accessories']);
        $uomPcs = Uom::firstOrCreate(['uom_code' => 'PCS'], ['uom_name' => 'Pieces']);
        $vat5 = TaxVatRate::where('rate_percent', 5.000)->first();

        $this->nonControlledProduct = Product::firstOrCreate([
            'sku_code' => 'SKU-GLASS-TUMBLER',
        ], [
            'barcode' => '9900112233445',
            'product_name' => 'Crystal Tumbler Glass Set',
            'category_id' => $generalCat->category_id,
            'base_uom_id' => $uomPcs->uom_id,
            'tax_rate_id' => $vat5->tax_rate_id,
            'is_permit_required' => false,
            'is_active' => true,
        ]);
        $this->nonControlledProduct->update(['is_permit_required' => false]);

        ProductPricingTier::firstOrCreate([
            'product_id' => $this->nonControlledProduct->product_id,
            'price_tier' => 'RETAIL',
        ], [
            'min_quantity' => 1.00,
            'unit_price' => 4.000,
            'effective_from' => '2026-01-01',
        ]);

        $ledger = app(StockLedgerService::class);

        $this->dutyPaidBatch = StockBatch::firstOrCreate([
            'batch_number' => 'BATCH-DP-POS-01',
        ], [
            'product_id' => $this->whiskyProduct->product_id,
            'duty_status' => 'DUTY_PAID',
            'expiry_date' => now()->addYear(),
        ]);

        $this->dutySuspendedBatch = StockBatch::firstOrCreate([
            'batch_number' => 'BATCH-BONDED-POS-01',
        ], [
            'product_id' => $this->whiskyProduct->product_id,
            'duty_status' => 'DUTY_FREE_BONDED',
            'expiry_date' => now()->addYear(),
        ]);

        $ledger->recordIntake(
            $this->warehouseMct->warehouse_id,
            $this->whiskyProduct->product_id,
            $this->dutyPaidBatch->batch_id,
            100.0
        );

        $glassBatch = StockBatch::firstOrCreate([
            'batch_number' => 'BATCH-GLASS-01',
        ], [
            'product_id' => $this->nonControlledProduct->product_id,
            'duty_status' => 'DUTY_PAID',
        ]);
        $ledger->recordIntake(
            $this->warehouseMct->warehouse_id,
            $this->nonControlledProduct->product_id,
            $glassBatch->batch_id,
            50.0
        );
    }

    protected function validPermitPayload(array $overrides = []): array
    {
        return array_merge([
            'civil_id' => '10459823',
            'holder_name' => 'Ahmed Al-Harthy',
            'permit_no' => 'ROP-10459823-26',
            'annual_limit' => 500.000,
            'used_amount' => 50.000,
            'remaining_amount' => 450.000,
            'valid_from' => now()->startOfYear()->toDateString(),
            'valid_to' => now()->endOfYear()->toDateString(),
            'verified_at' => now()->toDateTimeString(),
            'entry_method' => 'MANUAL',
            'attested' => true,
            'attestation' => true,
        ], $overrides);
    }

    protected function makeFakePng(string $name = 'permit.png'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "\x89PNG\r\n\x1a\n\x00\x00\x00\rIHDR\x00\x00\x00\x01\x00\x00\x00\x01\x08\x06\x00\x00\x00\x1f\x15c4\x00\x00\x00\nIDATx\x9cc\x00\x01\x00\x00\x05\x00\x01\r\n-\xb4\x00\x00\x00\x00IEND\xaeB`\x82"
        );
    }

    protected function makeFakeJpg(string $name = 'permit.jpg'): UploadedFile
    {
        return UploadedFile::fake()->createWithContent(
            $name,
            "\xFF\xD8\xFF\xE0\x00\x10JFIF\x00\x01\x01\x01\x00H\x00H\x00\x00\xFF\xDB\x00C\x00\xFF\xC0\x00\x0B\x08\x00\x01\x00\x01\x01\x01\x11\x00\xFF\xDA\x00\x08\x01\x01\x00\x00?\x00\xBF\x00\xFF\xD9"
        );
    }

    #[Test]
    public function checkout_success_deducts_stock_and_totals_vat_from_master_data(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $initialStock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->where('batch_id', $this->dutyPaidBatch->batch_id)
            ->first();
        $initialQty = (float) $initialStock->quantity_available;

        $this->actingAs($this->cashierMct);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                [
                    'product_id' => $this->whiskyProduct->product_id,
                    'quantity' => 2,
                ],
            ],
            'payment' => [
                'method' => 'CASH',
                'cash_amount' => 25.000,
            ],
            'permit_verification' => $this->validPermitPayload(),
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'grand_total' => 21.000,
            'change_given' => 4.000,
        ]);

        $stockAfter = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->where('batch_id', $this->dutyPaidBatch->batch_id)
            ->first();
        $this->assertEquals($initialQty - 2.0, (float) $stockAfter->quantity_available);

        $this->assertDatabaseHas('retail_sales', [
            'branch_id' => $this->branchMct->branch_id,
            'counter_id' => $this->counterMct->counter_id,
            'cashier_shift_id' => $shift->shift_id,
            'subtotal' => 20.000,
            'vat_total' => 1.000,
            'grand_total' => 21.000,
            'payment_method' => 'CASH',
            'cash_amount' => 25.000,
            'change_given' => 4.000,
            'status' => 'COMPLETED',
        ]);
    }

    #[Test]
    public function checkout_is_blocked_without_an_open_shift(): void
    {
        $this->actingAs($this->cashierMct);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                [
                    'product_id' => $this->whiskyProduct->product_id,
                    'quantity' => 1,
                ],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload(),
        ]);

        $response->assertStatus(403);
        $this->assertDatabaseCount('retail_sales', 0);
    }

    #[Test]
    public function checkout_is_blocked_when_stock_is_duty_suspended(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $ledger = app(StockLedgerService::class);
        $ledger->recordIntake(
            $this->warehouseMct->warehouse_id,
            $this->whiskyProduct->product_id,
            $this->dutySuspendedBatch->batch_id,
            50.0
        );

        $this->actingAs($this->cashierMct);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                [
                    'product_id' => $this->whiskyProduct->product_id,
                    'quantity' => 1,
                    'batch_id' => $this->dutySuspendedBatch->batch_id,
                ],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 25.000],
            'permit_verification' => $this->validPermitPayload(),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('duty-suspended', $response->json('error'));
        $this->assertDatabaseCount('retail_sales', 0);
    }

    #[Test]
    public function insufficient_stock_rolls_back_entire_sale(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                [
                    'product_id' => $this->whiskyProduct->product_id,
                    'quantity' => 5000,
                ],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 60000.000],
            'permit_verification' => $this->validPermitPayload(['remaining_amount' => 60000.000]),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('Insufficient duty-paid stock', $response->json('error'));

        $this->assertDatabaseCount('retail_sales', 0);
        $this->assertDatabaseCount('retail_sale_items', 0);
        $this->assertDatabaseCount('permit_verifications', 0);
    }

    #[Test]
    public function manual_verification_with_controlled_item_succeeds_and_creates_verification_linked_to_sale(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        $payload = $this->validPermitPayload([
            'civil_id' => '10459823',
            'holder_name' => 'Ahmed Al-Harthy',
            'remaining_amount' => 100.000,
        ]);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $payload,
        ]);

        $response->assertStatus(200);

        $sale = RetailSale::firstOrFail();
        $this->assertNotNull($sale->permitVerification);
        $this->assertEquals('USED', $sale->permitVerification->status);
        $this->assertEquals('MANUAL', $sale->permitVerification->entry_method);
        $this->assertEquals(10.500, (float) $sale->permitVerification->controlled_total);
        $this->assertEquals('Ahmed Al-Harthy', $sale->permitVerification->holder_name);
        $this->assertEquals('10459823', $sale->permitVerification->civil_id);
        $this->assertEquals('******9823', $sale->permitVerification->masked_civil_id);

        $this->assertDatabaseHas('permit_verifications', [
            'retail_sale_id' => $sale->id,
            'branch_id' => $this->branchMct->branch_id,
            'counter_id' => $this->counterMct->counter_id,
            'cashier_shift_id' => $shift->shift_id,
            'status' => 'USED',
            'entry_method' => 'MANUAL',
            'civil_id_last4' => '9823',
        ]);
    }

    #[Test]
    public function controlled_item_without_verification_is_blocked_and_non_controlled_cart_needs_none(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        // 1. Controlled item without permit verification -> Blocked 422
        $response1 = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
        ]);
        $response1->assertStatus(422);
        $response1->assertJsonValidationErrors(['permit_verification']);

        // 2. Non-controlled item without verification -> Allowed 200
        $response2 = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 5.000],
        ]);
        $response2->assertStatus(200);
        $this->assertDatabaseHas('retail_sales', [
            'grand_total' => 4.200,
        ]);
    }

    #[Test]
    public function permit_verification_validation_rules_attestation_format_and_dates(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        // 1. Missing attestation -> blocked
        $resMissingAttestation = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload(['attested' => false, 'attestation' => false]),
        ]);
        $resMissingAttestation->assertStatus(422);

        // 2. Invalid Civil ID format (e.g. short or letters) -> blocked
        $resInvalidCivilId = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload(['civil_id' => 'ABC12']),
        ]);
        $resInvalidCivilId->assertStatus(422);

        // 3. Future verified_at -> blocked
        $resFutureVerified = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload([
                'verified_at' => now()->addDays(2)->toDateTimeString(),
            ]),
        ]);
        $resFutureVerified->assertStatus(422);

        // 4. Stale verified_at (> 24 hours old) -> blocked
        $resStaleVerified = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload([
                'verified_at' => now()->subHours(25)->toDateTimeString(),
            ]),
        ]);
        $resStaleVerified->assertStatus(422);

        // 5. Expired permit (valid_to in the past) -> blocked
        $resExpired = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload([
                'valid_to' => now()->subDay()->toDateString(),
            ]),
        ]);
        $resExpired->assertStatus(422);
    }

    #[Test]
    public function remaining_amount_less_than_controlled_total_blocks_with_no_stock_change(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $initialStock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->first()->quantity_available;

        $this->actingAs($this->cashierMct);

        // Cart is 10.500 OMR; remaining amount is only 5.000 OMR
        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload(['remaining_amount' => 5.000]),
        ]);

        $response->assertStatus(422);
        $this->assertStringContainsString('exceeds remaining quota', $response->json('error'));

        // Zero stock change, zero sales written
        $stockAfter = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->first()->quantity_available;
        $this->assertEquals((float) $initialStock, (float) $stockAfter);
        $this->assertDatabaseCount('retail_sales', 0);
        $this->assertDatabaseCount('permit_verifications', 0);

        // Toggle permit_block_when_exceeds = false allows checkout
        config(['rmsc.permit_block_when_exceeds' => false]);
        $responseAllowed = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $this->validPermitPayload(['remaining_amount' => 5.000]),
        ]);
        $responseAllowed->assertStatus(200);
        $this->assertDatabaseCount('retail_sales', 1);
    }

    #[Test]
    public function screenshot_extraction_endpoint_returns_fields_and_confidence(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $fakeExtractor = new FakePermitExtractor();
        $fakeExtractor->setResult(new PermitExtractionResult(
            fields: [
                'civil_id' => '10459823',
                'holder_name' => 'Ahmed Al-Harthy',
                'permit_no' => 'ROP-10459823-26',
                'annual_limit' => 500.000,
                'used_amount' => 50.000,
                'remaining_amount' => 450.000,
                'valid_from' => '2026-01-01',
                'valid_to' => '2026-12-31',
                'verified_at' => now()->toDateTimeString(),
            ],
            confidences: [
                'civil_id' => 0.98,
                'holder_name' => 0.95,
                'remaining_amount' => 0.75, // Low confidence (< 0.8)
            ],
            overallConfidence: 0.89,
            rawText: 'Sultanate of Oman Liquor Permit'
        ));

        $this->app->instance(PermitScreenshotExtractor::class, $fakeExtractor);

        $this->actingAs($this->cashierMct);

        $fakeImage = $this->makeFakePng('government_permit.png');

        $response = $this->postJson(route('pos.permit.extract'), [
            'screenshot' => $fakeImage,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'data' => [
                'fields' => [
                    'civil_id' => '10459823',
                    'holder_name' => 'Ahmed Al-Harthy',
                    'remaining_amount' => 450.000,
                ],
                'overall_confidence' => 0.89,
            ],
        ]);

        $this->assertNotEmpty($response->json('data.screenshot_token'));
        $this->assertContains('remaining_amount', $response->json('data.low_confidence_fields'));
    }

    #[Test]
    public function screenshot_extraction_with_anthropic_driver_using_http_fake(): void
    {
        config(['rmsc.permit_extraction.driver' => 'anthropic']);
        config(['rmsc.permit_extraction.anthropic.api_key' => 'test-fake-key']);

        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => json_encode([
                            'fields' => [
                                'civil_id' => '١٠٤٥٩٨٢٣', // Arabic-Indic digits
                                'holder_name' => 'Salim Al-Harthy',
                                'permit_no' => 'ROP-١٠٤٥',
                                'annual_limit' => '٥٠٠.٠٠٠',
                                'used_amount' => '٥٠.٠٠٠',
                                'remaining_amount' => '٤٥٠.٠٠٠',
                                'valid_from' => '2026-01-01',
                                'valid_to' => '2026-12-31',
                                'verified_at' => '2026-10-27 10:00:00',
                            ],
                            'confidences' => [
                                'civil_id' => 0.99,
                                'holder_name' => 0.95,
                                'remaining_amount' => 0.95,
                            ],
                            'overall_confidence' => 0.96,
                            'raw_text_snippet' => 'Oman Royal Police Permit',
                        ]),
                    ],
                ],
            ], 200),
        ]);

        $extractor = new AnthropicPermitExtractor();
        $fakeImage = $this->makeFakeJpg('permit_arabic.jpg');

        $result = $extractor->extract($fakeImage);

        // Assert Arabic-Indic digits normalized to Western digits
        $this->assertEquals('10459823', $result->fields['civil_id']);
        $this->assertEquals(500.000, (float) $result->fields['annual_limit']);
        $this->assertEquals(450.000, (float) $result->fields['remaining_amount']);
        $this->assertEquals('Salim Al-Harthy', $result->fields['holder_name']);
        $this->assertEquals(0.96, $result->overallConfidence);
    }

    #[Test]
    public function screenshot_extraction_malformed_provider_output_rejected_cleanly(): void
    {
        config(['rmsc.permit_extraction.driver' => 'anthropic']);
        config(['rmsc.permit_extraction.anthropic.api_key' => 'test-fake-key']);

        Http::fake([
            'https://api.anthropic.com/v1/messages' => Http::response([
                'content' => [
                    [
                        'type' => 'text',
                        'text' => 'This is not valid JSON output from LLM',
                    ],
                ],
            ], 200),
        ]);

        $this->actingAs($this->cashierMct);

        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $fakeImage = $this->makeFakePng('permit_bad.png');

        $response = $this->postJson(route('pos.permit.extract'), [
            'screenshot' => $fakeImage,
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response->json('success'));
        $this->assertStringContainsString('extract', strtolower($response->json('error')));
    }

    #[Test]
    public function screenshot_extraction_unsupported_or_oversized_file_rejected(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        // 1. PDF is not allowed (only images)
        $fakePdf = UploadedFile::fake()->create('document.pdf', 500, 'application/pdf');
        $resPdf = $this->postJson(route('pos.permit.extract'), ['screenshot' => $fakePdf]);
        $resPdf->assertStatus(422);

        // 2. Oversized image (> 5 MB)
        $fakeOversized = UploadedFile::fake()->create('huge_permit.png', 6000, 'image/png');
        $resHuge = $this->postJson(route('pos.permit.extract'), ['screenshot' => $fakeOversized]);
        $resHuge->assertStatus(422);
    }

    #[Test]
    public function cashier_edits_to_extracted_fields_are_recorded_in_fields_edited_after_extraction(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        $payload = $this->validPermitPayload([
            'entry_method' => 'SCREENSHOT',
            'extraction_confidence' => 0.85,
            'fields_edited_after_extraction' => ['holder_name', 'remaining_amount'],
        ]);

        $response = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $payload,
        ]);

        $response->assertStatus(200);

        $verification = PermitVerification::firstOrFail();
        $this->assertEquals('SCREENSHOT', $verification->entry_method);
        $this->assertEquals(0.85, (float) $verification->extraction_confidence);
        $this->assertEquals(['holder_name', 'remaining_amount'], $verification->fields_edited_after_extraction);
    }

    #[Test]
    public function screenshot_attached_on_checkout_and_orphan_purge_command_removes_old(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        $fakeExtractor = new FakePermitExtractor();
        $fakeExtractor->setResult(new PermitExtractionResult(
            fields: $this->validPermitPayload(),
            confidences: ['civil_id' => 0.99],
            overallConfidence: 0.95
        ));
        $this->app->instance(PermitScreenshotExtractor::class, $fakeExtractor);

        $fakeImage = $this->makeFakePng('permit_upload.png');
        $extractRes = $this->postJson(route('pos.permit.extract'), ['screenshot' => $fakeImage]);
        $extractRes->assertStatus(200);

        $token = $extractRes->json('data.screenshot_token');
        $this->assertNotEmpty($token);

        // Checkout attaches screenshot via token
        $payload = $this->validPermitPayload([
            'entry_method' => 'SCREENSHOT',
            'screenshot_token' => $token,
        ]);

        $checkoutRes = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 10.500],
            'permit_verification' => $payload,
        ]);
        $checkoutRes->assertStatus(200);

        $verification = PermitVerification::firstOrFail();
        $this->assertNotNull($verification->screenshot_path);

        // Test orphan purge command removes unattached screenshots older than 24h
        $orphanDir = storage_path('app/private/permit-screenshots/test_branch/2025/01');
        File::makeDirectory($orphanDir, 0755, true, true);
        $oldFile = "{$orphanDir}/old_orphan.png";
        file_put_contents($oldFile, 'fake_png_data');
        touch($oldFile, time() - (25 * 3600)); // 25 hours ago

        $this->assertTrue(file_exists($oldFile));
        Artisan::call('permits:purge-orphan-screenshots');
        $this->assertFalse(file_exists($oldFile));
    }

    #[Test]
    public function supervisor_can_void_sale_marking_verification_voided_and_keeping_screenshot(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $initialStock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->where('batch_id', $this->dutyPaidBatch->batch_id)
            ->first()->quantity_available;

        $checkoutService = app(PosCheckoutService::class);

        $payload = $this->validPermitPayload([
            'entry_method' => 'SCREENSHOT',
            'screenshot_token' => 'demo_token_path',
        ]);
        $payload['screenshot_path'] = 'permit-screenshots/1/2026/10/demo.png';

        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 2]],
            ['method' => 'CASH', 'cash_amount' => 25.000],
            $payload
        );

        $this->assertEquals('COMPLETED', $sale->status);
        $this->assertEquals('USED', $sale->permitVerification->status);

        // Supervisor voids the sale
        $supervisor = User::factory()->create([
            'username' => 'supervisor_pos',
            'email' => 'supervisor_pos@raysut.om',
            'default_branch_id' => $this->branchMct->branch_id,
        ]);
        $supRole = Role::firstOrCreate(['role_name' => 'Supervisor']);
        $supervisor->roles()->attach($supRole->role_id);
        $supervisor->load('roles.permissions');

        $voidedSale = $checkoutService->void($sale, 'Customer returned items with sealed bottles', $supervisor);

        $this->assertEquals('VOIDED', $voidedSale->status);

        // Permit verification marked VOIDED but screenshot preserved
        $sale->permitVerification->refresh();
        $this->assertEquals('VOIDED', $sale->permitVerification->status);
        $this->assertEquals('permit-screenshots/1/2026/10/demo.png', $sale->permitVerification->screenshot_path);

        // Stock reversed
        $stockAfterVoid = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('product_id', $this->whiskyProduct->product_id)
            ->where('batch_id', $this->dutyPaidBatch->batch_id)
            ->first()->quantity_available;
        $this->assertEquals((float) $initialStock, (float) $stockAfterVoid);
    }

    #[Test]
    public function non_supervisor_cannot_void_sale(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);
        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 10.000]
        );

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Unauthorized');

        $checkoutService->void($sale, 'Cashier unauthorized void test', $this->cashierMct);
    }

    #[Test]
    public function cannot_void_sale_in_closed_or_approved_shift(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);
        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 5.000]
        );

        $shiftService->close($shift, 54.200, 0.000);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Voids are only permitted while the shift is OPEN');

        $checkoutService->void($sale, 'Late void attempt', $this->superAdmin);
    }

    #[Test]
    public function voided_sale_excluded_from_shift_close_expected_totals(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);

        $sale1 = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 2]],
            ['method' => 'CASH', 'cash_amount' => 25.000],
            $this->validPermitPayload()
        );

        $sale2 = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 5.000]
        );

        $checkoutService->void($sale1, 'Voiding Sale 1', $this->superAdmin);

        // Expected cash = float (50.000) + Sale 2 net cash (4.200) = 54.200 OMR (Sale 1 excluded)
        $closedShift = $shiftService->close($shift, 54.200, 0.000);

        $this->assertEquals(54.200, (float) $closedShift->expected_cash);
        $this->assertEquals(0.000, (float) $closedShift->variance);
        $this->assertEquals('BALANCED', $closedShift->variance_status);
    }

    #[Test]
    public function cash_card_and_split_payment_validation_and_change_calculation(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        // 1. Cash Underpaid fails
        $resUnderpaid = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 3.000],
        ]);
        $resUnderpaid->assertStatus(422);

        // 2. Card Exact succeeds (4.200 OMR)
        $resCard = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            'payment' => [
                'method' => 'CARD',
                'card_amount' => 4.200,
                'card_reference' => 'VISA-AUTH-9912',
            ],
        ]);
        $resCard->assertStatus(200);

        // 3. Split Payment: 3.000 Cash + 2.200 Card = 5.200 (Change 1.000)
        $resSplit = $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            'payment' => [
                'method' => 'SPLIT',
                'cash_amount' => 3.000,
                'card_amount' => 2.200,
            ],
        ]);
        $resSplit->assertStatus(200);
        $this->assertDatabaseHas('retail_sales', [
            'payment_method' => 'SPLIT',
            'change_given' => 1.000,
        ]);
    }

    #[Test]
    public function shift_close_expected_cash_and_card_total_include_pos_sales(): void
    {
        $shiftService = app(ShiftService::class);
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $this->actingAs($this->cashierMct);

        // Sale 1: CASH 21.000
        $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 2]],
            'payment' => ['method' => 'CASH', 'cash_amount' => 30.000],
            'permit_verification' => $this->validPermitPayload(),
        ]);

        // Sale 2: CARD 4.200
        $this->postJson(route('pos.checkout'), [
            'cart' => [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            'payment' => ['method' => 'CARD', 'card_amount' => 4.200],
        ]);

        $closedShift = $shiftService->close($shift, 71.000, 4.200, 'Evening balancing');

        $this->assertEquals(71.000, (float) $closedShift->expected_cash);
        $this->assertEquals(4.200, (float) $closedShift->card_total);
        $this->assertEquals('BALANCED', $closedShift->variance_status);
    }

    #[Test]
    public function receipt_number_generates_consecutively_without_duplicates(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);
        $todayStr = now()->format('Ymd');

        $sale1 = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 5.000]
        );

        $sale2 = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->nonControlledProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 5.000]
        );

        $this->assertEquals("RS-MCT-{$todayStr}-0001", $sale1->receipt_no);
        $this->assertEquals("RS-MCT-{$todayStr}-0002", $sale2->receipt_no);
    }

    #[Test]
    public function receipt_renders_and_branch_isolation_of_sales_is_enforced(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);
        $saleMct = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 20.000],
            $this->validPermitPayload()
        );

        $this->actingAs($this->cashierMct);
        $receiptRes = $this->get(route('pos.receipt', $saleMct->id));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee($saleMct->receipt_no);
        $receiptRes->assertSee('Johnnie Walker Black Label');
        $receiptRes->assertSee('Ahmed Al-Harthy');
        $receiptRes->assertSee('******9823');

        $mctIndex = $this->get(route('pos.sales.index'));
        $mctIndex->assertStatus(200);
        $mctIndex->assertSee($saleMct->receipt_no);

        $this->actingAs($this->cashierSll);
        $sllIndex = $this->get(route('pos.sales.index'));
        $sllIndex->assertStatus(200);
        $sllIndex->assertDontSee($saleMct->receipt_no);
    }

    #[Test]
    public function civil_id_masked_in_receipt_audit_logs_and_sales_list_but_visible_to_permit_editors(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);

        $payload = $this->validPermitPayload([
            'civil_id' => '7788991234',
            'holder_name' => 'Masking Test Holder',
            'permit_no' => 'ROP-MASK-NOCOLLIDE',
        ]);

        $sale = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 20.000],
            $payload
        );

        // 1. Audit Log masks Civil ID
        $audit = AuditLog::where('record_table', 'retail_sales')
            ->where('record_id', $sale->id)
            ->where('action', 'SALE_CHECKOUT')
            ->first();
        $this->assertNotNull($audit);
        $this->assertEquals('******1234', $audit->details_json['civil_id']);

        // 2. Receipt renders masked Civil ID and not full ID
        $this->actingAs($this->cashierMct);
        $receiptRes = $this->get(route('pos.receipt', $sale->id));
        $receiptRes->assertStatus(200);
        $receiptRes->assertSee('******1234');
        $receiptRes->assertDontSee('7788991234');

        // 3. Sales list renders masked Civil ID and not full ID
        $salesListRes = $this->get(route('pos.sales.index'));
        $salesListRes->assertStatus(200);
        $salesListRes->assertSee('******1234');
        $salesListRes->assertDontSee('7788991234');

        // 4. Permit admin screen:
        // Cashier sees masked Civil ID and NO Add/Edit buttons
        Livewire::actingAs($this->cashierMct)
            ->test(PermitManagement::class)
            ->assertStatus(200)
            ->assertSee('******1234')
            ->assertDontSee('7788991234')
            ->assertDontSee('Add Permit')
            ->assertDontSee('Edit');

        // Super Admin sees full Civil ID
        Livewire::actingAs($this->superAdmin)
            ->test(PermitManagement::class)
            ->assertStatus(200)
            ->assertSee('7788991234')
            ->assertDontSee('Add Permit');
    }

    #[Test]
    public function permits_list_search_by_hash_last4_and_status(): void
    {
        $verification1 = PermitVerification::create([
            'branch_id' => $this->branchMct->branch_id,
            'counter_id' => $this->counterMct->counter_id,
            'entered_by' => $this->cashierMct->user_id,
            'civil_id' => '1122334455',
            'holder_name' => 'Khalid Al-Ghafri',
            'permit_no' => 'ROP-112233',
            'annual_limit' => 500.000,
            'remaining_amount' => 400.000,
            'verified_at' => now(),
            'entry_method' => 'MANUAL',
            'controlled_total' => 20.000,
            'status' => 'USED',
            'attested_by' => $this->cashierMct->user_id,
            'attested_at' => now(),
        ]);

        $verification2 = PermitVerification::create([
            'branch_id' => $this->branchSll->branch_id,
            'counter_id' => $this->counterSll->counter_id,
            'entered_by' => $this->cashierSll->user_id,
            'civil_id' => '9988776655',
            'holder_name' => 'Fatima Al-Mashani',
            'permit_no' => 'ROP-998877',
            'annual_limit' => 600.000,
            'remaining_amount' => 550.000,
            'verified_at' => now(),
            'entry_method' => 'SCREENSHOT',
            'controlled_total' => 15.000,
            'status' => 'VOIDED',
            'attested_by' => $this->cashierSll->user_id,
            'attested_at' => now(),
        ]);

        // Search by last-4
        Livewire::actingAs($this->superAdmin)
            ->test(PermitManagement::class)
            ->set('search', '4455')
            ->assertSee('Khalid Al-Ghafri')
            ->assertDontSee('Fatima Al-Mashani');

        // Search by full civil ID (uses civil_id_hash exact match)
        Livewire::actingAs($this->superAdmin)
            ->test(PermitManagement::class)
            ->set('search', '9988776655')
            ->assertSee('Fatima Al-Mashani')
            ->assertDontSee('Khalid Al-Ghafri');

        // Filter by status
        Livewire::actingAs($this->superAdmin)
            ->test(PermitManagement::class)
            ->set('statusFilter', 'VOIDED')
            ->assertSee('Fatima Al-Mashani')
            ->assertDontSee('Khalid Al-Ghafri');
    }

    #[Test]
    public function permits_screenshot_route_authorization_and_private_streaming(): void
    {
        $fakeRelativePath = 'permit-screenshots/BR-MCT/2026/10/test-preview.png';
        $fullPath = storage_path("app/private/{$fakeRelativePath}");
        File::makeDirectory(dirname($fullPath), 0755, true, true);
        file_put_contents($fullPath, 'fake_preview_png');

        $verification = PermitVerification::create([
            'branch_id' => $this->branchMct->branch_id,
            'counter_id' => $this->counterMct->counter_id,
            'entered_by' => $this->cashierMct->user_id,
            'civil_id' => '11223344',
            'holder_name' => 'Private Stream Test',
            'remaining_amount' => 300.000,
            'verified_at' => now(),
            'entry_method' => 'SCREENSHOT',
            'screenshot_path' => $fakeRelativePath,
            'controlled_total' => 20.000,
            'status' => 'USED',
            'attested_by' => $this->cashierMct->user_id,
            'attested_at' => now(),
        ]);

        // 1. Authorized user (Super Admin) streams screenshot successfully
        $this->actingAs($this->superAdmin);
        $resAuth = $this->get(route('permits.screenshot', $verification->id));
        $resAuth->assertStatus(200);

        // 2. Unauthorized user (Cashier without APPROVE permission) denied 403
        $this->actingAs($this->cashierMct);
        $resDenied = $this->get(route('permits.screenshot', $verification->id));
        $resDenied->assertStatus(403);
    }

    #[Test]
    public function same_verification_reusable_within_same_shift_and_day_if_remaining_quota_covers(): void
    {
        $shiftService = app(ShiftService::class);
        $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000);

        $checkoutService = app(PosCheckoutService::class);

        // First checkout with manual verification payload
        $payload = $this->validPermitPayload([
            'civil_id' => '10459823',
            'remaining_amount' => 100.000,
        ]);

        $sale1 = $checkoutService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1]],
            ['method' => 'CASH', 'cash_amount' => 20.000],
            $payload
        );
        $this->assertEquals('COMPLETED', $sale1->status);

        // Second checkout in same shift reusing the verified civil ID string
        // Remaining was 100 - 10.500 = 89.500 OMR, cart is 10.500 OMR -> succeeds!
        $this->actingAs($this->cashierMct);
        $resReuse = $this->postJson(route('pos.checkout'), [
            'cart' => [
                ['product_id' => $this->whiskyProduct->product_id, 'quantity' => 1],
            ],
            'payment' => ['method' => 'CASH', 'cash_amount' => 15.000],
            'civil_id' => '10459823',
        ]);

        $resReuse->assertStatus(200);
        $this->assertDatabaseCount('retail_sales', 2);
    }
}
