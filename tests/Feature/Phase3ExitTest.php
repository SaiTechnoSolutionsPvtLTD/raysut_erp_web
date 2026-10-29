<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\OmanPermit;
use App\Models\Product;
use App\Models\RetailSale;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WholesaleQuotation;
use App\Services\CreditService;
use App\Services\DeliveryNoteService;
use App\Services\InvoiceService;
use App\Services\PermitService;
use App\Services\PosCheckoutService;
use App\Services\QuotationService;
use App\Services\SalesOrderService;
use App\Services\ShiftService;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\BranchOperations\ShiftManagement;
use App\Livewire\Sales\Wholesale\SalesInvoiceManagement;
use App\Livewire\Sales\Wholesale\SalesOrderManagement;

class Phase3ExitTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $hqAdmin;
    protected User $branchAdminMct;
    protected User $supervisorMct;
    protected User $cashierMct;
    protected User $cashierSll;

    protected Branch $branchMct;
    protected Branch $branchSll;
    protected Counter $counterMct;
    protected Counter $counterSll;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed Phase 1, Phase 2, and Phase 3 Demo foundation
        $this->seed();
        Artisan::call('db:seed', ['--class' => 'Phase3DemoSeeder']);

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->firstOrFail();
        $this->branchSll = Branch::where('branch_code', 'BR-SLL')->firstOrFail();

        $this->counterMct = Counter::where('branch_id', $this->branchMct->branch_id)->firstOrFail();
        $this->counterSll = Counter::where('branch_id', $this->branchSll->branch_id)->firstOrFail();

        // Users
        $this->superAdmin = User::where('username', 'admin')->firstOrFail();

        $this->hqAdmin = User::firstOrCreate(['username' => 'hq_admin_user'], [
            'full_name' => 'RMSC HQ Executive',
            'email' => 'hqadmin@rmsc.om',
            'password_hash' => bcrypt('password'),
            'default_branch_id' => null,
            'is_active' => true,
        ]);
        $hqRole = Role::where('role_name', 'HQ Admin')->firstOrFail();
        if (! $this->hqAdmin->hasRole('HQ Admin')) {
            $this->hqAdmin->roles()->attach($hqRole->role_id);
        }

        $this->branchAdminMct = User::firstOrCreate(['username' => 'mct_branch_admin'], [
            'full_name' => 'Muscat Branch Administrator',
            'email' => 'admin.mct@rmsc.om',
            'password_hash' => bcrypt('password'),
            'default_branch_id' => $this->branchMct->branch_id,
            'is_active' => true,
        ]);
        $branchAdminRole = Role::where('role_name', 'Branch Admin')->firstOrFail();
        if (! $this->branchAdminMct->hasRole('Branch Admin')) {
            $this->branchAdminMct->roles()->attach($branchAdminRole->role_id);
        }
        if (! $this->branchAdminMct->branches->contains($this->branchMct->branch_id)) {
            $this->branchAdminMct->branches()->attach($this->branchMct->branch_id);
        }

        $this->supervisorMct = User::where('username', 'supervisor_mct')->firstOrFail();
        $this->cashierMct = User::where('username', 'cashier01')->firstOrFail();
        $this->cashierSll = User::where('username', 'cashier_sll')->firstOrFail();

        $this->superAdmin->load('roles.permissions', 'branches');
        $this->hqAdmin->load('roles.permissions', 'branches');
        $this->branchAdminMct->load('roles.permissions', 'branches');
        $this->supervisorMct->load('roles.permissions', 'branches');
        $this->cashierMct->load('roles.permissions', 'branches');
        $this->cashierSll->load('roles.permissions', 'branches');
    }

    #[Test]
    public function phase3_end_to_end_walkthrough_steps_a_through_h()
    {
        $shiftService = app(ShiftService::class);
        $posService = app(PosCheckoutService::class);
        $permitService = app(PermitService::class);
        $quoteService = app(QuotationService::class);
        $soService = app(SalesOrderService::class);
        $creditService = app(CreditService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);
        $stockLedgerService = app(StockLedgerService::class);

        // --- STEP A: 4 Branches & Counters Listed ---
        $branches = Branch::whereIn('branch_code', ['BR-MCT', 'BR-SLL', 'BR-SOH', 'BR-NZW'])->get();
        $this->assertCount(4, $branches);
        foreach ($branches as $b) {
            $this->assertGreaterThanOrEqual(1, $b->counters()->count());
        }

        // --- STEP B: Open Cashier Shift with Float ---
        $shift = $shiftService->open($this->cashierMct, $this->counterMct->counter_id, 50.000, 'Phase 3 Exit Walkthrough Shift');
        $this->assertNotNull($shift);
        $this->assertEquals('OPEN', $shift->status);
        $this->assertEquals(50.000, (float) $shift->opening_float);

        // --- STEP C: Retail Sale (Non-Controlled) with VAT from Master Data ---
        $tonic = Product::where('sku_code', 'SKU-TONIC-CAN-250')->firstOrFail();
        $tonicBatch = StockBatch::where('batch_number', 'BATCH-BR-MCT-TONIC-DP')->firstOrFail();
        $mctWh = Warehouse::where('branch_id', $this->branchMct->branch_id)->where('warehouse_type', 'MAIN')->firstOrFail();

        $tonicStockBefore = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('batch_id', $tonicBatch->batch_id)->value('quantity_on_hand');

        $sale1 = $posService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [
                [
                    'product_id' => $tonic->product_id,
                    'batch_id' => $tonicBatch->batch_id,
                    'quantity' => 5.0,
                    'unit_price' => 1.000,
                ],
            ],
            [
                'method' => 'CASH',
                'cash_amount' => 10.000,
            ],
            null
        );

        $this->assertEquals(5.000, (float) $sale1->subtotal);
        $this->assertEquals(0.250, (float) $sale1->vat_total); // 5% VAT
        $this->assertEquals(5.250, (float) $sale1->grand_total);
        $this->assertEquals(4.750, (float) $sale1->change_given);

        $tonicStockAfter = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('batch_id', $tonicBatch->batch_id)->value('quantity_on_hand');
        $this->assertEquals($tonicStockBefore - 5.0, $tonicStockAfter);

        // --- STEP D: Retail Sale with Permit Verification (Manual Verification & Remaining Quota Enforcement) ---
        $liquor = Product::where('sku_code', 'SKU-JW-BLK-750')->firstOrFail();
        $liquorBatch = StockBatch::where('batch_number', 'BATCH-BR-MCT-JW-DP')->firstOrFail();

        // 1 Bottle @ 18.500 + 5% VAT (0.925) = 19.425 OMR <= 50.000 OMR -> PASS
        $manualPayload = [
            'civil_id' => '10293847',
            'holder_name' => 'Tariq Al-Balushi',
            'permit_no' => 'PERM-2026-0991',
            'annual_limit' => 500.000,
            'used_amount' => 100.000,
            'remaining_amount' => 50.000,
            'valid_from' => now()->startOfYear()->toDateString(),
            'valid_to' => now()->endOfYear()->toDateString(),
            'verified_at' => now()->toDateTimeString(),
            'entry_method' => 'MANUAL',
            'attestation' => true,
        ];

        $sale2 = $posService->checkout(
            $this->cashierMct,
            $this->counterMct,
            [
                [
                    'product_id' => $liquor->product_id,
                    'batch_id' => $liquorBatch->batch_id,
                    'quantity' => 1.0,
                    'unit_price' => 18.500,
                ],
            ],
            [
                'method' => 'CARD',
                'card_amount' => 19.425,
                'card_reference' => 'CARD-REF-P3',
            ],
            $manualPayload
        );

        $this->assertNotNull($sale2->permitVerification);
        $this->assertEquals('USED', $sale2->permitVerification->status);
        $this->assertEquals(19.425, (float) $sale2->permitVerification->controlled_total);

        // Attempt checkout where remaining quota (10.000 OMR) is less than cart controlled total (19.425 OMR) -> must block!
        $quotaExceededBlocked = false;
        try {
            $blockedPayload = [
                'civil_id' => '55443322',
                'holder_name' => 'Majid Al-Habsi',
                'remaining_amount' => 10.000,
                'verified_at' => now()->toDateTimeString(),
                'entry_method' => 'MANUAL',
                'attestation' => true,
            ];

            $posService->checkout(
                $this->cashierMct,
                $this->counterMct,
                [
                    [
                        'product_id' => $liquor->product_id,
                        'batch_id' => $liquorBatch->batch_id,
                        'quantity' => 1.0,
                        'unit_price' => 18.500,
                    ],
                ],
                [
                    'method' => 'CARD',
                    'card_amount' => 19.425,
                ],
                $blockedPayload
            );
        } catch (ValidationException | Exception $e) {
            $quotaExceededBlocked = true;
        }
        $this->assertTrue($quotaExceededBlocked, 'Sale where remaining permit amount < controlled total should be blocked.');

        // --- STEP E: Close Shift with Cash Variance & Supervisor Approval ---
        // Expected cash: 50.000 (float) + 5.250 (cash sale 1) = 55.250 OMR
        $countedCash = 57.250; // +2.000 OMR variance
        $closedShift = $shiftService->close($shift, $countedCash, 19.425, 'Closed with 2.000 OMR tip variance', 1.000);
        $this->assertEquals('FLAGGED', $closedShift->variance_status);
        $this->assertEquals(2.000, (float) $closedShift->variance);

        $approvedShift = $shiftService->approve($closedShift, $this->supervisorMct, 'Variance approved');
        $this->assertEquals('APPROVED', $approvedShift->variance_status);
        $this->assertEquals('RECONCILED', $approvedShift->status);
        $this->assertEquals($this->supervisorMct->user_id, $approvedShift->approved_by);

        // --- STEP F: Wholesale Lifecycle (Quote -> SO -> DN -> Tax Invoice -> Payment) ---
        $customer = Customer::where('customer_code', 'CUST-HYATT-MCT')->firstOrFail();

        // 1. Quotation
        $quote = $quoteService->create([
            'customer_id' => $customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(30)->toDateString(),
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $liquor->product_id,
                'batch_id' => $liquorBatch->batch_id,
                'quantity' => 10.0,
                'unit_price' => 15.200,
            ],
        ], $this->superAdmin);

        $quoteService->accept($quote, $this->superAdmin);
        $order = $quoteService->convertToSalesOrder($quote, (int) $mctWh->warehouse_id, $this->superAdmin);
        $this->assertEquals('CONVERTED', $quote->fresh()->status);

        // 2. Confirm Sales Order (Credit check & Stock Reservation)
        $totalAvailBefore = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('product_id', $liquor->product_id)->sum('quantity_available');

        $confirmedSO = $soService->confirm($order, $this->superAdmin);
        $this->assertEquals('CONFIRMED', $confirmedSO->status);

        $totalAvailAfter = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('product_id', $liquor->product_id)->sum('quantity_available');
        $this->assertEquals($totalAvailBefore - 10.0, $totalAvailAfter);

        $reservation = $confirmedSO->reservations->first();
        $this->assertNotNull($reservation);
        $this->assertEquals(10.0, (float) $reservation->quantity_reserved);

        $stockReservedAfter = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('batch_id', $reservation->batch_id)->value('quantity_reserved');
        $this->assertGreaterThanOrEqual(10.0, $stockReservedAfter);

        // 3. Delivery Note Dispatch
        $dn = $dnService->createAndDispatch($confirmedSO, [
            [
                'order_item_id' => $confirmedSO->items->first()->item_id,
                'quantity' => 10.0,
            ],
        ], ['driver_name' => 'Ahmed'], $this->superAdmin);
        $this->assertEquals('DISPATCHED', $dn->status);

        // 4. Post Wholesale Tax Invoice (Deducts on-hand stock and releases reservation)
        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals('POSTED', $invoice->status);
        $this->assertEquals(152.000, (float) $invoice->subtotal);
        $this->assertEquals(7.600, (float) $invoice->vat_total);
        $this->assertEquals(159.600, (float) $invoice->grand_total);

        // Reservation released
        $reservedAfterInv = (float) WarehouseStock::where('warehouse_id', $mctWh->warehouse_id)
            ->where('batch_id', $liquorBatch->batch_id)->value('quantity_reserved');
        $this->assertEquals(0.0, $reservedAfterInv);

        // 5. Payment Recording
        $payment = $invoiceService->recordPayment($invoice, [
            'amount' => 159.600,
            'payment_method' => 'BANK_TRANSFER',
            'reference' => 'TXN-BANK-SETTLE',
        ], $this->superAdmin);

        $this->assertEquals('PAID', $invoice->fresh()->status);
        $this->assertEquals(0.0, (float) $invoice->fresh()->balance_due);

        // --- STEP G: Credit Block on ON_HOLD Customer & Supervisor Override ---
        $holdCust = Customer::where('customer_code', 'CUST-DEMO-ONHOLD')->firstOrFail();
        $this->assertEquals('ON_HOLD', $holdCust->credit_status);

        $holdOrder = $soService->create([
            'customer_id' => $holdCust->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $mctWh->warehouse_id,
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $liquor->product_id,
                'batch_id' => $liquorBatch->batch_id,
                'quantity' => 5.0,
                'unit_price' => 15.200,
            ],
        ], $this->superAdmin);

        // Attempt confirm -> blocked because ON_HOLD
        $blocked = false;
        try {
            $soService->confirm($holdOrder, $this->superAdmin);
        } catch (ValidationException | \Exception $e) {
            $blocked = true;
            $this->assertEquals('BLOCKED', $holdOrder->fresh()->credit_check_status);
        }
        $this->assertTrue($blocked);

        // Supervisor override
        $overriddenOrder = $creditService->override($holdOrder, $this->supervisorMct, 'Emergency hotel banquet stock approval');
        $this->assertEquals('OVERRIDDEN', $overriddenOrder->credit_check_status);

        // Re-confirm -> succeeds
        $confirmedHoldOrder = $soService->confirm($overriddenOrder, $this->superAdmin);
        $this->assertEquals('CONFIRMED', $confirmedHoldOrder->status);

        // --- STEP H: Stock Ledger & Audit Trail Verification ---
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'CREDIT_OVERRIDE',
            'record_table' => 'sales_orders',
            'record_id' => $holdOrder->order_id,
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'POST_INVOICE',
            'record_table' => 'sales_invoices',
            'record_id' => $invoice->invoice_id,
        ]);
    }

    #[Test]
    public function regression_guard_phase1_and_phase2_exit_assertions_remain_valid()
    {
        // 1. Company Setup
        $this->assertDatabaseHas('companies', ['company_name' => 'Raysut Marketing & Supply Co. LLC (RMSC)']);

        // 2. Branches
        $this->assertGreaterThanOrEqual(4, Branch::count());

        // 3. Named Warehouses
        $this->assertDatabaseHas('warehouses', ['warehouse_name' => 'Muscat Central Bonded Warehouse', 'warehouse_type' => 'DUTY_FREE_BONDED']);
        $this->assertDatabaseHas('warehouses', ['warehouse_name' => 'Salalah Freezone Depot', 'warehouse_type' => 'DUTY_FREE_BONDED']);
        $this->assertDatabaseHas('warehouses', ['warehouse_name' => 'Sohar Bonded Terminal', 'warehouse_type' => 'DUTY_FREE_BONDED']);

        // 4. Super Admin
        $this->assertTrue($this->superAdmin->hasRole('Super Admin'));
    }

    #[Test]
    public function permission_matrix_across_all_phase3_roles()
    {
        // 1. Super Admin: full access to Phase 3 demo & wholesale
        $response = $this->actingAs($this->superAdmin)->get(route('phase3.demo'));
        $response->assertOk();

        // 2. Cashier: can access shifts and pos sales, but CANNOT access approvals or override
        $shift = ShiftServiceTestHelper::createShiftForUser($this->cashierMct, $this->counterMct);

        // Cashier cannot approve shift variance (requires APPROVE)
        Livewire::actingAs($this->cashierMct)
            ->test(ShiftManagement::class)
            ->call('openApproveModal', $shift->shift_id)
            ->assertForbidden();

        // Cashier cannot void a sales invoice
        $mctWh = Warehouse::where('branch_id', $this->branchMct->branch_id)->first();
        $testSO = SalesOrder::create([
            'so_no' => 'SO-TEST-CASHIER-01',
            'customer_id' => Customer::first()->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $mctWh->warehouse_id,
            'status' => 'CONFIRMED',
            'order_date' => now()->toDateString(),
            'subtotal' => 100,
            'vat_total' => 5,
            'grand_total' => 105,
            'payment_terms_days' => 30,
            'created_by' => $this->superAdmin->user_id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_no' => 'INV-TEST-001',
            'sales_order_id' => $testSO->order_id,
            'customer_id' => Customer::first()->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $mctWh->warehouse_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'payment_terms_days' => 30,
            'subtotal' => 100,
            'vat_total' => 5,
            'grand_total' => 105,
            'amount_paid' => 0,
            'status' => 'POSTED',
            'created_by' => $this->cashierMct->user_id,
        ]);
        Livewire::actingAs($this->cashierMct)
            ->test(SalesInvoiceManagement::class)
            ->call('openVoidModal', $invoice->invoice_id)
            ->assertForbidden();

        // 3. Supervisor: CAN approve shift variance
        $shift->variance_status = 'FLAGGED';
        $shift->status = 'CLOSED';
        $shift->variance = 5.000;
        $shift->save();
        Livewire::actingAs($this->supervisorMct)
            ->test(ShiftManagement::class)
            ->call('openApproveModal', $shift->shift_id)
            ->set('supervisorNotes', 'Approved by supervisor')
            ->call('executeApproveShift');
        $this->assertEquals('APPROVED', $shift->fresh()->variance_status);
        $this->assertEquals('RECONCILED', $shift->fresh()->status);

        // 4. HQ Admin: has global access
        $response = $this->actingAs($this->hqAdmin)->get(route('wholesale.orders.index'));
        $response->assertOk();
    }

    #[Test]
    public function branch_isolation_across_retail_and_wholesale_and_shifts()
    {
        // Create an order in Branch SLL
        $sllCust = Customer::first();
        $sllWh = Warehouse::where('branch_id', $this->branchSll->branch_id)->first() ?? Warehouse::create([
            'company_id' => $this->branchSll->company_id,
            'branch_id' => $this->branchSll->branch_id,
            'warehouse_code' => 'WH-SLL-ISO',
            'warehouse_name' => 'Salalah Isolation Wh',
            'warehouse_type' => 'MAIN',
            'is_active' => true,
        ]);

        $orderSll = SalesOrder::create([
            'so_no' => 'SO-SLL-2026-9999',
            'customer_id' => $sllCust->customer_id,
            'branch_id' => $this->branchSll->branch_id,
            'warehouse_id' => $sllWh->warehouse_id,
            'status' => 'DRAFT',
            'order_date' => now()->toDateString(),
            'subtotal' => 100,
            'vat_total' => 5,
            'grand_total' => 105,
            'payment_terms_days' => 30,
            'created_by' => $this->cashierSll->user_id,
        ]);

        // Muscat staff cannot see SLL sales order (branch isolated)
        Livewire::actingAs($this->cashierMct)
            ->test(SalesOrderManagement::class)
            ->assertDontSee($orderSll->so_no);
        $this->assertFalse($orderSll->branch_id === $this->cashierMct->default_branch_id);
    }

    #[Test]
    public function smoke_test_all_phase3_sidebar_links_return_200_for_super_admin()
    {
        // Ensure shift is open for POS terminal
        ShiftServiceTestHelper::createShiftForUser($this->superAdmin, $this->counterMct);

        $routes = [
            'shifts.index',
            'system.branches',
            'system.counters',
            'pos.terminal',
            'permits.index',
            'wholesale.quotations.index',
            'wholesale.orders.index',
            'wholesale.delivery-notes.index',
            'wholesale.invoices.index',
            'wholesale.credit.index',
            'phase3.demo',
        ];

        foreach ($routes as $route) {
            $response = $this->actingAs($this->superAdmin)->get(route($route));
            $this->assertEquals(200, $response->getStatusCode(), "Failed asserting route [{$route}] returns 200 OK.");
        }
    }

    #[Test]
    public function void_sales_invoice_blocked_if_payments_exist()
    {
        $invoiceService = app(InvoiceService::class);
        $customer = Customer::where('customer_code', 'CUST-HYATT-MCT')->firstOrFail();
        $mctWh = Warehouse::where('branch_id', $this->branchMct->branch_id)->first();

        $testSO = SalesOrder::create([
            'so_no' => 'SO-TEST-PAID-01',
            'customer_id' => $customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $mctWh->warehouse_id,
            'status' => 'CONFIRMED',
            'order_date' => now()->toDateString(),
            'subtotal' => 100,
            'vat_total' => 5,
            'grand_total' => 105,
            'payment_terms_days' => 30,
            'created_by' => $this->superAdmin->user_id,
        ]);

        $invoice = SalesInvoice::create([
            'invoice_no' => 'INV-TEST-PAID-001',
            'sales_order_id' => $testSO->order_id,
            'customer_id' => $customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $mctWh->warehouse_id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'payment_terms_days' => 30,
            'subtotal' => 100,
            'vat_total' => 5,
            'grand_total' => 105,
            'amount_paid' => 0,
            'status' => 'POSTED',
            'created_by' => $this->superAdmin->user_id,
        ]);

        // Record partial payment
        $invoiceService->recordPayment($invoice, [
            'amount' => 50.000,
            'payment_method' => 'CASH',
            'reference' => 'RCPT-001',
        ], $this->superAdmin);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Cannot void invoice ' . $invoice->invoice_no . ' because payments have already been recorded. Reverse payments first.');

        $invoiceService->void($invoice, $this->superAdmin, 'Trying to void paid invoice');
    }

    #[Test]
    public function console_command_expires_overdue_sent_quotations()
    {
        $customer = Customer::where('customer_code', 'CUST-HYATT-MCT')->firstOrFail();

        // 1. Overdue quotation in SENT status
        $overdueQuote = WholesaleQuotation::create([
            'quote_no' => 'WQ-MCT-2026-9001',
            'customer_id' => $customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->subDay()->toDateString(),
            'status' => 'SENT',
            'subtotal' => 50,
            'vat_total' => 2.5,
            'grand_total' => 52.5,
            'payment_terms_days' => 30,
            'created_by' => $this->superAdmin->user_id,
        ]);

        // 2. Active quotation in SENT status
        $activeQuote = WholesaleQuotation::create([
            'quote_no' => 'WQ-MCT-2026-9002',
            'customer_id' => $customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(7)->toDateString(),
            'status' => 'SENT',
            'subtotal' => 50,
            'vat_total' => 2.5,
            'grand_total' => 52.5,
            'payment_terms_days' => 30,
            'created_by' => $this->superAdmin->user_id,
        ]);

        // Execute console command
        Artisan::call('wholesale:expire-quotations');

        $this->assertEquals('EXPIRED', $overdueQuote->fresh()->status);
        $this->assertEquals('SENT', $activeQuote->fresh()->status);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'AUTO_EXPIRE_QUOTATION',
            'record_table' => 'wholesale_quotations',
            'record_id' => $overdueQuote->quotation_id,
        ]);
    }

    #[Test]
    public function phase3_demo_run_and_reset_endpoints_work_as_expected()
    {
        // 1. Run demo simulation
        $response = $this->actingAs($this->superAdmin)->post(route('phase3.demo.run'));
        $response->assertRedirect(route('phase3.demo'));
        $response->assertSessionHas('success');
        $this->assertNotNull(session('phase3_demo_results'));
        $this->assertEquals('PASS', session('phase3_demo_results')['overall_status']);

        // 2. Reset demo data
        $response = $this->actingAs($this->superAdmin)->post(route('phase3.demo.reset'));
        $response->assertRedirect(route('phase3.demo'));
        $response->assertSessionHas('success');
        $this->assertNull(session('phase3_demo_results'));
    }
}

class ShiftServiceTestHelper
{
    public static function createShiftForUser(User $user, Counter $counter): \App\Models\CashierShift
    {
        $existing = $user->openShift;
        if ($existing) {
            return $existing;
        }

        return \App\Models\CashierShift::create([
            'counter_id' => $counter->counter_id,
            'branch_id' => $counter->branch_id,
            'user_id' => $user->user_id,
            'opened_at' => now(),
            'opening_float' => 50.000,
            'status' => 'OPEN',
        ]);
    }
}
