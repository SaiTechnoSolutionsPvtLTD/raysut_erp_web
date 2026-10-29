<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryNote;
use App\Models\Permission;
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
use App\Services\DeliveryNoteService;
use App\Services\InvoiceService;
use App\Services\QuotationService;
use App\Services\SalesOrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Tests\TestCase;

class DocumentViewsAndPrintAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $branchStaffMct;
    protected User $branchStaffSll;
    protected User $unauthorizedUser;
    protected Branch $branchMct;
    protected Branch $branchSll;
    protected Warehouse $warehouseMct;
    protected Warehouse $warehouseSll;
    protected Customer $customer;
    protected Product $product;
    protected StockBatch $batch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->superAdmin = User::where('username', 'admin')->first()
            ?: User::factory()->create([
                'username' => 'admin',
                'email' => 'admin@rmsc.om',
                'is_active' => true,
            ]);

        $superAdminRole = Role::firstOrCreate(['role_name' => 'Super Admin']);
        if (! $this->superAdmin->hasRole('Super Admin')) {
            $this->superAdmin->roles()->attach($superAdminRole->role_id);
            $this->superAdmin->load('roles.permissions');
        }

        $this->branchMct = Branch::where('branch_code', 'BR-MCT')->first()
            ?: Branch::create([
                'company_id' => 1,
                'branch_code' => 'BR-MCT',
                'branch_name' => 'Muscat Central Branch',
                'branch_type' => 'RETAIL',
                'city' => 'Muscat',
                'is_active' => true,
            ]);

        $this->branchSll = Branch::where('branch_code', 'BR-SLL')->first()
            ?: Branch::create([
                'company_id' => $this->branchMct->company_id ?? 1,
                'branch_code' => 'BR-SLL',
                'branch_name' => 'Salalah Freezone Branch',
                'branch_type' => 'WAREHOUSE_DEPOT',
                'city' => 'Salalah',
                'is_active' => true,
            ]);

        $this->warehouseMct = Warehouse::where('branch_id', $this->branchMct->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchMct->company_id,
                'branch_id' => $this->branchMct->branch_id,
                'warehouse_code' => 'WH-MCT-DOM-TEST',
                'warehouse_name' => 'Muscat Domestic Warehouse',
                'warehouse_type' => 'DOMESTIC_DUTY_PAID',
                'is_active' => true,
            ]);

        $this->warehouseSll = Warehouse::where('branch_id', $this->branchSll->branch_id)
            ->where('warehouse_type', '!=', 'DUTY_FREE_BONDED')
            ->first() ?? Warehouse::create([
                'company_id' => $this->branchSll->company_id,
                'branch_id' => $this->branchSll->branch_id,
                'warehouse_code' => 'WH-SLL-DOM-TEST',
                'warehouse_name' => 'Salalah Domestic Warehouse',
                'warehouse_type' => 'DOMESTIC_DUTY_PAID',
                'is_active' => true,
            ]);

        $branchAdminRole = Role::firstOrCreate(['role_name' => 'Branch Admin']);

        $this->branchStaffMct = User::create([
            'employee_id' => 'EMP-MCT-VIEW',
            'username' => 'staff_mct_view',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Muscat Staff User',
            'email' => 'staff_mct_view@rmsc.om',
            'default_branch_id' => $this->branchMct->branch_id,
            'is_active' => true,
        ]);
        $this->branchStaffMct->roles()->attach($branchAdminRole->role_id);
        $this->branchStaffMct->branches()->attach($this->branchMct->branch_id);
        $this->branchStaffMct->load('roles.permissions');

        $this->branchStaffSll = User::create([
            'employee_id' => 'EMP-SLL-VIEW',
            'username' => 'staff_sll_view',
            'password_hash' => bcrypt('password'),
            'full_name' => 'Salalah Staff User',
            'email' => 'staff_sll_view@rmsc.om',
            'default_branch_id' => $this->branchSll->branch_id,
            'is_active' => true,
        ]);
        $this->branchStaffSll->roles()->attach($branchAdminRole->role_id);
        $this->branchStaffSll->branches()->attach($this->branchSll->branch_id);
        $this->branchStaffSll->load('roles.permissions');

        $unauthorizedRole = Role::firstOrCreate(['role_name' => 'Unauthorized Sales Role']);
        $this->unauthorizedUser = User::create([
            'employee_id' => 'EMP-NO-ACCESS',
            'username' => 'no_access_user',
            'password_hash' => bcrypt('password'),
            'full_name' => 'No Access User',
            'email' => 'no_access@rmsc.om',
            'default_branch_id' => $this->branchSll->branch_id,
            'is_active' => true,
        ]);
        $this->unauthorizedUser->roles()->attach($unauthorizedRole->role_id);
        $this->unauthorizedUser->load('roles.permissions');

        $this->customer = Customer::firstOrCreate(['customer_code' => 'CUST-VIEW-TEST'], [
            'customer_name' => 'Al Bustan Hospitality LLC',
            'customer_type' => 'WHOLESALE',
            'credit_limit' => 10000.000,
            'payment_terms_days' => 30,
            'credit_status' => 'ACTIVE',
            'vat_number' => 'OM1100099887',
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

        $this->product = Product::firstOrCreate(['sku_code' => 'SKU-JW-RED-1L'], [
            'product_name' => 'Johnnie Walker Red Label 1L',
            'category_id' => $cat->category_id,
            'base_uom_id' => $uom->uom_id,
            'tax_rate_id' => $tax->tax_rate_id,
            'is_active' => true,
        ]);

        ProductPricingTier::firstOrCreate([
            'product_id' => $this->product->product_id,
            'price_tier' => 'WHOLESALE',
        ], [
            'min_quantity' => 1,
            'unit_price' => 12.000,
            'effective_from' => '2026-01-01',
        ]);

        $this->batch = StockBatch::firstOrCreate(['batch_number' => 'BATCH-VIEW-TEST-01'], [
            'product_id' => $this->product->product_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'duty_status' => 'DUTY_PAID',
            'cost_per_unit' => 8.000,
            'expiry_date' => now()->addYear(),
        ]);

        WarehouseStock::firstOrCreate([
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'batch_id' => $this->batch->batch_id,
        ], [
            'product_id' => $this->product->product_id,
            'quantity_on_hand' => 500.0,
            'quantity_reserved' => 0.0,
            'quantity_available' => 500.0,
        ]);
    }

    #[Test]
    public function quotation_show_and_print_pages_enforce_branch_isolation_and_authorization(): void
    {
        $quotationService = app(QuotationService::class);
        $quoteMct = $quotationService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'valid_until' => now()->addDays(20)->toDateString(),
            'payment_terms_days' => 30,
            'notes' => 'Test quotation for Muscat branch view check',
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 10,
                'unit_price' => 12.000,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        // 1. Super Admin: 200 on show and print
        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.quotations.show', $quoteMct))
            ->assertStatus(200)
            ->assertSee($quoteMct->quote_no)
            ->assertSee($this->customer->customer_name)
            ->assertSee('use the action icons on the list');

        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.quotations.print', $quoteMct))
            ->assertStatus(200)
            ->assertSee($quoteMct->quote_no)
            ->assertSee('WHOLESALE QUOTATION');

        // 2. Owning branch user (Muscat): 200 on show and print
        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.quotations.show', $quoteMct))
            ->assertStatus(200)
            ->assertSee($quoteMct->quote_no);

        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.quotations.print', $quoteMct))
            ->assertStatus(200)
            ->assertSee($quoteMct->quote_no);

        // 3. Other branch user (Salalah): 403/404 on show and print
        $resShow = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.quotations.show', $quoteMct));
        $this->assertForbiddenOrNotFound($resShow);

        $resPrint = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.quotations.print', $quoteMct));
        $this->assertForbiddenOrNotFound($resPrint);

        // 4. Non-existent record: 404
        $this->actingAs($this->superAdmin)
            ->get('/wholesale/quotations/999999')
            ->assertStatus(404);

        $this->actingAs($this->superAdmin)
            ->get('/wholesale/quotations/999999/print')
            ->assertStatus(404);
    }

    #[Test]
    public function sales_order_show_and_print_pages_enforce_branch_isolation_and_authorization(): void
    {
        $soService = app(SalesOrderService::class);
        $orderMct = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
            'payment_terms_days' => 30,
            'notes' => 'Test sales order for view check',
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 5,
                'unit_price' => 12.000,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        // 1. Super Admin: 200 on show and print
        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.orders.show', $orderMct))
            ->assertStatus(200)
            ->assertSee($orderMct->so_no)
            ->assertSee($this->customer->customer_name)
            ->assertSee('use the action icons on the list');

        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.orders.print', $orderMct))
            ->assertStatus(200)
            ->assertSee($orderMct->so_no)
            ->assertSee('SALES ORDER');

        // 2. Owning branch user (Muscat): 200 on show and print
        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.orders.show', $orderMct))
            ->assertStatus(200)
            ->assertSee($orderMct->so_no);

        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.orders.print', $orderMct))
            ->assertStatus(200)
            ->assertSee($orderMct->so_no);

        // 3. Other branch user (Salalah): 403/404 on show and print
        $resShow = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.orders.show', $orderMct));
        $this->assertForbiddenOrNotFound($resShow);

        $resPrint = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.orders.print', $orderMct));
        $this->assertForbiddenOrNotFound($resPrint);

        // 4. Non-existent record: 404
        $this->actingAs($this->superAdmin)
            ->get('/wholesale/orders/999999')
            ->assertStatus(404);

        $this->actingAs($this->superAdmin)
            ->get('/wholesale/orders/999999/print')
            ->assertStatus(404);
    }

    #[Test]
    public function delivery_note_show_and_print_pages_enforce_branch_isolation_and_authorization(): void
    {
        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);

        $orderMct = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 4,
                'unit_price' => 12.000,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        $orderMct = $soService->confirm($orderMct, $this->superAdmin);

        $dnMct = $dnService->createAndDispatch($orderMct, [
            [
                'order_item_id' => $orderMct->items->first()->item_id,
                'quantity' => 4,
                'batch_id' => $this->batch->batch_id,
            ]
        ], [
            'vehicle_no' => 'MCT-4433-A',
            'driver_name' => 'Ahmed Al-Balushi',
            'notes' => 'Test Delivery Note Dispatch',
        ], $this->superAdmin);

        // 1. Super Admin: 200 on show and print
        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.delivery-notes.show', $dnMct))
            ->assertStatus(200)
            ->assertSee($dnMct->dn_no)
            ->assertSee('use the action icons on the list');

        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.delivery-notes.print', $dnMct))
            ->assertStatus(200)
            ->assertSee($dnMct->dn_no)
            ->assertSee('DELIVERY NOTE');

        // 2. Owning branch user (Muscat): 200 on show and print
        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.delivery-notes.show', $dnMct))
            ->assertStatus(200)
            ->assertSee($dnMct->dn_no);

        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.delivery-notes.print', $dnMct))
            ->assertStatus(200)
            ->assertSee($dnMct->dn_no);

        // 3. Other branch user (Salalah): 403/404 on show and print
        $resShow = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.delivery-notes.show', $dnMct));
        $this->assertForbiddenOrNotFound($resShow);

        $resPrint = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.delivery-notes.print', $dnMct));
        $this->assertForbiddenOrNotFound($resPrint);

        // 4. Non-existent record: 404
        $this->actingAs($this->superAdmin)
            ->get('/wholesale/delivery-notes/999999')
            ->assertStatus(404);

        $this->actingAs($this->superAdmin)
            ->get('/wholesale/delivery-notes/999999/print')
            ->assertStatus(404);
    }

    #[Test]
    public function sales_invoice_show_and_print_pages_enforce_branch_isolation_and_authorization(): void
    {
        $soService = app(SalesOrderService::class);
        $dnService = app(DeliveryNoteService::class);
        $invService = app(InvoiceService::class);

        $orderMct = $soService->create([
            'customer_id' => $this->customer->customer_id,
            'branch_id' => $this->branchMct->branch_id,
            'warehouse_id' => $this->warehouseMct->warehouse_id,
        ], [
            [
                'product_id' => $this->product->product_id,
                'quantity' => 2,
                'unit_price' => 12.000,
                'vat_rate' => 5.0,
            ]
        ], $this->superAdmin);

        $orderMct = $soService->confirm($orderMct, $this->superAdmin);

        $dnMct = $dnService->createAndDispatch($orderMct, [
            [
                'order_item_id' => $orderMct->items->first()->item_id,
                'quantity' => 2,
                'batch_id' => $this->batch->batch_id,
            ]
        ], [], $this->superAdmin);

        $invoiceMct = $invService->postFromDeliveryNote($dnMct, $this->superAdmin);

        // 1. Super Admin: 200 on show and print
        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.invoices.show', $invoiceMct))
            ->assertStatus(200)
            ->assertSee($invoiceMct->invoice_no)
            ->assertSee('use the action icons on the list');

        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.invoices.print', $invoiceMct))
            ->assertStatus(200)
            ->assertSee($invoiceMct->invoice_no)
            ->assertSee('TAX INVOICE');

        // 2. Owning branch user (Muscat): 200 on show and print
        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.invoices.show', $invoiceMct))
            ->assertStatus(200)
            ->assertSee($invoiceMct->invoice_no);

        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.invoices.print', $invoiceMct))
            ->assertStatus(200)
            ->assertSee($invoiceMct->invoice_no);

        // 3. Other branch user (Salalah): 403/404 on show and print
        $resShow = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.invoices.show', $invoiceMct));
        $this->assertForbiddenOrNotFound($resShow);

        $resPrint = $this->actingAs($this->branchStaffSll)
            ->get(route('wholesale.invoices.print', $invoiceMct));
        $this->assertForbiddenOrNotFound($resPrint);

        // 4. Non-existent record: 404
        $this->actingAs($this->superAdmin)
            ->get('/wholesale/invoices/999999')
            ->assertStatus(404);

        $this->actingAs($this->superAdmin)
            ->get('/wholesale/invoices/999999/print')
            ->assertStatus(404);
    }

    #[Test]
    public function customer_credit_show_and_print_pages_authorization(): void
    {
        // 1. Super Admin: 200 on show and print
        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.credit.show', $this->customer))
            ->assertStatus(200)
            ->assertSee($this->customer->customer_name)
            ->assertSee('use the action icons on the list');

        $this->actingAs($this->superAdmin)
            ->get(route('wholesale.credit.print', $this->customer))
            ->assertStatus(200)
            ->assertSee($this->customer->customer_name)
            ->assertSee('CREDIT STATEMENT');

        // 2. Branch user with permission: 200 on show and print
        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.credit.show', $this->customer))
            ->assertStatus(200)
            ->assertSee($this->customer->customer_name);

        $this->actingAs($this->branchStaffMct)
            ->get(route('wholesale.credit.print', $this->customer))
            ->assertStatus(200)
            ->assertSee($this->customer->customer_name);

        // 3. Unauthorized user without view permission: 403
        $this->actingAs($this->unauthorizedUser)
            ->get(route('wholesale.credit.show', $this->customer))
            ->assertStatus(403);

        $this->actingAs($this->unauthorizedUser)
            ->get(route('wholesale.credit.print', $this->customer))
            ->assertStatus(403);

        // 4. Non-existent record: 404
        $this->actingAs($this->superAdmin)
            ->get('/wholesale/credit/999999')
            ->assertStatus(404);

        $this->actingAs($this->superAdmin)
            ->get('/wholesale/credit/999999/print')
            ->assertStatus(404);
    }

    #[Test]
    public function sales_views_contain_no_unregistered_routes(): void
    {
        $salesViewsPath = resource_path('views/sales');
        $this->assertDirectoryExists($salesViewsPath, 'Directory resources/views/sales must exist.');

        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($salesViewsPath));
        $missingRoutes = [];
        $scannedFilesCount = 0;
        $allScannedRouteCalls = [];

        foreach ($iterator as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.blade.php')) {
                $scannedFilesCount++;
                $content = file_get_contents($file->getPathname());
                $relativePath = str_replace(resource_path(), 'resources', $file->getPathname());

                // Match route('name') or route("name")
                preg_match_all('/route\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches);

                if (! empty($matches[1])) {
                    foreach ($matches[1] as $routeName) {
                        $allScannedRouteCalls[] = [
                            'file' => $relativePath,
                            'route' => $routeName,
                        ];

                        if (! Route::has($routeName)) {
                            $missingRoutes[] = [
                                'file' => $relativePath,
                                'route' => $routeName,
                            ];
                        }
                    }
                }
            }
        }

        $this->assertGreaterThan(0, $scannedFilesCount, 'Expected at least one Blade file under resources/views/sales.');
        $this->assertNotEmpty($allScannedRouteCalls, 'Expected route(...) calls to be found in resources/views/sales.');

        $errorMessage = "Found unregistered routes in resources/views/sales Blade templates:\n" .
            json_encode($missingRoutes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $this->assertEmpty($missingRoutes, $errorMessage);
    }

    protected function assertForbiddenOrNotFound($response): void
    {
        $this->assertTrue(
            in_array($response->status(), [403, 404], true),
            "Expected status code 403 or 404, but received {$response->status()}."
        );
    }
}
