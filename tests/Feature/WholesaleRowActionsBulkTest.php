<?php

namespace Tests\Feature;

use App\Livewire\Sales\Wholesale\CustomerCreditManagement;
use App\Livewire\Sales\Wholesale\DeliveryNoteManagement;
use App\Livewire\Sales\Wholesale\SalesInvoiceManagement;
use App\Livewire\Sales\Wholesale\SalesOrderManagement;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use App\Services\DeliveryNoteService;
use App\Services\InvoiceService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WholesaleRowActionsBulkTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchAdmin;
    protected User $viewOnlyUser;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;
    protected StockBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Artisan::call('db:seed', ['--class' => 'Phase3DemoSeeder']);

        $this->superAdmin = User::where('username', 'admin')->first();
        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->attach($superAdminRole->role_id);
        }
        $this->superAdmin->load('roles.permissions');

        $this->branch = Branch::where('branch_code', 'BR-MCT')->firstOrFail();
        $this->warehouse = Warehouse::where('branch_id', $this->branch->branch_id)
            ->where('warehouse_type', 'MAIN')
            ->firstOrFail();

        $branchAdminRole = Role::firstOrCreate(['role_name' => 'Branch Admin']);
        $this->branchAdmin = User::create([
            'employee_id' => 'EMP-BA-BULK',
            'username' => 'branch_admin_bulk',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Bulk Branch Admin',
            'email' => 'ba_bulk@rmsc.om',
            'default_branch_id' => $this->branch->branch_id,
            'is_active' => true,
        ]);
        $this->branchAdmin->roles()->attach($branchAdminRole->role_id);
        $this->branchAdmin->branches()->attach($this->branch->branch_id);
        $this->branchAdmin->load('roles.permissions');

        $viewOnlyRole = Role::create(['role_name' => 'View Only Sales Bulk']);
        $viewPerm = Permission::where('module_name', 'Retail & Wholesale Sales')
            ->where('action_code', 'VIEW')
            ->firstOrFail();
        $viewOnlyRole->permissions()->attach($viewPerm->permission_id);

        $this->viewOnlyUser = User::create([
            'employee_id' => 'EMP-VIEW-BULK',
            'username' => 'view_only_bulk',
            'password_hash' => bcrypt('password'),
            'full_name' => 'View Only User',
            'email' => 'view_bulk@rmsc.om',
            'default_branch_id' => $this->branch->branch_id,
            'is_active' => true,
        ]);
        $this->viewOnlyUser->roles()->attach($viewOnlyRole->role_id);
        $this->viewOnlyUser->branches()->attach($this->branch->branch_id);
        $this->viewOnlyUser->load('roles.permissions');

        $this->customer = Customer::where('customer_code', 'CUST-HYATT-MCT')->firstOrFail();
        $this->product = Product::where('sku_code', 'SKU-JW-BLK-750')->firstOrFail();
        $this->batch = StockBatch::where('batch_number', 'BATCH-BR-MCT-JW-DP')->firstOrFail();
    }

    protected function createSalesOrder(string $status = 'DRAFT'): SalesOrder
    {
        /** @var SalesOrderService $soService */
        $soService = app(SalesOrderService::class);
        $order = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branch->branch_id,
            'warehouse_id' => $this->warehouse->warehouse_id,
            'payment_terms_days' => 30,
        ], [
            [
                'product_id' => $this->product->product_id,
                'batch_id' => $this->batch->batch_id,
                'quantity' => 2.0,
                'unit_price' => 15.000,
            ],
        ], $this->superAdmin);

        if ($status === 'CONFIRMED') {
            $soService->confirm($order, $this->superAdmin);
        } elseif ($status === 'CANCELLED') {
            $soService->cancel($order, $this->superAdmin, 'Test cancel');
        }

        return $order->fresh(['items', 'reservations']);
    }

    protected function createDeliveryNote(string $status = 'DRAFT'): DeliveryNote
    {
        $order = $this->createSalesOrder('CONFIRMED');
        /** @var DeliveryNoteService $dnService */
        $dnService = app(DeliveryNoteService::class);

        $dn = $dnService->createDraft($order, [
            [
                'order_item_id' => $order->items->first()->item_id,
                'quantity' => 2.0,
            ],
        ], [
            'driver_name' => 'Driver Ali',
            'vehicle_no' => '12345-AA',
        ], $this->superAdmin);

        if ($status === 'DISPATCHED') {
            $dnService->dispatch($dn, $this->superAdmin);
        } elseif ($status === 'CANCELLED') {
            $dnService->cancel($dn, $this->superAdmin, 'Test cancel DN');
        }

        return $dn->fresh(['items']);
    }

    protected function createSalesInvoice(string $status = 'POSTED'): SalesInvoice
    {
        $dn = $this->createDeliveryNote('DISPATCHED');
        /** @var InvoiceService $invService */
        $invService = app(InvoiceService::class);
        $inv = $invService->postFromDeliveryNote($dn, $this->superAdmin);

        if ($status === 'PAID') {
            $invService->recordPayment($inv, [
                'amount' => (float) $inv->grand_total,
                'method' => 'BANK_TRANSFER',
                'reference' => 'TXN-BULK-01',
            ], $this->superAdmin);
        }

        return $inv->fresh(['items', 'payments']);
    }

    // ==========================================
    // 1. SALES ORDER ROW ACTIONS TESTS
    // ==========================================

    #[Test]
    public function super_admin_can_confirm_sales_order(): void
    {
        $order = $this->createSalesOrder('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesOrderManagement::class)
            ->call('openConfirmModal', $order->order_id)
            ->call('executeConfirmOrder')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('CONFIRMED', $order->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'sales_orders',
            'record_id' => $order->order_id,
            'action' => 'CONFIRM',
        ]);
    }

    #[Test]
    public function branch_admin_can_confirm_sales_order(): void
    {
        $order = $this->createSalesOrder('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesOrderManagement::class)
            ->call('openConfirmModal', $order->order_id)
            ->call('executeConfirmOrder')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('CONFIRMED', $order->fresh()->status);
    }

    #[Test]
    public function unauthorized_user_cannot_confirm_sales_order(): void
    {
        $order = $this->createSalesOrder('DRAFT');

        Livewire::actingAs($this->viewOnlyUser)
            ->test(SalesOrderManagement::class)
            ->call('openConfirmModal', $order->order_id)
            ->assertDispatched('notify', type: 'error');

        $this->assertEquals('DRAFT', $order->fresh()->status);
    }

    #[Test]
    public function super_admin_can_cancel_sales_order(): void
    {
        $order = $this->createSalesOrder('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesOrderManagement::class)
            ->call('confirmCancelOrder', $order->order_id)
            ->call('executeCancelOrder')
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('CANCELLED', $order->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'sales_orders',
            'record_id' => $order->order_id,
            'action' => 'CANCEL',
        ]);
    }

    #[Test]
    public function branch_admin_can_cancel_sales_order(): void
    {
        $order = $this->createSalesOrder('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesOrderManagement::class)
            ->call('confirmCancelOrder', $order->order_id)
            ->call('executeCancelOrder')
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('CANCELLED', $order->fresh()->status);
    }

    #[Test]
    public function super_admin_can_delete_cancelled_sales_order(): void
    {
        $order = $this->createSalesOrder('CANCELLED');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesOrderManagement::class)
            ->call('confirmDeleteItem', $order->order_id, $order->so_no)
            ->call('executeDeleteItem')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('sales_orders', ['order_id' => $order->order_id]);
    }

    #[Test]
    public function branch_admin_can_delete_cancelled_sales_order(): void
    {
        $order = $this->createSalesOrder('CANCELLED');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesOrderManagement::class)
            ->call('confirmDeleteItem', $order->order_id, $order->so_no)
            ->call('executeDeleteItem')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('sales_orders', ['order_id' => $order->order_id]);
    }

    #[Test]
    public function unauthorized_user_cannot_delete_sales_order(): void
    {
        $order = $this->createSalesOrder('CANCELLED');

        Livewire::actingAs($this->viewOnlyUser)
            ->test(SalesOrderManagement::class)
            ->call('confirmDeleteItem', $order->order_id, $order->so_no)
            ->assertDispatched('notify', type: 'error');

        $this->assertDatabaseHas('sales_orders', ['order_id' => $order->order_id]);
    }

    // ==========================================
    // 2. DELIVERY NOTE ROW ACTIONS TESTS
    // ==========================================

    #[Test]
    public function super_admin_can_create_delivery_note(): void
    {
        $order = $this->createSalesOrder('CONFIRMED');

        Livewire::actingAs($this->superAdmin)
            ->test(DeliveryNoteManagement::class)
            ->call('openCreateModal', $order->order_id)
            ->call('saveDeliveryNote')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('delivery_notes', [
            'sales_order_id' => $order->order_id,
            'status' => 'DISPATCHED',
        ]);
    }

    #[Test]
    public function branch_admin_can_create_delivery_note(): void
    {
        $order = $this->createSalesOrder('CONFIRMED');

        Livewire::actingAs($this->branchAdmin)
            ->test(DeliveryNoteManagement::class)
            ->call('openCreateModal', $order->order_id)
            ->call('saveDeliveryNote')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('delivery_notes', [
            'sales_order_id' => $order->order_id,
            'status' => 'DISPATCHED',
        ]);
    }

    #[Test]
    public function unauthorized_user_cannot_create_delivery_note(): void
    {
        $order = $this->createSalesOrder('CONFIRMED');

        Livewire::actingAs($this->viewOnlyUser)
            ->test(DeliveryNoteManagement::class)
            ->call('openCreateModal', $order->order_id)
            ->assertDispatched('notify', type: 'error');
    }

    #[Test]
    public function super_admin_can_delete_delivery_note(): void
    {
        $dn = $this->createDeliveryNote('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(DeliveryNoteManagement::class)
            ->call('confirmDeleteItem', $dn->delivery_note_id, $dn->dn_no)
            ->call('executeDeleteItem')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('delivery_notes', ['delivery_note_id' => $dn->delivery_note_id]);
    }

    #[Test]
    public function branch_admin_can_delete_delivery_note(): void
    {
        $dn = $this->createDeliveryNote('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(DeliveryNoteManagement::class)
            ->call('confirmDeleteItem', $dn->delivery_note_id, $dn->dn_no)
            ->call('executeDeleteItem')
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('delivery_notes', ['delivery_note_id' => $dn->delivery_note_id]);
    }

    #[Test]
    public function unauthorized_user_cannot_delete_delivery_note(): void
    {
        $dn = $this->createDeliveryNote('DRAFT');

        Livewire::actingAs($this->viewOnlyUser)
            ->test(DeliveryNoteManagement::class)
            ->call('confirmDeleteItem', $dn->delivery_note_id, $dn->dn_no)
            ->assertDispatched('notify', type: 'error');

        $this->assertDatabaseHas('delivery_notes', ['delivery_note_id' => $dn->delivery_note_id]);
    }

    // ==========================================
    // 3. SALES INVOICE ROW ACTIONS TESTS
    // ==========================================

    #[Test]
    public function super_admin_can_post_invoice_from_delivery_note(): void
    {
        $dn = $this->createDeliveryNote('DISPATCHED');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesInvoiceManagement::class)
            ->set('sourceType', 'DELIVERY_NOTE')
            ->set('selectedSourceId', $dn->delivery_note_id)
            ->call('postInvoice')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('sales_invoices', [
            'delivery_note_id' => $dn->delivery_note_id,
            'status' => 'POSTED',
        ]);
    }

    #[Test]
    public function branch_admin_can_post_invoice_from_delivery_note(): void
    {
        $dn = $this->createDeliveryNote('DISPATCHED');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesInvoiceManagement::class)
            ->set('sourceType', 'DELIVERY_NOTE')
            ->set('selectedSourceId', $dn->delivery_note_id)
            ->call('postInvoice')
            ->assertDispatched('notify', type: 'success');

        $this->assertDatabaseHas('sales_invoices', [
            'delivery_note_id' => $dn->delivery_note_id,
            'status' => 'POSTED',
        ]);
    }

    #[Test]
    public function super_admin_can_record_payment_for_invoice(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesInvoiceManagement::class)
            ->call('openPaymentModal', $inv->invoice_id)
            ->set('paymentAmount', (float) $inv->balance_due)
            ->set('paymentMethod', 'BANK_TRANSFER')
            ->set('paymentReference', 'REF-TEST-001')
            ->call('recordPayment')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('PAID', $inv->fresh()->status);
        $this->assertEquals(0.0, (float) $inv->fresh()->balance_due);
    }

    #[Test]
    public function branch_admin_can_record_payment_for_invoice(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesInvoiceManagement::class)
            ->call('openPaymentModal', $inv->invoice_id)
            ->set('paymentAmount', (float) $inv->balance_due)
            ->set('paymentMethod', 'CASH')
            ->call('recordPayment')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('PAID', $inv->fresh()->status);
    }

    #[Test]
    public function super_admin_can_void_invoice_without_payments(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesInvoiceManagement::class)
            ->call('openVoidModal', $inv->invoice_id)
            ->set('voidReason', 'Customer requested order cancellation before delivery.')
            ->call('executeVoidInvoice')
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('VOIDED', $inv->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'sales_invoices',
            'record_id' => $inv->invoice_id,
            'action' => 'VOID_INVOICE',
        ]);
    }

    #[Test]
    public function branch_admin_can_void_invoice_without_payments(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->branchAdmin)
            ->test(SalesInvoiceManagement::class)
            ->call('openVoidModal', $inv->invoice_id)
            ->set('voidReason', 'Duplicate invoice generated in error.')
            ->call('executeVoidInvoice')
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('VOIDED', $inv->fresh()->status);
    }

    #[Test]
    public function void_invoice_is_blocked_when_payments_exist(): void
    {
        $inv = $this->createSalesInvoice('PAID');

        Livewire::actingAs($this->superAdmin)
            ->test(SalesInvoiceManagement::class)
            ->call('openVoidModal', $inv->invoice_id)
            ->set('voidReason', 'Test voiding paid invoice')
            ->call('executeVoidInvoice')
            ->assertDispatched('notify', type: 'error')
            ->assertSee('Cannot void Invoice');

        $this->assertEquals('PAID', $inv->fresh()->status);
    }

    // ==========================================
    // 4. CUSTOMER CREDIT MANAGEMENT TESTS
    // ==========================================

    #[Test]
    public function super_admin_can_edit_credit_terms(): void
    {
        Livewire::actingAs($this->superAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('editCreditTerms', $this->customer->customer_id)
            ->set('creditLimit', 8000.000)
            ->set('paymentTermsDays', 45)
            ->set('creditStatus', 'ACTIVE')
            ->call('saveCreditTerms')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals(8000.000, (float) $this->customer->fresh()->credit_limit);
        $this->assertEquals(45, (int) $this->customer->fresh()->payment_terms_days);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'customers',
            'record_id' => $this->customer->customer_id,
            'action' => 'UPDATE_CREDIT_TERMS',
        ]);
    }

    #[Test]
    public function branch_admin_can_edit_credit_terms(): void
    {
        Livewire::actingAs($this->branchAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('editCreditTerms', $this->customer->customer_id)
            ->set('creditLimit', 9500.000)
            ->set('paymentTermsDays', 60)
            ->set('creditStatus', 'ACTIVE')
            ->call('saveCreditTerms')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals(9500.000, (float) $this->customer->fresh()->credit_limit);
        $this->assertEquals(60, (int) $this->customer->fresh()->payment_terms_days);
    }

    #[Test]
    public function unauthorized_user_cannot_edit_credit_terms(): void
    {
        Livewire::actingAs($this->viewOnlyUser)
            ->test(CustomerCreditManagement::class)
            ->call('editCreditTerms', $this->customer->customer_id)
            ->assertDispatched('notify', type: 'error');

        $this->assertNotEquals(99999, (int) $this->customer->fresh()->credit_limit);
    }

    #[Test]
    public function super_admin_and_branch_admin_can_toggle_credit_hold(): void
    {
        $this->assertEquals('ACTIVE', $this->customer->credit_status);

        // Branch admin toggles to ON_HOLD
        Livewire::actingAs($this->branchAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('toggleCreditHold', $this->customer->customer_id)
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('ON_HOLD', $this->customer->fresh()->credit_status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'customers',
            'record_id' => $this->customer->customer_id,
            'action' => 'TOGGLE_CREDIT_STATUS',
        ]);

        // Super admin toggles back to ACTIVE
        Livewire::actingAs($this->superAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('toggleCreditHold', $this->customer->customer_id)
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('ACTIVE', $this->customer->fresh()->credit_status);
    }

    #[Test]
    public function unauthorized_user_cannot_toggle_credit_hold(): void
    {
        Livewire::actingAs($this->viewOnlyUser)
            ->test(CustomerCreditManagement::class)
            ->call('toggleCreditHold', $this->customer->customer_id)
            ->assertDispatched('notify', type: 'error');

        $this->assertEquals('ACTIVE', $this->customer->fresh()->credit_status);
    }

    #[Test]
    public function super_admin_can_record_payment_from_customer_credit_screen(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->superAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('openPaymentModal', $this->customer->customer_id)
            ->assertSet('showPaymentModal', true)
            ->assertSee($inv->invoice_no)
            ->assertSee('Balance Due:')
            ->set('paymentAmount', (float) $inv->balance_due)
            ->set('paymentMethod', 'BANK_TRANSFER')
            ->set('paymentReference', 'CR-PAY-001')
            ->call('saveCustomerPayment')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('PAID', $inv->fresh()->status);
        $this->assertEquals(0.0, (float) $inv->fresh()->balance_due);
        $this->assertDatabaseHas('customer_payments', [
            'invoice_id' => $inv->invoice_id,
            'customer_id' => $this->customer->customer_id,
            'amount' => $inv->grand_total,
            'method' => 'BANK_TRANSFER',
        ]);
    }

    #[Test]
    public function branch_admin_can_record_payment_from_customer_credit_screen(): void
    {
        $inv = $this->createSalesInvoice('POSTED');

        Livewire::actingAs($this->branchAdmin)
            ->test(CustomerCreditManagement::class)
            ->call('openPaymentModal', $this->customer->customer_id)
            ->assertSet('showPaymentModal', true)
            ->assertSee($inv->invoice_no)
            ->set('paymentAmount', (float) $inv->balance_due)
            ->set('paymentMethod', 'CASH')
            ->call('saveCustomerPayment')
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('PAID', $inv->fresh()->status);
    }
}
