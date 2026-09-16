<?php

use App\Livewire\MasterData\CustomerManagement;
use App\Livewire\MasterData\ProductManagement;
use App\Livewire\MasterData\SupplierManagement;
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
use App\Livewire\WarehouseInventory\StockAdjustments;
use App\Livewire\WarehouseInventory\StockBatches;
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
    Route::get('/counters', CounterManagement::class)->middleware('permission:Branch & Shift Operations,VIEW')->name('counters');
    Route::get('/warehouses', WarehouseManagement::class)->middleware('permission:Warehouse & Inventory,VIEW')->name('warehouses');
});

// Module 2: Master Data
Route::middleware(['auth', 'permission:Master Data,VIEW'])->prefix('master-data')->name('master-data.')->group(function () {
    Route::get('/products', ProductManagement::class)->name('products');
    Route::get('/suppliers', SupplierManagement::class)->name('suppliers');
    Route::get('/customers', CustomerManagement::class)->name('customers');
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
    Route::get('/transfers', StockTransfers::class)->name('transfers');
    Route::get('/adjustments', StockAdjustments::class)->name('adjustments');
});

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
