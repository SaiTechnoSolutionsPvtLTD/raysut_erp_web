<?php

use App\Http\Controllers\BranchOperations\BranchController;
use App\Http\Controllers\BranchOperations\ShiftController;
use App\Http\Controllers\Sales\Retail\PermitVerificationController;
use App\Http\Controllers\Sales\Retail\PosController;
use App\Http\Controllers\Sales\Wholesale\CustomerCreditController;
use App\Http\Controllers\Sales\Wholesale\DeliveryNoteController;
use App\Http\Controllers\Sales\Wholesale\InvoiceController;
use App\Http\Controllers\Sales\Wholesale\QuotationController;
use App\Http\Controllers\Sales\Wholesale\SalesOrderController;
use App\Http\Controllers\System\Phase3DemoController;
use App\Http\Middleware\RequireOpenShift;
use App\Livewire\BranchOperations\BranchManagement;
use App\Livewire\BranchOperations\CounterManagement;
use App\Livewire\BranchOperations\ShiftManagement;
use App\Livewire\Dashboard;
use App\Livewire\MasterData\CustomerManagement;
use App\Livewire\MasterData\ProductManagement;
use App\Livewire\MasterData\SupplierManagement;
use App\Livewire\MasterData\TaxVatManagement;
use App\Livewire\MasterData\UomManagement;
use App\Livewire\PurchaseImport\DutyAssessmentManagement;
use App\Livewire\PurchaseImport\GoodsReceiptManagement;
use App\Livewire\PurchaseImport\ImportShipmentManagement;
use App\Livewire\PurchaseImport\PurchaseOrderManagement;
use App\Livewire\Sales\Retail\PermitManagement;
use App\Livewire\Sales\Wholesale\CustomerCreditManagement;
use App\Livewire\Sales\Wholesale\DeliveryNoteManagement;
use App\Livewire\Sales\Wholesale\QuotationManagement;
use App\Livewire\Sales\Wholesale\SalesInvoiceManagement;
use App\Livewire\Sales\Wholesale\SalesOrderManagement;
use App\Livewire\System\AuditLogViewer;
use App\Livewire\System\CompanyManagement;
use App\Livewire\System\RoleManagement;
use App\Livewire\System\UserManagement;
use App\Livewire\System\WarehouseManagement;
use App\Livewire\WarehouseInventory\DutyPaidConversionManagement;
use App\Livewire\WarehouseInventory\StockAdjustments;
use App\Livewire\WarehouseInventory\StockBatches;
use App\Livewire\WarehouseInventory\StockFlowDemo;
use App\Livewire\WarehouseInventory\StockLedger;
use App\Livewire\WarehouseInventory\StockOnHand;
use App\Livewire\WarehouseInventory\StockTransfers;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('dashboard', Dashboard::class)
    ->middleware(['auth'])
    ->name('dashboard');

