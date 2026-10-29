<?php

namespace Tests\Feature;

use App\Livewire\Sales\Wholesale\QuotationManagement;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Permission;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WholesaleQuotation;
use App\Services\QuotationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class QuotationRowActionsTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchAdmin;
    protected Branch $branch;
    protected Warehouse $warehouse;
    protected Customer $customer;
    protected Product $product;

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

        $this->branch = Branch::where('branch_code', 'BR-MCT')->first();

        $this->warehouse = Warehouse::where('branch_id', $this->branch->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branch->company_id,
                'branch_id' => $this->branch->branch_id,
                'warehouse_code' => 'WH-MCT-TEST',
                'warehouse_name' => 'Muscat Test Warehouse',
                'warehouse_type' => 'DISTRIBUTION_CENTER',
                'is_active' => true,
            ]);

        $branchAdminRole = Role::firstOrCreate(['role_name' => 'Branch Admin']);
        $this->branchAdmin = User::create([
            'employee_id' => 'EMP-BA-MCT',
            'username' => 'branch_admin_mct',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Muscat Branch Admin',
            'email' => 'ba_mct@rmsc.om',
            'default_branch_id' => $this->branch->branch_id,
            'is_active' => true,
        ]);
        $this->branchAdmin->roles()->attach($branchAdminRole->role_id);
        $this->branchAdmin->branches()->attach($this->branch->branch_id);
        $this->branchAdmin->load('roles.permissions');

        $this->customer = Customer::create([
            'customer_code' => 'CUST-TEST-001',
            'customer_name' => 'Test Wholesale Customer',
            'customer_type' => 'WHOLESALE',
            'payment_terms_days' => 30,
            'credit_limit' => 5000,
            'is_active' => true,
        ]);

        $this->product = Product::first();
    }

    protected function createQuotation(string $status = 'DRAFT'): WholesaleQuotation
    {
        /** @var QuotationService $service */
        $service = app(QuotationService::class);
        $quotation = $service->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branch->branch_id,
            'valid_until' => now()->addDays(30)->toDateString(),
            'payment_terms_days' => 30,
            'notes' => 'Test quotation note',
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 5,
                'unit_price' => 10.000,
                'vat_rate' => 5.0,
            ],
        ], $this->superAdmin);

        if ($status === 'SENT') {
            $service->send($quotation, $this->superAdmin);
        } elseif ($status === 'ACCEPTED') {
            $service->send($quotation, $this->superAdmin);
            $service->accept($quotation, $this->superAdmin);
        }

        return $quotation->fresh();
    }

    // 1. Send DRAFT Tests
    #[Test]
    public function super_admin_can_send_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('sendQuotation', $q->quotation_id)
            ->assertSee("Quotation {$q->quote_no} marked as SENT.")
            ->assertDispatched('notify', type: 'info');

        $this->assertEquals('SENT', $q->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    #[Test]
    public function branch_admin_can_send_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('sendQuotation', $q->quotation_id)
            ->assertSee("Quotation {$q->quote_no} marked as SENT.")
            ->assertDispatched('notify', type: 'info');

        $this->assertEquals('SENT', $q->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    // 2. Accept SENT Tests
    #[Test]
    public function super_admin_can_accept_sent_quotation(): void
    {
        $q = $this->createQuotation('SENT');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('acceptQuotation', $q->quotation_id)
            ->assertSee("Quotation {$q->quote_no} marked as ACCEPTED.")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('ACCEPTED', $q->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    #[Test]
    public function branch_admin_can_accept_sent_quotation(): void
    {
        $q = $this->createQuotation('SENT');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('acceptQuotation', $q->quotation_id)
            ->assertSee("Quotation {$q->quote_no} marked as ACCEPTED.")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('ACCEPTED', $q->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    // 3. Reject SENT (with reason) Tests
    #[Test]
    public function super_admin_can_reject_sent_quotation(): void
    {
        $q = $this->createQuotation('SENT');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('openRejectModal', $q->quotation_id)
            ->assertSet('showRejectModal', true)
            ->assertSet('rejectQuotationId', $q->quotation_id)
            ->set('rejectReason', 'Customer found lower price')
            ->call('confirmReject')
            ->assertSee("Quotation {$q->quote_no} has been REJECTED.")
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('REJECTED', $q->fresh()->status);
        $this->assertStringContainsString('Customer found lower price', $q->fresh()->notes);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    #[Test]
    public function branch_admin_can_reject_sent_quotation(): void
    {
        $q = $this->createQuotation('SENT');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('openRejectModal', $q->quotation_id)
            ->assertSet('showRejectModal', true)
            ->assertSet('rejectQuotationId', $q->quotation_id)
            ->set('rejectReason', 'Customer canceled project')
            ->call('confirmReject')
            ->assertSee("Quotation {$q->quote_no} has been REJECTED.")
            ->assertDispatched('notify', type: 'warning');

        $this->assertEquals('REJECTED', $q->fresh()->status);
        $this->assertStringContainsString('Customer canceled project', $q->fresh()->notes);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    // 4. Convert ACCEPTED (pick a warehouse) Tests
    #[Test]
    public function super_admin_can_convert_accepted_quotation(): void
    {
        $q = $this->createQuotation('ACCEPTED');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('openConvertModal', $q->quotation_id)
            ->assertSet('showConvertModal', true)
            ->assertSet('convertQuotationId', $q->quotation_id)
            ->set('targetWarehouseId', $this->warehouse->warehouse_id)
            ->call('confirmConvert')
            ->assertSee("converted to Sales Order")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('CONVERTED', $q->fresh()->status);
        $this->assertNotNull($q->fresh()->salesOrder);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    #[Test]
    public function branch_admin_can_convert_accepted_quotation(): void
    {
        $q = $this->createQuotation('ACCEPTED');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('openConvertModal', $q->quotation_id)
            ->assertSet('showConvertModal', true)
            ->assertSet('convertQuotationId', $q->quotation_id)
            ->set('targetWarehouseId', $this->warehouse->warehouse_id)
            ->call('confirmConvert')
            ->assertSee("converted to Sales Order")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('CONVERTED', $q->fresh()->status);
        $this->assertNotNull($q->fresh()->salesOrder);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'STATUS_CHANGE',
        ]);
    }

    // 5. Edit DRAFT Tests
    #[Test]
    public function super_admin_can_edit_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('editQuotation', $q->quotation_id)
            ->assertSet('showModal', true)
            ->assertSet('isEdit', true)
            ->assertSet('quotation_id', $q->quotation_id)
            ->set('notes', 'Updated quotation notes')
            ->call('saveQuotation')
            ->assertSee("Wholesale Quotation {$q->quote_no} updated successfully.")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('Updated quotation notes', $q->fresh()->notes);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'UPDATE',
        ]);
    }

    #[Test]
    public function branch_admin_can_edit_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('editQuotation', $q->quotation_id)
            ->assertSet('showModal', true)
            ->assertSet('isEdit', true)
            ->assertSet('quotation_id', $q->quotation_id)
            ->set('notes', 'Branch admin notes')
            ->call('saveQuotation')
            ->assertSee("Wholesale Quotation {$q->quote_no} updated successfully.")
            ->assertDispatched('notify', type: 'success');

        $this->assertEquals('Branch admin notes', $q->fresh()->notes);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'UPDATE',
        ]);
    }

    // 6. Delete DRAFT Tests
    #[Test]
    public function super_admin_can_delete_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->superAdmin)
            ->test(QuotationManagement::class)
            ->call('confirmDeleteItem', $q->quotation_id, $q->quote_no)
            ->assertSet('showItemDeleteModal', true)
            ->assertSet('itemToDeleteId', $q->quotation_id)
            ->call('executeDeleteItem')
            ->assertSee("Quotation '{$q->quote_no}' deleted successfully.")
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('wholesale_quotations', ['quotation_id' => $q->quotation_id]);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'DELETE',
        ]);
    }

    #[Test]
    public function branch_admin_can_delete_draft_quotation(): void
    {
        $q = $this->createQuotation('DRAFT');

        Livewire::actingAs($this->branchAdmin)
            ->test(QuotationManagement::class)
            ->call('confirmDeleteItem', $q->quotation_id, $q->quote_no)
            ->assertSet('showItemDeleteModal', true)
            ->assertSet('itemToDeleteId', $q->quotation_id)
            ->call('executeDeleteItem')
            ->assertSee("Quotation '{$q->quote_no}' deleted successfully.")
            ->assertDispatched('notify', type: 'warning');

        $this->assertDatabaseMissing('wholesale_quotations', ['quotation_id' => $q->quotation_id]);
        $this->assertDatabaseHas('audit_logs', [
            'record_table' => 'wholesale_quotations',
            'record_id' => $q->quotation_id,
            'action' => 'DELETE',
        ]);
    }

    // 7. Authorization Hardening Test: EDIT but not APPROVE
    #[Test]
    public function user_with_edit_but_not_approve_cannot_see_accept_button_and_gets_error_on_direct_call(): void
    {
        $salesRole = Role::firstOrCreate(['role_name' => 'Wholesale Sales Clerk']);
        $viewPerm = Permission::where('module_name', 'Retail & Wholesale Sales')->where('action_code', 'VIEW')->first();
        $editPerm = Permission::where('module_name', 'Retail & Wholesale Sales')->where('action_code', 'EDIT')->first();
        $salesRole->permissions()->syncWithoutDetaching([$viewPerm->permission_id, $editPerm->permission_id]);

        $salesUser = User::create([
            'employee_id' => 'EMP-SALES-01',
            'username' => 'sales_clerk',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Wholesale Sales Clerk',
            'email' => 'sales_clerk@rmsc.om',
            'default_branch_id' => $this->branch->branch_id,
            'is_active' => true,
        ]);
        $salesUser->roles()->attach($salesRole->role_id);
        $salesUser->branches()->attach($this->branch->branch_id);
        $salesUser->load('roles.permissions');

        $q = $this->createQuotation('SENT');

        // Verify policy abilities directly
        $this->assertTrue($salesUser->can('update', $q));
        $this->assertFalse($salesUser->can('approve', $q));

        // 1. Assert user does NOT see the Accept button or action in the table
        Livewire::actingAs($salesUser)
            ->test(QuotationManagement::class)
            ->assertDontSeeHtml("acceptQuotation({$q->quotation_id})")
            ->assertDontSeeHtml('title="Customer Accepted"');

        // 2. Direct call to acceptQuotation is denied and caught gracefully
        Livewire::actingAs($salesUser)
            ->test(QuotationManagement::class)
            ->call('acceptQuotation', $q->quotation_id)
            ->assertDispatched('notify', type: 'error')
            ->assertSee('This action is unauthorized.');

        // Status remains unchanged
        $this->assertEquals('SENT', $q->fresh()->status);
    }
}
