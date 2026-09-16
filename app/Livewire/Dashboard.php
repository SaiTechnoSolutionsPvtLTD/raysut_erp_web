<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\CustomerCreditTerm;
use App\Models\DutyAssessment;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\StockBatch;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Livewire\Component;

class Dashboard extends Component
{
    public function formatOMR($amount): string
    {
        return 'OMR ' . number_format((float) $amount, 3);
    }

    public function render()
    {
        // 1. Top KPI Row Data
        $stocks = WarehouseStock::with('product')->get();
        $totalStockOnHandValue = $stocks->sum(function ($s) {
            return (float) $s->quantity_on_hand * 25.000;
        });

        $openPurchaseOrdersCount = PurchaseOrder::whereIn('status', ['DRAFT', 'ISSUED', 'SHIPPED'])->count();
        $shipmentsInCustomsCount = ImportShipment::whereNotIn('stage', ['CLEARED', 'DELIVERED_WH'])->count();
        $discrepancyGrnsCount = GoodsReceiptNote::where('has_discrepancy', true)->count();
        $activeUsersCount = User::where('is_active', true)->count();

        // 2. Warehouse & Inventory Panel
        $warehouses = Warehouse::withSum('stocks', 'quantity_on_hand')->get();

        $expiringBatches = StockBatch::with('product')
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date', 'asc')
            ->take(5)
            ->get();

        $recentStockMovements = WarehouseStock::with(['product', 'warehouse', 'batch'])
            ->orderBy('last_updated_at', 'desc')
            ->take(10)
            ->get();

        // 3. Purchase & Import Panel
        $poStatuses = ['DRAFT', 'ISSUED', 'SHIPPED', 'RECEIVED', 'CLOSED'];
        $poStatusCounts = [];
        $totalPoCount = 0;
        foreach ($poStatuses as $st) {
            $count = PurchaseOrder::where('status', $st)->count();
            $poStatusCounts[$st] = $count;
            $totalPoCount += $count;
        }

        $shipmentsInTransit = ImportShipment::with('purchaseOrder')
            ->whereNotIn('stage', ['CLEARED', 'DELIVERED_WH'])
            ->latest('shipment_id')
            ->take(5)
            ->get();

        $latestDutyAssessments = DutyAssessment::with('shipment')
            ->latest('assessed_date')
            ->take(5)
            ->get();

        // 4. Master Data Panel
        $totalProducts = Product::where('is_active', true)->count();
        $totalSuppliers = Supplier::where('is_active', true)->count();
        $totalCustomers = Customer::where('is_active', true)->count();

        $customerCreditAlerts = CustomerCreditTerm::with('customer')
            ->orderBy('outstanding_balance', 'desc')
            ->take(5)
            ->get();

        $topSuppliers = Supplier::withCount('purchaseOrders')
            ->orderBy('purchase_orders_count', 'desc')
            ->take(5)
            ->get();

        // 5. System & Security Panel
        $latestAuditLogs = AuditLog::with('user')
            ->latest('audit_id')
            ->take(5)
            ->get();

        $totalWarehouseStockQty = $warehouses->sum('stocks_sum_quantity_on_hand');

        return view('livewire.dashboard', [
            'totalStockOnHandValue' => $totalStockOnHandValue,
            'openPurchaseOrdersCount' => $openPurchaseOrdersCount,
            'shipmentsInCustomsCount' => $shipmentsInCustomsCount,
            'discrepancyGrnsCount' => $discrepancyGrnsCount,
            'activeUsersCount' => $activeUsersCount,
            'warehouses' => $warehouses,
            'totalWarehouseStockQty' => $totalWarehouseStockQty,
            'expiringBatches' => $expiringBatches,
            'recentStockMovements' => $recentStockMovements,
            'poStatusCounts' => $poStatusCounts,
            'totalPoCount' => $totalPoCount,
            'shipmentsInTransit' => $shipmentsInTransit,
            'latestDutyAssessments' => $latestDutyAssessments,
            'totalProducts' => $totalProducts,
            'totalSuppliers' => $totalSuppliers,
            'totalCustomers' => $totalCustomers,
            'customerCreditAlerts' => $customerCreditAlerts,
            'topSuppliers' => $topSuppliers,
            'latestAuditLogs' => $latestAuditLogs,
        ])->layout('layouts.app');
    }
}