// System & Facility Setup Routes
Route::middleware(['auth'])->prefix('system')->name('system.')->group(function () {
    Route::get('/company', CompanyManagement::class)->middleware('permission:System, Security & Setup,VIEW')->name('company');
    Route::get('/users', UserManagement::class)->middleware('permission:System, Security & Setup,VIEW')->name('users');
    Route::get('/roles', RoleManagement::class)->middleware('permission:System, Security & Setup,VIEW')->name('roles');
    Route::get('/audit-logs', AuditLogViewer::class)->middleware('permission:System, Security & Setup,VIEW')->name('audit-logs');

    // Branches Routes (BranchManagement Livewire)
    Route::get('/branches', BranchManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('branches');
    Route::get('/branches/list', BranchManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('branches.index');
    Route::post('/branches/switch', [BranchController::class, 'switchBranch'])->name('branches.switch');

    // Counters Routes (CounterManagement Livewire)
    Route::get('/counters', CounterManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('counters');
    Route::get('/counters/list', CounterManagement::class)->name('counters.index');

    Route::get('/warehouses', WarehouseManagement::class)->middleware('permission:Warehouse & Inventory,VIEW')->name('warehouses');
});

// Module 2: Master Data
Route::middleware(['auth', 'permission:Master Data,VIEW'])->prefix('master-data')->name('master-data.')->group(function () {
    Route::get('/products', ProductManagement::class)->name('products');
    Route::get('/suppliers', SupplierManagement::class)->name('suppliers');
    Route::get('/customers', CustomerManagement::class)->name('customers');
    Route::get('/uom', UomManagement::class)->name('uom');
    Route::get('/tax-rates', TaxVatManagement::class)->name('tax-rates');
});

// Module 3: Purchase & Import
Route::middleware(['auth', 'permission:Purchase & Import,VIEW'])->prefix('purchase-import')->name('purchase-import.')->group(function () {
    Route::get('/purchase-orders', PurchaseOrderManagement::class)->name('purchase-orders');
    Route::get('/shipments', ImportShipmentManagement::class)->name('shipments');
    Route::get('/grns', GoodsReceiptManagement::class)->name('grns');
    Route::get('/duty-assessments', DutyAssessmentManagement::class)->name('duty-assessments');
});

// Module 4: Warehouse & Inventory
Route::middleware(['auth', 'permission:Warehouse & Inventory,VIEW'])->prefix('warehouse-inventory')->name('warehouse-inventory.')->group(function () {
    Route::get('/stock', StockOnHand::class)->name('stock');
    Route::get('/ledger', StockLedger::class)->name('ledger');
    Route::get('/ledger/export', function (\Illuminate\Http\Request $request) {
        $component = new \App\Livewire\WarehouseInventory\StockLedger();
        $component->warehouse_filter = (string) $request->input('warehouse_filter', '');
        $component->branch_filter = (string) $request->input('branch_filter', '');
        $component->product_filter = (string) $request->input('product_filter', '');
        $component->movement_type_filter = (string) $request->input('movement_type_filter', '');
        $component->duty_status_filter = (string) $request->input('duty_status_filter', '');
        $component->date_from = (string) $request->input('date_from', '');
        $component->date_to = (string) $request->input('date_to', '');
        return $component->exportCsv();
    })->name('ledger.export');
    Route::get('/batches', StockBatches::class)->name('batches');
    Route::get('/duty-conversions', DutyPaidConversionManagement::class)->name('duty-conversions');
    Route::get('/transfers', StockTransfers::class)->name('transfers');
    Route::get('/adjustments', StockAdjustments::class)->name('adjustments');
    Route::get('/stock-flow-demo', StockFlowDemo::class)->name('stock-flow-demo');
});

// Shift Management Routes
Route::middleware(['auth'])->prefix('shifts')->name('shifts.')->group(function () {
    Route::get('/', ShiftManagement::class)->name('index');
    Route::get('/open', fn () => redirect()->route('shifts.index'))->name('open');
    Route::get('/{shift}/close', fn () => redirect()->route('shifts.index'))->name('show-close');
    Route::get('/{shift}/print', [ShiftController::class, 'printSummary'])->name('print');
});

// POS Checkout Routes (Protected by RequireOpenShift middleware)
Route::middleware(['auth'])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/receipt/{sale}', [PosController::class, 'receipt'])->name('receipt');
    Route::get('/sales', [PosController::class, 'sales'])->name('sales.index');
    Route::get('/sales/{sale}', [PosController::class, 'show'])->name('sales.show');
    Route::post('/sales/{sale}/void', [PosController::class, 'voidSale'])->name('sales.void');

    Route::middleware([RequireOpenShift::class])->group(function () {
        Route::get('/terminal', [PosController::class, 'terminal'])->name('terminal');
        Route::get('/permit-lookup', [PosController::class, 'lookupPermit'])->name('permit-lookup');
        Route::post('/permit/extract', [PosController::class, 'extractPermitScreenshot'])->middleware('throttle:10,1')->name('permit.extract');
        Route::post('/checkout', [PosController::class, 'checkout'])->name('checkout');
    });
});

Route::get('/retail/sales/{sale}', [PosController::class, 'show'])->middleware(['auth'])->name('retail.sales.show');

// Oman Permits Management Routes
Route::middleware(['auth'])->prefix('permits')->name('permits.')->group(function () {
    Route::get('/', PermitManagement::class)->name('index');
    Route::get('/export/csv', [PermitVerificationController::class, 'exportCsv'])->name('export');
    Route::get('/{verification}/screenshot', [PermitVerificationController::class, 'screenshot'])->name('screenshot');
});

// Module: Retail & Wholesale Sales - Wholesale Flow Routes
Route::middleware(['auth'])->prefix('wholesale')->name('wholesale.')->group(function () {
    // Wholesale Quotations
    Route::get('/quotations', QuotationManagement::class)->name('quotations.index');
    Route::get('/quotations/{quotation}', [QuotationController::class, 'show'])->name('quotations.show');
    Route::get('/quotations/{quotation}/print', [QuotationController::class, 'print'])->name('quotations.print');

    // Sales Orders
    Route::get('/orders', SalesOrderManagement::class)->name('orders.index');
    Route::get('/orders/{order}', [SalesOrderController::class, 'show'])->name('orders.show');
    Route::get('/orders/{order}/print', [SalesOrderController::class, 'print'])->name('orders.print');

    // Delivery Notes
    Route::get('/delivery-notes', DeliveryNoteManagement::class)->name('delivery-notes.index');
    Route::get('/delivery-notes/{deliveryNote}', [DeliveryNoteController::class, 'show'])->name('delivery-notes.show');
    Route::get('/delivery-notes/{deliveryNote}/print', [DeliveryNoteController::class, 'print'])->name('delivery-notes.print');

    // Sales Invoices
    Route::get('/invoices', SalesInvoiceManagement::class)->name('invoices.index');
    Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');
    Route::get('/invoices/{invoice}/print', [InvoiceController::class, 'print'])->name('invoices.print');

    // Customer Credit Management
    Route::get('/credit', CustomerCreditManagement::class)->name('credit.index');
    Route::get('/credit/{customer}', [CustomerCreditController::class, 'show'])->name('credit.show');
    Route::get('/credit/{customer}/print', [CustomerCreditController::class, 'print'])->name('credit.print');
});

// Phase 3 Exit Demo & Walkthrough Routes
Route::middleware(['auth'])->prefix('phase3')->name('phase3.')->group(function () {
    Route::get('/demo', [Phase3DemoController::class, 'index'])->name('demo');
    Route::post('/demo/run', [Phase3DemoController::class, 'run'])->name('demo.run');
    Route::post('/demo/reset', [Phase3DemoController::class, 'reset'])->name('demo.reset');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
