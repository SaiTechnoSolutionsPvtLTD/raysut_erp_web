<?php

namespace App\Livewire\WarehouseInventory;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLedger as StockLedgerModel;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Response;
use Livewire\Component;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockLedger extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $search = '';
    public string $warehouse_filter = '';
    public string $branch_filter = '';
    public string $product_filter = '';
    public string $movement_type_filter = '';
    public string $duty_status_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Fixed page size (protected so it can't be tampered with from the browser)
    protected int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingBranchFilter(): void
    {
        $this->resetPage();
    }

    public function updatingProductFilter(): void
    {
        $this->resetPage();
    }

    public function updatingMovementTypeFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDutyStatusFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset([
            'search',
            'warehouse_filter',
            'branch_filter',
            'product_filter',
            'movement_type_filter',
            'duty_status_filter',
            'date_from',
            'date_to',
        ]);
        $this->resetPage();
    }

    protected function getFilteredQuery()
    {
        $user = auth()->user();
        $query = StockLedgerModel::with(['warehouse', 'branch', 'product', 'batch', 'user'])
            ->orderBy('ledger_id', 'desc');

        // Branch Isolation: Branch users see only their branch warehouses/movements
        $isHq = $user && ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'));
        if (! $isHq && $user) {
            $branchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter()->unique()->values();
            $query->whereIn('stock_ledger.branch_id', $branchIds);
        } elseif ($this->branch_filter) {
            $query->where('stock_ledger.branch_id', $this->branch_filter);
        }

        if ($this->warehouse_filter) {
            $query->where('stock_ledger.warehouse_id', $this->warehouse_filter);
        }

        if ($this->product_filter) {
            $query->where('stock_ledger.product_id', $this->product_filter);
        }

        if ($this->movement_type_filter) {
            $query->where('stock_ledger.movement_type', $this->movement_type_filter);
        }

        if ($this->duty_status_filter) {
            $query->whereHas('batch', function ($q) {
                $q->where('duty_status', $this->duty_status_filter);
            });
        }

        if ($this->date_from) {
            $query->whereDate('stock_ledger.created_at', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('stock_ledger.created_at', '<=', $this->date_to);
        }

        if ($this->search) {
            $search = '%' . trim($this->search) . '%';
            $query->where(function ($q) use ($search) {
                $q->where('reference_no', 'like', $search)
                  ->orWhere('notes', 'like', $search)
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('product_name', 'like', $search)
                         ->orWhere('product_code', 'like', $search)
                         ->orWhere('barcode', 'like', $search);
                  })
                  ->orWhereHas('batch', function ($bq) use ($search) {
                      $bq->where('batch_number', 'like', $search);
                  });
            });
        }

        return $query;
    }

    public function exportCsv(): StreamedResponse
    {
        $user = auth()->user();
        if (! $user || (! $user->hasPermission('Warehouse & Inventory', 'VIEW') && ! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin'))) {
            abort(403, 'Unauthorized to export stock ledger data.');
        }

        // Export stays unpaginated so the CSV contains every matching row
        $records = $this->getFilteredQuery()->get();
        $fileName = 'stock-ledger-' . now()->format('Y-m-d-His') . '.csv';

        return Response::stream(function () use ($records) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Date/Time',
                'Warehouse',
                'Branch',
                'Product Code',
                'Product Name',
                'Batch Number',
                'Duty Status',
                'Movement Type',
                'Quantity',
                'Balance After',
                'Reference Type',
                'Reference No',
                'User',
                'Notes',
            ]);

            foreach ($records as $row) {
                fputcsv($handle, [
                    $row->created_at?->format('Y-m-d H:i:s'),
                    $row->warehouse?->warehouse_code . ' (' . $row->warehouse?->warehouse_name . ')',
                    $row->branch?->branch_code,
                    $row->product?->product_code,
                    $row->product?->product_name,
                    $row->batch?->batch_number,
                    $row->batch?->duty_status,
                    $row->movement_type,
                    $row->quantity,
                    $row->balance_after,
                    $row->reference_type,
                    $row->reference_no,
                    $row->user?->name ?? 'System',
                    $row->notes,
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function render()
    {
        $user = auth()->user();
        if ($user && ! $user->hasPermission('Warehouse & Inventory', 'VIEW') && ! $user->hasRole('Super Admin') && ! $user->hasRole('HQ Admin')) {
            abort(403, 'Unauthorized access to Stock Ledger.');
        }

        $entries = $this->getFilteredQuery()->paginate($this->perPage);

        // Warehouses list scoped to user's branch
        $isHq = $user && ($user->hasRole('Super Admin') || $user->hasRole('HQ Admin'));
        $warehousesQuery = Warehouse::where('is_active', true)->orderBy('warehouse_name');
        if (! $isHq && $user) {
            $branchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter()->unique()->values();
            $warehousesQuery->whereIn('branch_id', $branchIds);
        }
        $warehouses = $warehousesQuery->get();

        $branches = $isHq ? Branch::where('is_active', true)->orderBy('branch_code')->get() : collect();
        $products = Product::where('is_active', true)->orderBy('product_name')->get(['product_id', 'product_code', 'product_name']);

        // Summary strip calculation (on-hand, reserved, available for current warehouse/product filter)
        $stockSummaryQuery = WarehouseStock::query();
        if (! $isHq && $user) {
            $branchIds = $user->branches->pluck('branch_id')->push($user->default_branch_id)->filter()->unique()->values();
            $stockSummaryQuery->whereHas('warehouse', fn($q) => $q->whereIn('branch_id', $branchIds));
        } elseif ($this->branch_filter) {
            $stockSummaryQuery->whereHas('warehouse', fn($q) => $q->where('branch_id', $this->branch_filter));
        }

        if ($this->warehouse_filter) {
            $stockSummaryQuery->where('warehouse_id', $this->warehouse_filter);
        }
        if ($this->product_filter) {
            $stockSummaryQuery->where('product_id', $this->product_filter);
        }
        if ($this->duty_status_filter) {
            $stockSummaryQuery->whereHas('batch', fn($q) => $q->where('duty_status', $this->duty_status_filter));
        }

        $summary = [
            'on_hand' => (float) $stockSummaryQuery->sum('quantity_on_hand'),
            'reserved' => (float) $stockSummaryQuery->sum('quantity_reserved'),
            'available' => (float) $stockSummaryQuery->sum('quantity_available'),
        ];

        return view('livewire.warehouse-inventory.stock-ledger', [
            'entries' => $entries,
            'warehouses' => $warehouses,
            'branches' => $branches,
            'products' => $products,
            'summary' => $summary,
            'isHq' => $isHq,
        ])->layout('layouts.app');
    }
}