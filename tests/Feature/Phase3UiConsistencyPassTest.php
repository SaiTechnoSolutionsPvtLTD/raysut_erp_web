<?php

namespace Tests\Feature;

use App\Livewire\BranchOperations\BranchManagement;
use App\Livewire\BranchOperations\CounterManagement;
use App\Livewire\BranchOperations\ShiftManagement;
use App\Livewire\Sales\Retail\PermitManagement;
use App\Livewire\Sales\Wholesale\CustomerCreditManagement;
use App\Livewire\Sales\Wholesale\DeliveryNoteManagement;
use App\Livewire\Sales\Wholesale\QuotationManagement;
use App\Livewire\Sales\Wholesale\SalesInvoiceManagement;
use App\Livewire\Sales\Wholesale\SalesOrderManagement;
use App\Models\Branch;
use App\Models\CashierShift;
use App\Models\Counter;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\OmanPermit;
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
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class Phase3UiConsistencyPassTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $cashierUser;
    protected Branch $branchMct;
    protected Warehouse $warehouseMct;
    protected Customer $customer;
    protected Product $product;
    protected StockBatch $dutyPaidBatch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('username', 'admin')->firstOrFail();
        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->attach($superAdminRole->role_id);
        }

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->first()
            ?: Branch::firstOrCreate(['branch_code' => 'BR-MCT'], [
                'branch_name' => 'Muscat Central Branch',
                'branch_type' => 'RETAIL',
                'city' => 'Muscat',
                'is_active' => true,
            ]);

        $this->warehouseMct = Warehouse::where('branch_id', $this->branchMct->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first()
            ?: Warehouse::firstOrCreate(['warehouse_code' => 'WH-MCT-DOM'], [
                'warehouse_name' => 'Muscat Domestic Store',
                'branch_id' => $this->branchMct->branch_id,
                'warehouse_type' => 'DOMESTIC_DUTY_PAID',
                'is_active' => true,
            ]);

        $this->cashierUser = User::where('username', 'cashier_mct')->first()
            ?: User::factory()->create([
                'username' => 'cashier_mct',
                'email' => 'cashier_mct@rmsc.om',
                'default_branch_id' => $this->branchMct->branch_id,
                'is_active' => true,
            ]);
        $cashierRole = Role::firstOrCreate(['role_name' => 'Cashier']);
        if (! $this->cashierUser->hasRole('Cashier')) {
            $this->cashierUser->roles()->attach($cashierRole->role_id);
            $this->cashierUser->refresh();
        }

        $this->customer = Customer::firstOrCreate(['customer_code' => 'CUST-TEST-001'], [
            'customer_name' => 'Grand Hotel Muscat LLC',
            'customer_type' => 'WHOLESALE',
            'credit_limit' => 5000.000,
            'payment_terms_days' => 30,
            'credit_status' => 'ACTIVE',
            'is_active' => true,
        ]);

        $uom = Uom::firstOrCreate(['uom_code' => 'BTL'], ['uom_name' => 'Bottle', 'is_active' => true]);
        $tax = TaxVatRate::firstOrCreate(['tax_name' => 'Oman Standard VAT 5%'], [
            'tax_type' => 'VAT',
            'rate_percent' => 5.0,
            'effective_from' => '2021-04-16',
            'is_active' => true,
        ]);
        $cat = ProductCategory::firstOrCreate(['category_name' => 'Spirits']);

        $this->product = Product::firstOrCreate(['sku_code' => 'SKU-JW-BLK-1L'], [
            'product_name' => 'Johnnie Walker Black Label 1L',
            'category_id' => $cat->category_id,
            'base_uom_id' => $uom->uom_id,
            'tax_rate_id' => $tax->tax_rate_id,
            'is_active' => true,
            'is_permit_required' => true,
        ]);

        ProductPricingTier::firstOrCreate([
            'product_id' => $this->product->product_id,
            'price_tier' => 'WHOLESALE',
        ], [
            'min_quantity' => 1,
            'unit_price' => 20.000,
            'effective_from' => '2026-01-01',
        ]);

        $this->dutyPaidBatch = StockBatch::firstOrCreate(['batch_number' => 'BATCH-DP-TEST-01'], [
            'product_id' => $this->product->product_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'duty_status' => 'DUTY_PAID',
            'cost_per_unit' => 15.000,
            'expiry_date' => now()->addYear(),
        ]);

        WarehouseStock::firstOrCreate([
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'batch_id' => $this->dutyPaidBatch->batch_id,
        ], [
            'product_id' => $this->product->product_id,
            'quantity_on_hand' => 100.0,
            'quantity_reserved' => 0.0,
            'quantity_available' => 100.0,
        ]);
    }

    #[Test]
    public function branch_management_renders_and_auto_generates_code(): void
    {
        $comp = Livewire::actingAs($this->superAdmin)
            ->test(BranchManagement::class)
            ->assertStatus(200)
            ->assertSee('Branch & Counter Operations', escape: false)
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->assertSet('isEdit', false);

        $this->assertNotEmpty($comp->get('branch_code'));
    }

    #[Test]
    public function counter_management_renders_and_saves_modal_create(): void
    {
        $testCode = 'CNT-TEST-' . rand(100, 999);

        Livewire::actingAs($this->superAdmin)
            ->test(CounterManagement::class)
            ->assertStatus(200)
            ->assertSee('POS Counters')
            ->call('openCreateModal')
            ->set('counter_code', $testCode)
            ->set('counter_name', 'Express Checkout')
            ->set('branch_id', $this->branchMct->branch_id)
            ->call('saveCounter')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('counters', [
            'counter_code' => $testCode,
            'branch_id' => $this->branchMct->branch_id,
        ]);
    }

    #[Test]
    public function permit_management_renders_and_masks_civil_id_for_non_editors(): void
    {
        $civilId = '99887766';
        \App\Models\PermitVerification::create([
            'branch_id' => $this->branchMct->branch_id,
            'entered_by' => $this->cashierUser->user_id,
            'civil_id' => $civilId,
            'holder_name' => 'Tariq Al-Said',
            'permit_no' => 'PRM-2026-99',
            'annual_limit' => 600.000,
            'used_amount' => 50.000,
            'remaining_amount' => 550.000,
            'valid_from' => '2026-01-01',
            'valid_to' => '2026-12-31',
            'verified_at' => now(),
            'entry_method' => 'MANUAL',
            'controlled_total' => 25.000,
            'status' => 'USED',
            'attested_by' => $this->cashierUser->user_id,
            'attested_at' => now(),
        ]);

        // Editor sees full Civil ID
        Livewire::actingAs($this->superAdmin)
            ->test(PermitManagement::class)
            ->assertStatus(200)
            ->assertSee($civilId);

        // Cashier sees masked Civil ID and no Add buttons
        Livewire::actingAs($this->cashierUser)
            ->test(PermitManagement::class)
            ->assertStatus(200)
            ->assertSee('******7766')
            ->assertDontSee($civilId)
            ->assertDontSee('Add Permit');
    }

    #[Test]
    public function wholesale_quotations_modal_create_calculates_and_saves(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->assertStatus(200)
            ->assertSee('Wholesale Quotations')
            ->call('openCreateModal')
            ->assertSet('showModal', true)
            ->set('customer_id', $this->customer->customer_id)
            ->set('branch_id', $this->branchMct->branch_id)
            ->set('valid_until', now()->addDays(30)->toDateString())
            ->set('items.0.product_id', $this->product->product_id)
            ->set('items.0.quantity', 5)
            ->set('items.0.unit_price', 20.0)
            ->set('items.0.vat_rate', 5.0)
            ->call('saveQuotation')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('wholesale_quotations', [
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'status' => 'DRAFT',
        ]);
    }

    #[Test]
    public function wholesale_orders_renders_and_confirms_with_credit_check(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(SalesOrderManagement::class)
            ->assertStatus(200)
            ->assertSee('Sales Orders')
            ->call('openCreateModal')
            ->set('customer_id', $this->customer->customer_id)
            ->set('branch_id', $this->branchMct->branch_id)
            ->set('warehouse_id', $this->warehouseMct->warehouse_id)
            ->set('items.0.product_id', $this->product->product_id)
            ->set('items.0.quantity', 2)
            ->set('items.0.unit_price', 20.0)
            ->call('saveSalesOrder')
            ->assertHasNoErrors();

        $order = SalesOrder::where('customer_id', $this->customer->customer_id)
            ->where('status', 'DRAFT')
            ->latest('order_id')
            ->firstOrFail();

        // Confirm order via modal
        Livewire::actingAs($this->superAdmin)
            ->test(SalesOrderManagement::class)
            ->call('openConfirmModal', $order->order_id)
            ->assertSet('showConfirmModal', true)
            ->assertSet('requiresOverride', false)
            ->call('executeConfirmOrder')
            ->assertHasNoErrors();

        $order->refresh();
        $this->assertEquals('CONFIRMED', $order->status);
    }

    #[Test]
    public function delivery_notes_renders_and_dispatches_confirmed_order(): void
    {
        $soService = app(SalesOrderService::class);
        $order = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 2,
                'unit_price' => 20.0,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        $order = $soService->confirm($order, $this->superAdmin);

        // Livewire component modal dispatch
        Livewire::actingAs($this->superAdmin)
            ->test(DeliveryNoteManagement::class)
            ->assertStatus(200)
            ->assertSee('Delivery Notes')
            ->call('openCreateModal', $order->order_id)
            ->assertSet('selected_sales_order_id', $order->order_id)
            ->set('driver_name', 'Khamis Al-Harthy')
            ->set('vehicle_no', '7890-B')
            ->call('saveDeliveryNote')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('delivery_notes', [
            'sales_order_id' => $order->order_id,
            'status' => 'DISPATCHED',
            'driver_name' => 'Khamis Al-Harthy',
        ]);
    }

    #[Test]
    public function sales_invoices_renders_and_records_payment_modal(): void
    {
        $soService = app(SalesOrderService::class);
        $order = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 5,
                'unit_price' => 20.0,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        $invoice = SalesInvoice::create([
            'invoice_no' => 'INV-TEST-' . rand(100, 999),
            'sales_order_id' => $order->order_id,
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'invoice_date' => now(),
            'due_date' => now()->addDays(30),
            'status' => 'POSTED',
            'subtotal' => 100.0,
            'vat_total' => 5.0,
            'grand_total' => 105.0,
            'amount_paid' => 0.0,
            'created_by' => $this->superAdmin->user_id,
        ]);

        Livewire::actingAs($this->superAdmin)
            ->test(SalesInvoiceManagement::class)
            ->assertStatus(200)
            ->assertSee('Sales Invoices')
            ->call('openPaymentModal', $invoice->invoice_id)
            ->assertSet('showPaymentModal', true)
            ->set('paymentAmount', 50.0)
            ->set('paymentMethod', 'BANK_TRANSFER')
            ->set('paymentReference', 'BT-778899')
            ->call('recordPayment')
            ->assertHasNoErrors();

        $invoice->refresh();
        $this->assertEquals(50.0, (float) $invoice->amount_paid);
        $this->assertEquals(55.0, (float) $invoice->balance_due);
        $this->assertEquals('PARTIALLY_PAID', $invoice->status);
    }

    #[Test]
    public function customer_credit_renders_and_edits_terms_modal(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CustomerCreditManagement::class)
            ->assertStatus(200)
            ->assertSee('Customer Credit Limits')
            ->call('editCreditTerms', $this->customer->customer_id)
            ->assertSet('showModal', true)
            ->set('creditLimit', 8000.0)
            ->set('paymentTermsDays', 45)
            ->set('creditStatus', 'ACTIVE')
            ->call('saveCreditTerms')
            ->assertHasNoErrors();

        $this->customer->refresh();
        $this->assertEquals(8000.0, (float) $this->customer->credit_limit);
        $this->assertEquals(45, (int) $this->customer->payment_terms_days);
    }

    #[Test]
    public function cashier_shifts_renders_and_open_close_modals_work(): void
    {
        $counter = Counter::firstOrCreate(['counter_code' => 'CNT-MCT-S01'], [
            'counter_name' => 'Shift Test Counter',
            'branch_id' => $this->branchMct->branch_id,
            'is_active' => true,
        ]);

        // Open Shift - verify all active counters are dynamically available across branches
        Livewire::actingAs($this->superAdmin)
            ->test(ShiftManagement::class)
            ->assertStatus(200)
            ->assertSee('Cashier Shifts & Floats', escape: false)
            ->call('openShiftModal')
            ->assertSet('showOpenModal', true)
            ->assertSee('CNT-MCT-01')
            ->assertSee('CNT-SLL-01')
            ->set('openCounterId', $counter->counter_id)
            ->set('openFloat', 50.0)
            ->call('executeOpenShift')
            ->assertHasNoErrors();

        $shift = CashierShift::where('user_id', $this->superAdmin->user_id)
            ->where('status', 'OPEN')
            ->firstOrFail();

        // Close Shift via modal
        Livewire::actingAs($this->superAdmin)
            ->test(ShiftManagement::class)
            ->call('openCloseModal', $shift->shift_id)
            ->assertSet('showCloseModal', true)
            ->set('countedCash', 50.0)
            ->set('cardTotal', 0.0)
            ->call('executeCloseShift')
            ->assertHasNoErrors();

        $shift->refresh();
        $this->assertNotEquals('OPEN', $shift->status);
    }
}
