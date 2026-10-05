<?php

use App\Livewire\MasterData\CustomerManagement;
use App\Livewire\MasterData\ProductManagement;
use App\Livewire\MasterData\SupplierManagement;
use App\Livewire\MasterData\TaxVatManagement;
use App\Livewire\MasterData\UomManagement;
use App\Livewire\PurchaseImport\DutyAssessmentManagement;
use App\Livewire\PurchaseImport\GoodsReceiptManagement;
use App\Livewire\PurchaseImport\ImportShipmentManagement;
use App\Livewire\PurchaseImport\PurchaseOrderManagement;
use App\Livewire\System\AuditLogViewer;
use App\Livewire\System\BranchManagement;
use App\Livewire\System\CompanyManagement;
use App\Livewire\System\CounterManagement;
use App\Livewire\System\RoleManagement;
use App\Livewire\System\UserManagement;
use App\Livewire\System\WarehouseManagement;
use App\Livewire\WarehouseInventory\DutyPaidConversionManagement;
use App\Livewire\WarehouseInventory\StockAdjustments;
use App\Livewire\WarehouseInventory\StockBatches;
use App\Livewire\WarehouseInventory\StockFlowDemo;
use App\Livewire\WarehouseInventory\StockOnHand;
use App\Livewire\Dashboard;
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

    Route::get('/branches', BranchManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('branches');
    Route::get('/branches/list', [\App\Http\Controllers\BranchController::class, 'index'])->name('branches.index');
    Route::get('/branches/create', [\App\Http\Controllers\BranchController::class, 'create'])->name('branches.create');
    Route::post('/branches', [\App\Http\Controllers\BranchController::class, 'store'])->name('branches.store');
    Route::get('/branches/{branch}/edit', [\App\Http\Controllers\BranchController::class, 'edit'])->name('branches.edit');
    Route::put('/branches/{branch}', [\App\Http\Controllers\BranchController::class, 'update'])->name('branches.update');
    Route::patch('/branches/{branch}/toggle-status', [\App\Http\Controllers\BranchController::class, 'toggleStatus'])->name('branches.toggle-status');

    Route::get('/counters', CounterManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('counters');
    Route::post('/counters', [\App\Http\Controllers\CounterController::class, 'store'])->name('counters.store');
    Route::patch('/counters/{counter}/toggle-status', [\App\Http\Controllers\CounterController::class, 'toggleStatus'])->name('counters.toggle-status');
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
    Route::get('/batches', StockBatches::class)->name('batches');
    Route::get('/duty-conversions', DutyPaidConversionManagement::class)->name('duty-conversions');
    Route::get('/transfers', StockTransfers::class)->name('transfers');
    Route::get('/adjustments', StockAdjustments::class)->name('adjustments');
    Route::get('/stock-flow-demo', StockFlowDemo::class)->name('stock-flow-demo');
});

// Shift Management Routes
Route::middleware(['auth'])->prefix('shifts')->name('shifts.')->group(function () {
    Route::get('/', [\App\Http\Controllers\ShiftController::class, 'index'])->name('index');
    Route::get('/open', [\App\Http\Controllers\ShiftController::class, 'showOpenForm'])->name('open');
    Route::post('/open', [\App\Http\Controllers\ShiftController::class, 'open'])->name('open.store');
    Route::get('/{shift}/close', [\App\Http\Controllers\ShiftController::class, 'showCloseForm'])->name('show-close');
    Route::post('/{shift}/close', [\App\Http\Controllers\ShiftController::class, 'close'])->name('close.store');
    Route::post('/{shift}/approve', [\App\Http\Controllers\ShiftController::class, 'approve'])->name('approve');
    Route::get('/{shift}/print', [\App\Http\Controllers\ShiftController::class, 'printSummary'])->name('print');
});

// POS Checkout Routes (Protected by RequireOpenShift middleware)
Route::middleware(['auth', \App\Http\Middleware\RequireOpenShift::class])->prefix('pos')->name('pos.')->group(function () {
    Route::get('/terminal', function () {
        return view('dashboard'); // POS Terminal placeholder screen
    })->name('terminal');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
