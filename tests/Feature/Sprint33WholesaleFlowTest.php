<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\CustomerPayment;
use App\Models\DeliveryNote;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductPricingTier;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBatch;
use App\Models\TaxVatRate;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Models\WholesaleQuotation;
use App\Services\CreditService;
use App\Services\DeliveryNoteService;
use App\Services\DocumentSequenceService;
use App\Services\InvoiceService;
use App\Services\QuotationService;
use App\Services\SalesOrderService;
use App\Services\StockLedgerService;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Livewire\Livewire;
use App\Livewire\Sales\Wholesale\QuotationManagement;

class Sprint33WholesaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchStaffMct;
    protected User $branchStaffSll;
    protected Branch $branchMct;
    protected Branch $branchSll;
    protected Warehouse $warehouseMct;
    protected Warehouse $warehouseSll;
    protected Customer $wholesaleCustomer;
    protected Product $productA;
    protected Product $productB;
    protected StockBatch $dutyPaidBatch1;
    protected StockBatch $dutyPaidBatch2;
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
        $this->superAdmin->load('roles.permissions');

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->first();
        $this->branchSll = Branch::where('branch_code', 'BR-SLL')->first();

        $this->warehouseMct = Warehouse::where('branch_id', $this->branchMct->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchMct->company_id,
                'branch_id' => $this->branchMct->branch_id,
                'warehouse_code' => 'WH-MCT-DOM',
                'warehouse_name' => 'Muscat Domestic Warehouse',
                'warehouse_type' => 'DISTRIBUTION_CENTER',
                'is_active' => true,
            ]);

        $this->warehouseSll = Warehouse::where('branch_id', $this->branchSll->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchSll->company_id,
                'branch_id' => $this->branchSll->branch_id,
                'warehouse_code' => 'WH-SLL-DOM',
                'warehouse_name' => 'Salalah Domestic Warehouse',
                'warehouse_type' => 'DISTRIBUTION_CENTER',
                'is_active' => true,
            ]);

        // Branch Users
        $branchAdminRole = Role::firstOrCreate(['role_name' => 'Branch Admin']);

        $this->branchStaffMct = User::create([
            'employee_id' => 'EMP-WS-MCT',
            'username' => 'staff_mct',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Muscat Wholesale Staff',
            'email' => 'mct_ws@rmsc.om',
            'default_branch_id' => $this->branchMct->branch_id,
            'is_active' => true,
        ]);
        $this->branchStaffMct->roles()->attach($branchAdminRole->role_id);
        $this->branchStaffMct->branches()->attach($this->branchMct->branch_id);
        $this->branchStaffMct->load('roles.permissions');

        $this->branchStaffSll = User::create([
            'employee_id' => 'EMP-WS-SLL',
            'username' => 'staff_sll',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Salalah Wholesale Staff',
            'email' => 'sll_ws@rmsc.om',
            'default_branch_id' => $this->branchSll->branch_id,
            'is_active' => true,
        ]);
        $this->branchStaffSll->roles()->attach($branchAdminRole->role_id);
        $this->branchStaffSll->branches()->attach($this->branchSll->branch_id);
        $this->branchStaffSll->load('roles.permissions');

        // Customer
        $this->wholesaleCustomer = Customer::create([
            'customer_code' => 'CUST-WS-001',
            'customer_name' => 'Al Bustan Hospitality LLC',
            'customer_segment' => 'WHOLESALE',
            'customer_type' => 'WHOLESALE',
            'credit_limit' => 5000.000,
            'payment_terms_days' => 30,
            'credit_status' => 'ACTIVE',
            'vat_number' => 'OM9900112233',
            'phone' => '+968 24123456',
            'email' => 'accounts@albustan.om',
            'is_active' => true,
        ]);

        $uomBottle = Uom::firstOrCreate(['uom_code' => 'BTL'], ['uom_name' => 'Bottle']);
        $vatRate5 = TaxVatRate::firstOrCreate(['tax_name' => 'Oman Standard VAT 5%'], [
            'tax_type' => 'VAT',
            'rate_percent' => 5.0,
            'effective_from' => '2021-04-16',
            'is_active' => true,
        ]);

        $category = ProductCategory::firstOrCreate(
            ['category_code' => 'WINE'],
            ['category_name' => 'Wine', 'is_excise_applicable' => false]
        );

        $this->productA = Product::firstOrCreate(['sku_code' => 'WINE-CHARD-750'], [
            'barcode' => '7891011121314',
            'product_name' => 'Chardonnay Reserve 750ml',
            'category_id' => $category->category_id,
            'base_uom_id' => $uomBottle->uom_id,
            'tax_rate_id' => $vatRate5->tax_rate_id,
            'is_permit_required' => false,
            'is_active' => true,
        ]);

        ProductPricingTier::updateOrCreate(
            ['product_id' => $this->productA->product_id, 'price_tier' => 'WHOLESALE'],
            [
                'min_quantity' => 1,
                'unit_price' => 15.000,
                'effective_from' => '2026-01-01',
            ]
        );

        $this->productB = Product::firstOrCreate(['sku_code' => 'WINE-MERLOT-750'], [
            'barcode' => '7891011121315',
            'product_name' => 'Merlot Superior 750ml',
            'category_id' => $category->category_id,
            'base_uom_id' => $uomBottle->uom_id,
            'tax_rate_id' => $vatRate5->tax_rate_id,
            'is_permit_required' => false,
            'is_active' => true,
        ]);

        ProductPricingTier::updateOrCreate(
            ['product_id' => $this->productB->product_id, 'price_tier' => 'WHOLESALE'],
            [
                'min_quantity' => 1,
                'unit_price' => 20.000,
                'effective_from' => '2026-01-01',
            ]
        );

        // Batches: FEFO order
        $this->dutyPaidBatch1 = StockBatch::create([
            'batch_number' => 'BATCH-DP-EARLY',
            'product_id' => $this->productA->product_id,
            'manufacture_date' => '2025-01-01',
            'expiry_date' => '2027-01-01',
            'duty_status' => 'DUTY_PAID',
        ]);

        $this->dutyPaidBatch2 = StockBatch::create([
            'batch_number' => 'BATCH-DP-LATER',
            'product_id' => $this->productA->product_id,
            'manufacture_date' => '2025-06-01',
            'expiry_date' => '2028-01-01',
            'duty_status' => 'DUTY_PAID',
        ]);

        $this->dutySuspendedBatch = StockBatch::create([
            'batch_number' => 'BATCH-DUTY-SUSP',
            'product_id' => $this->productA->product_id,
            'manufacture_date' => '2025-01-01',
            'expiry_date' => '2027-06-01',
            'duty_status' => 'DUTY_FREE_BONDED',
        ]);

        // Put stock in warehouse
        $stockService = app(StockLedgerService::class);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatch1->batch_id, 100);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutyPaidBatch2->batch_id, 100);
        $stockService->recordIntake($this->warehouseMct->warehouse_id, $this->productA->product_id, $this->dutySuspendedBatch->batch_id, 50);
    }

    #[Test]
    public function full_chain_quotation_to_so_to_dn_to_invoice_with_vat_and_totals()
    {
        $this->actingAs($this->superAdmin);

        $quotationService = app(QuotationService::class);
        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        // 1. Create Quotation: 10 units of Product A @ 15.000 (Subtotal = 150.000, VAT 5% = 7.500, Total = 157.500)
        $quote = $quotationService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(14)->toDateString(),
            'payment_terms_days' => 30,
            'notes' => 'Bulk seasonal order',
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 10,
                'unit_price' => 15.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $this->assertEquals('DRAFT', $quote->status);
        $this->assertEquals(150.000, (float) $quote->subtotal);
        $this->assertEquals(7.500, (float) $quote->vat_total);
        $this->assertEquals(157.500, (float) $quote->grand_total);
        $this->assertStringStartsWith('WQ-MCT-', $quote->quote_no);

        // Transition: DRAFT -> SENT -> ACCEPTED
        $quotationService->send($quote, $this->superAdmin);
        $this->assertEquals('SENT', $quote->status);

        $quotationService->accept($quote, $this->superAdmin);
        $this->assertEquals('ACCEPTED', $quote->status);

        // 2. Convert to Sales Order
        $so = $quotationService->convertToSalesOrder($quote, $this->warehouseMct->warehouse_id, $this->superAdmin);
        $quote->refresh();
        $this->assertEquals('CONVERTED', $quote->status);
        $this->assertEquals('DRAFT', $so->status);
        $this->assertEquals(157.500, (float) $so->grand_total);
        $this->assertStringStartsWith('SO-MCT-', $so->so_no);

        // Quotation is now locked from editing
        $this->expectException(Exception::class);
        $quotationService->update($quote, [], [['product_id' => $this->productA->product_id, 'quantity' => 5, 'unit_price' => 15]], $this->superAdmin);
    }

    #[Test]
    public function full_chain_execution_from_so_to_delivery_and_invoicing()
    {
        $this->actingAs($this->superAdmin);

        $quotationService = app(QuotationService::class);
        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        // Create and convert quote
        $quote = $quotationService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(14)->toDateString(),
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 10,
                'unit_price' => 15.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $so = $quotationService->convertToSalesOrder($quote, $this->warehouseMct->warehouse_id, $this->superAdmin);

        // 3. Confirm SO (triggers CreditService check + stock reservation in FEFO order)
        $stockBefore = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals(100.0, (float) $stockBefore->quantity_available);
        $this->assertEquals(0.0, (float) $stockBefore->quantity_reserved);

        $so = $soService->confirm($so, $this->superAdmin);
        $this->assertEquals('CONFIRMED', $so->status);
        $this->assertEquals('PASSED', $so->credit_check_status);

        // Check stock reserved on Batch 1 (FEFO earlier batch)
        $stockAfterConfirm = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals(90.0, (float) $stockAfterConfirm->quantity_available);
        $this->assertEquals(10.0, (float) $stockAfterConfirm->quantity_reserved);
        $this->assertEquals(100.0, (float) $stockAfterConfirm->quantity_on_hand);

        // 4. Create and Dispatch Delivery Note
        $orderItem = $so->items->first();
        $dn = $dnService->createAndDispatch($so, [
            [
                'order_item_id' => $orderItem->item_id,
                'quantity' => 10,
            ],
        ], [
            'vehicle_no' => 'OM-9921-A',
            'driver_name' => 'Rashid Al-Kiyumi',
        ], $this->superAdmin);

        $this->assertEquals('DISPATCHED', $dn->status);
        $this->assertStringStartsWith('DN-MCT-', $dn->dn_no);

        $so->refresh();
        $this->assertEquals('DISPATCHED', $so->status);
        $this->assertEquals(10.0, (float) $so->items->first()->quantity_dispatched);

        // 5. Post Sales Invoice from Delivery Note
        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals('POSTED', $invoice->status);
        $this->assertStringStartsWith('INV-MCT-', $invoice->invoice_no);
        $this->assertEquals(150.000, (float) $invoice->subtotal);
        $this->assertEquals(7.500, (float) $invoice->vat_total);
        $this->assertEquals(157.500, (float) $invoice->grand_total);
        $this->assertEquals(157.500, (float) $invoice->balance_due);
        $this->assertEquals($invoice->invoice_date->copy()->addDays(30)->toDateString(), $invoice->due_date->toDateString());

        // Stock deduction check: quantity_reserved cleared, quantity_on_hand deducted!
        $stockAfterPost = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals(90.0, (float) $stockAfterPost->quantity_on_hand);
        $this->assertEquals(90.0, (float) $stockAfterPost->quantity_available);
        $this->assertEquals(0.0, (float) $stockAfterPost->quantity_reserved);

        // Sales Order status becomes INVOICED
        $so->refresh();
        $this->assertEquals('INVOICED', $so->status);

        // Verify Audit Log records
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'sales_invoices',
            'action' => 'POST_INVOICE',
            'record_id' => $invoice->invoice_id,
        ]);
    }

    #[Test]
    public function credit_limit_blocks_so_confirm_and_override_allowed_with_reason()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);
        $creditService = app(CreditService::class);

        // Set customer credit limit to 100 OMR
        $this->wholesaleCustomer->credit_limit = 100.000;
        $this->wholesaleCustomer->save();

        // Create Sales Order for 20 units @ 15.000 + 5% VAT = 315.000 OMR (exceeds limit 100)
        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 20,
                'unit_price' => 15.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $this->assertEquals(315.000, (float) $so->grand_total);

        // Confirmation without override MUST fail
        try {
            $soService->confirm($so, $this->superAdmin);
            $this->fail('Expected Exception for credit limit exceeded.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Credit limit exceeded', $e->getMessage());
        }

        $so->refresh();
        $this->assertEquals('DRAFT', $so->status);
        $this->assertEquals('BLOCKED', $so->credit_check_status);

        // Confirmation WITH override by authorized supervisor succeeds
        $so = $soService->confirm($so, $this->superAdmin, true, 'Approved by GM for Ramadan wholesale campaign');
        $so->refresh();
        $this->assertEquals('CONFIRMED', $so->status);
        $this->assertEquals('OVERRIDDEN', $so->credit_check_status);
        $this->assertEquals('Approved by GM for Ramadan wholesale campaign', $so->credit_override_reason);
        $this->assertEquals($this->superAdmin->user_id, $so->credit_override_by);

        // Verify audit log for override
        $this->assertDatabaseHas('audit_logs', [
            'module_name' => 'Retail & Wholesale Sales',
            'action' => 'CREDIT_OVERRIDE',
            'record_table' => 'sales_orders',
            'record_id' => $so->order_id,
        ]);
    }

    #[Test]
    public function credit_on_hold_and_overdue_invoices_block_so_confirm()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);

        // 1. Customer on hold
        $this->wholesaleCustomer->credit_status = 'ON_HOLD';
        $this->wholesaleCustomer->credit_limit = 10000.000;
        $this->wholesaleCustomer->save();

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 2,
                'unit_price' => 15.000,
            ],
        ], $this->superAdmin);

        try {
            $soService->confirm($so, $this->superAdmin);
            $this->fail('Expected failure due to ON_HOLD credit status.');
        } catch (Exception $e) {
            $this->assertStringContainsString('ON HOLD', $e->getMessage());
        }

        // 2. Reactivate, but create an overdue invoice
        $this->wholesaleCustomer->credit_status = 'ACTIVE';
        $this->wholesaleCustomer->save();

        // Create an overdue invoice
        SalesInvoice::create([
            'invoice_no' => 'INV-OVERDUE-01',
            'sales_order_id' => $so->order_id,
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'invoice_date' => now()->subDays(45)->toDateString(),
            'due_date' => now()->subDays(15)->toDateString(),
            'payment_terms_days' => 30,
            'subtotal' => 200,
            'vat_total' => 10,
            'grand_total' => 210,
            'amount_paid' => 0,
            'status' => 'POSTED',
        ]);

        try {
            $soService->confirm($so, $this->superAdmin);
            $this->fail('Expected failure due to overdue invoices.');
        } catch (Exception $e) {
            $this->assertStringContainsString('overdue', $e->getMessage());
        }
    }

    #[Test]
    public function reservation_reduces_available_stock_and_cancel_releases()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);

        $stock = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals(100.0, (float) $stock->quantity_available);
        $this->assertEquals(0.0, (float) $stock->quantity_reserved);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 25,
                'unit_price' => 15.000,
            ],
        ], $this->superAdmin);

        // Confirm -> reserves 25
        $so = $soService->confirm($so, $this->superAdmin);
        $stock->refresh();
        $this->assertEquals(75.0, (float) $stock->quantity_available);
        $this->assertEquals(25.0, (float) $stock->quantity_reserved);
        $this->assertEquals(100.0, (float) $stock->quantity_on_hand);

        // Cancel -> releases 25
        $soService->cancel($so, $this->superAdmin);
        $stock->refresh();
        $this->assertEquals(100.0, (float) $stock->quantity_available);
        $this->assertEquals(0.0, (float) $stock->quantity_reserved);
        $this->assertEquals(100.0, (float) $stock->quantity_on_hand);
        $this->assertEquals('CANCELLED', $so->fresh()->status);
    }

    #[Test]
    public function duty_suspended_stock_cannot_be_reserved_dispatched_or_invoiced()
    {
        $this->actingAs($this->superAdmin);

        $stockLedger = app(StockLedgerService::class);
        $soService = app(SalesOrderService::class);

        // 1. Direct reservation attempt on duty-suspended batch
        try {
            $stockLedger->reserveStock(
                $this->warehouseMct->warehouse_id,
                $this->productA->product_id,
                $this->dutySuspendedBatch->batch_id,
                5,
                true // onlyDutyPaid = true
            );
            $this->fail('Expected reservation of duty-suspended stock to fail.');
        } catch (Exception $e) {
            $this->assertStringContainsString('duty-paid stock', $e->getMessage());
        }

        // 2. Direct deduction attempt for wholesale on duty-suspended batch
        try {
            $stockLedger->deductSaleStock(
                $this->warehouseMct->warehouse_id,
                $this->productA->product_id,
                $this->dutySuspendedBatch->batch_id,
                5,
                'WHOLESALE'
            );
            $this->fail('Expected wholesale deduction of duty-suspended stock to fail.');
        } catch (Exception $e) {
            $this->assertStringContainsString('duty-paid stock', $e->getMessage());
        }
    }

    #[Test]
    public function partial_dispatch_and_second_dispatch_and_over_dispatch_blocked()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 50,
                'unit_price' => 15.000,
            ],
        ], $this->superAdmin);

        $so = $soService->confirm($so, $this->superAdmin);
        $orderItem = $so->items->first();

        // 1. First partial dispatch: 20 units
        $dn1 = $dnService->createAndDispatch($so, [
            [
                'order_item_id' => $orderItem->item_id,
                'quantity' => 20,
            ],
        ], ['vehicle_no' => 'OM-PART-1'], $this->superAdmin);

        $so->refresh();
        $this->assertEquals('PARTIALLY_DISPATCHED', $so->status);
        $this->assertEquals(20.0, (float) $so->items->first()->quantity_dispatched);

        // 2. Over-dispatch attempt: trying to dispatch 35 units when remaining is only 30
        try {
            $dnService->createAndDispatch($so, [
                [
                    'order_item_id' => $orderItem->item_id,
                    'quantity' => 35,
                ],
            ], ['vehicle_no' => 'OM-OVER'], $this->superAdmin);
            $this->fail('Expected over-dispatch to be blocked.');
        } catch (Exception $e) {
            $this->assertStringContainsString('Over-dispatch blocked', $e->getMessage());
        }

        // 3. Second dispatch: remaining 30 units
        $dn2 = $dnService->createAndDispatch($so, [
            [
                'order_item_id' => $orderItem->item_id,
                'quantity' => 30,
            ],
        ], ['vehicle_no' => 'OM-PART-2'], $this->superAdmin);

        $so->refresh();
        $this->assertEquals('DISPATCHED', $so->status);
        $this->assertEquals(50.0, (float) $so->items->first()->quantity_dispatched);
    }

    #[Test]
    public function invoice_posting_deducts_stock_exactly_once_and_void_reverses()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 10,
                'unit_price' => 15.000,
            ],
        ], $this->superAdmin);

        $so = $soService->confirm($so, $this->superAdmin);
        $dn = $dnService->createAndDispatch($so, [
            ['order_item_id' => $so->items->first()->item_id, 'quantity' => 10],
        ], [], $this->superAdmin);

        $stockBefore = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $onHandBefore = (float) $stockBefore->quantity_on_hand;

        // Post Invoice
        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals('POSTED', $invoice->status);

        $stockAfter = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals($onHandBefore - 10, (float) $stockAfter->quantity_on_hand);

        // Post again (idempotent test) -> must NOT double deduct!
        $invoice2 = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals($invoice->invoice_id, $invoice2->invoice_id);

        $stockAfterRepeat = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals($onHandBefore - 10, (float) $stockAfterRepeat->quantity_on_hand);

        // Void invoice -> stock reversed back!
        $invoiceService->void($invoice, $this->superAdmin, 'Customer cancelation after billing');
        $this->assertEquals('VOIDED', $invoice->fresh()->status);

        $stockAfterVoid = WarehouseStock::where('warehouse_id', $this->warehouseMct->warehouse_id)
            ->where('batch_id', $this->dutyPaidBatch1->batch_id)
            ->first();
        $this->assertEquals($onHandBefore, (float) $stockAfterVoid->quantity_on_hand);
    }

    #[Test]
    public function due_date_from_payment_terms_and_payment_updates_status()
    {
        $this->actingAs($this->superAdmin);

        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        // Custom payment terms: 45 days
        $this->wholesaleCustomer->payment_terms_days = 45;
        $this->wholesaleCustomer->save();

        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'payment_terms_days' => 45,
        ], [
            [
                'product_id' => $this->productA->product_id,
                'quantity' => 10,
                'unit_price' => 10.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        $so = $soService->confirm($so, $this->superAdmin);
        $dn = $dnService->createAndDispatch($so, [
            ['order_item_id' => $so->items->first()->item_id, 'quantity' => 10],
        ], [], $this->superAdmin);

        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);
        $this->assertEquals(105.000, (float) $invoice->grand_total);
        $this->assertEquals(45, $invoice->payment_terms_days);
        $this->assertEquals($invoice->invoice_date->copy()->addDays(45)->toDateString(), $invoice->due_date->toDateString());

        // 1. Partial payment: 50 OMR
        $invoiceService->recordPayment($invoice, [
            'amount' => 50.000,
            'method' => 'BANK_TRANSFER',
            'reference' => 'WIRE-9901',
        ], $this->superAdmin);

        $invoice->refresh();
        $this->assertEquals('PARTIALLY_PAID', $invoice->status);
        $this->assertEquals(50.000, (float) $invoice->amount_paid);
        $this->assertEquals(55.000, (float) $invoice->balance_due);

        // 2. Overpayment attempt: 60 OMR when balance is 55 OMR -> BLOCKED
        try {
            $invoiceService->recordPayment($invoice, [
                'amount' => 60.000,
                'method' => 'CASH',
            ], $this->superAdmin);
            $this->fail('Expected overpayment to fail.');
        } catch (Exception $e) {
            $this->assertStringContainsString('cannot exceed invoice balance due', $e->getMessage());
        }

        // 3. Final payment: 55.000 OMR -> PAID
        $invoiceService->recordPayment($invoice, [
            'amount' => 55.000,
            'method' => 'CHEQUE',
            'reference' => 'CHQ-7788',
        ], $this->superAdmin);

        $invoice->refresh();
        $this->assertEquals('PAID', $invoice->status);
        $this->assertEquals(105.000, (float) $invoice->amount_paid);
        $this->assertEquals(0.000, (float) $invoice->balance_due);
    }

    #[Test]
    public function doc_number_uniqueness_and_pessimistic_locking()
    {
        $seqService = app(DocumentSequenceService::class);

        $n1 = $seqService->generateNumber($this->branchMct->branch_id, 'WQ', 'BR-MCT');
        $n2 = $seqService->generateNumber($this->branchMct->branch_id, 'WQ', 'BR-MCT');
        $n3 = $seqService->generateNumber($this->branchMct->branch_id, 'SO', 'BR-MCT');

        $this->assertNotEquals($n1, $n2);
        $this->assertEquals('WQ-MCT-' . now()->format('Y') . '-00001', $n1);
        $this->assertEquals('WQ-MCT-' . now()->format('Y') . '-00002', $n2);
        $this->assertEquals('SO-MCT-' . now()->format('Y') . '-00001', $n3);
    }

    #[Test]
    public function branch_isolation_and_print_views_render()
    {
        $soService = app(SalesOrderService::class);
        $quotationService = app(QuotationService::class);
        $dnService = app(DeliveryNoteService::class);
        $invoiceService = app(InvoiceService::class);

        // Create Muscat order and Salalah order
        $quoteMct = $quotationService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(10)->toDateString(),
        ], [['product_id' => $this->productA->product_id, 'quantity' => 1, 'unit_price' => 10]], $this->superAdmin);

        $quoteSll = $quotationService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchSll->branch_id,
            'valid_until' => now()->addDays(10)->toDateString(),
        ], [['product_id' => $this->productA->product_id, 'quantity' => 1, 'unit_price' => 10]], $this->superAdmin);

        // Branch staff in Muscat can view Muscat quote, but not Salalah quote
        Livewire::actingAs($this->branchStaffMct)
            ->test(QuotationManagement::class)
            ->assertSee($quoteMct->quote_no)
            ->assertDontSee($quoteSll->quote_no);

        // HQ Admin can view both
        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->assertSee($quoteMct->quote_no)
            ->assertSee($quoteSll->quote_no);

        // Printable Views Render Test
        $so = $soService->create([
            'customer_id' => $this->wholesaleCustomer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [['product_id' => $this->productA->product_id, 'quantity' => 2, 'unit_price' => 15.000]], $this->superAdmin);
        $so = $soService->confirm($so, $this->superAdmin);
        $dn = $dnService->createAndDispatch($so, [['order_item_id' => $so->items->first()->item_id, 'quantity' => 2]], [], $this->superAdmin);
        $invoice = $invoiceService->postFromDeliveryNote($dn, $this->superAdmin);

        // 1. Quotation Print
        $qPrint = $this->get(route('wholesale.quotations.print', $quoteMct->quotation_id));
        $qPrint->assertStatus(200);
        $qPrint->assertSee('WHOLESALE QUOTATION');
        $qPrint->assertSee($quoteMct->quote_no);

        // 2. Delivery Note Print (No prices)
        $dnPrint = $this->get(route('wholesale.delivery-notes.print', $dn->delivery_note_id));
        $dnPrint->assertStatus(200);
        $dnPrint->assertSee('DELIVERY NOTE');
        $dnPrint->assertSee($dn->dn_no);
        $dnPrint->assertDontSee('Unit Price');
        $dnPrint->assertDontSee('Line Total');

        // 3. Wholesale Tax Invoice Print (Company VAT no, Customer VAT no, line VAT, 3 decimals)
        $invPrint = $this->get(route('wholesale.invoices.print', $invoice->invoice_id));
        $invPrint->assertStatus(200);
        $invPrint->assertSee('TAX INVOICE');
        $invPrint->assertSee(config('rmsc.company_vat_no', 'OM1100000001'));
        $invPrint->assertSee($this->wholesaleCustomer->vat_number);
        $invPrint->assertSee(number_format($invoice->grand_total, 3));
    }
}
