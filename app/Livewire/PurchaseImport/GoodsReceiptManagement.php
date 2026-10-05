<?php

namespace App\Livewire\PurchaseImport;

use App\Models\GoodsReceiptItem;
use App\Models\GoodsReceiptNote;
use App\Models\ImportShipment;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\StockBatch;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\StockLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class GoodsReceiptManagement extends Component
{
    use WithPagination;

    // Filter properties
    public string $search = '';
    public string $warehouse_filter = '';
    public string $discrepancy_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $grn_id = null;

    // Form fields
    public string $grn_number = '';
    public string $po_id = '';
    public string $shipment_id = '';
    public string $warehouse_id = '';
    public string $received_date = '';
    public string $received_by = '';
    public bool $has_discrepancy = false;
    public string $discrepancy_notes = '';

    // Item rows array
    public array $items = [];

    // Selected PO Info preview text
    public ?string $selected_po_info = null;

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_grn_id = null;

    // Delete Confirmation Modal State
    public bool $showItemDeleteModal = false;
    public int $itemToDeleteId = 0;
    public string $itemToDeleteNumber = '';

    protected function rules(): array
    {
        return [
            'grn_number' => 'required|string|max:40|unique:goods_receipt_notes,grn_number,' . ($this->grn_id ?? 'NULL') . ',grn_id',
            'po_id' => 'required|exists:purchase_orders,po_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'received_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.quantity_received' => 'required|numeric|min:0',
        ];
    }

    protected array $messages = [
        'grn_number.required' => 'GRN reference number is required.',
        'grn_number.unique' => 'This GRN number has already been generated.',
        'po_id.required' => 'Please select an associated Purchase Order.',
        'warehouse_id.required' => 'Please select the receiving warehouse.',
        'received_date.required' => 'Please enter the received date.',
        'items.required' => 'At least one item line must be received.',
        'items.*.quantity_received.required' => 'Please enter received quantity for all items.',
        'items.*.quantity_received.min' => 'Received quantity cannot be negative.',
    ];

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingWarehouseFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDiscrepancyFilter(): void
    {
        $this->resetPage();
    }

    public function mount(): void
    {
        $this->received_date = date('Y-m-d');
        $this->received_by = (string) (Auth::id() ?? User::first()?->user_id ?? 1);
    }

    public function generateGrnNumber(): string
    {
        $year = date('Y');
        $lastGrn = GoodsReceiptNote::latest('grn_id')->first();
        $nextNumber = $lastGrn ? ($lastGrn->grn_id + 1) : 1;
        return 'GRN-' . $year . '-' . str_pad((string) $nextNumber, 4, '0', STR_PAD_LEFT);
    }

    public function updatedPoId($value): void
    {
        if (!$value) {
            $this->selected_po_info = null;
            $this->items = [];
            return;
        }

        $po = PurchaseOrder::with(['supplier', 'warehouse', 'items.product', 'shipments'])->find($value);
        if (!$po) {
            $this->selected_po_info = null;
            $this->items = [];
            return;
        }

        $this->warehouse_id = (string) $po->warehouse_id;
        
        // Auto-select linked shipment if available
        $latestShipment = $po->shipments->last();
        if ($latestShipment) {
            $this->shipment_id = (string) $latestShipment->shipment_id;
        }

        $this->selected_po_info = "PO: {$po->po_number} • Supplier: {$po->supplier?->supplier_name} • Warehouse: {$po->warehouse?->warehouse_name}";

        // Populate items
        $this->items = [];
        foreach ($po->items as $poItem) {
            $prevReceived = (float) GoodsReceiptItem::where('po_item_id', $poItem->po_item_id)
                ->when($this->grn_id, function ($q) {
                    return $q->where('grn_id', '!=', $this->grn_id);
                })
                ->sum('quantity_received');

            $remainingOrdered = max(0, (float) $poItem->quantity_ordered - $prevReceived);

            $this->items[] = [
                'po_item_id' => $poItem->po_item_id,
                'product_id' => $poItem->product_id,
                'product_name' => $poItem->product?->product_name ?? 'Product #' . $poItem->product_id,
                'sku_code' => $poItem->product?->sku_code ?? '',
                'unit_cost' => (float) $poItem->unit_cost,
                'quantity_ordered' => (float) $poItem->quantity_ordered,
                'previously_received' => $prevReceived,
                'quantity_received' => $remainingOrdered,
                'batch_number' => 'BATCH-' . strtoupper(substr($poItem->product?->sku_code ?? 'ITEM', 0, 8)) . '-' . date('Ymd'),
                'manufacture_date' => date('Y-m-d', strtotime('-1 month')),
                'expiry_date' => date('Y-m-d', strtotime('+3 years')),
            ];
        }
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEdit = false;
        $this->grn_id = null;
        $this->grn_number = $this->generateGrnNumber();
        $this->po_id = '';
        $this->shipment_id = '';
        $this->warehouse_id = '';
        $this->received_date = date('Y-m-d');
        $this->received_by = (string) (Auth::id() ?? User::first()?->user_id ?? 1);
        $this->has_discrepancy = false;
        $this->discrepancy_notes = '';
        $this->selected_po_info = null;
        $this->items = [];
        $this->showModal = true;
    }

    public function openEditModal(int $grnId): void
    {
        $this->resetValidation();
        $grn = GoodsReceiptNote::with(['items.product', 'items.batch', 'purchaseOrder.supplier'])->find($grnId);
        if (!$grn) return;

        $this->grn_id = $grn->grn_id;
        $this->isEdit = true;
        $this->grn_number = $grn->grn_number;
        $this->po_id = (string) $grn->po_id;
        $this->shipment_id = (string) ($grn->shipment_id ?? '');
        $this->warehouse_id = (string) $grn->warehouse_id;
        $this->received_date = $grn->received_date ? $grn->received_date->format('Y-m-d') : date('Y-m-d');
        $this->received_by = (string) ($grn->received_by ?? Auth::id() ?? 1);
        $this->has_discrepancy = (bool) $grn->has_discrepancy;

        $po = $grn->purchaseOrder;
        if ($po) {
            $this->selected_po_info = "PO: {$po->po_number} • Supplier: {$po->supplier?->supplier_name}";
        }

        $this->items = [];
        foreach ($grn->items as $item) {
            $this->items[] = [
                'po_item_id' => $item->po_item_id,
                'product_id' => $item->product_id,
                'product_name' => $item->product?->product_name ?? 'Product #' . $item->product_id,
                'sku_code' => $item->product?->sku_code ?? '',
                'unit_cost' => (float) ($item->poItem?->unit_cost ?? 0),
                'quantity_ordered' => (float) $item->quantity_ordered,
                'previously_received' => 0,
                'quantity_received' => (float) $item->quantity_received,
                'batch_number' => $item->batch?->batch_number ?? ('BATCH-' . date('Ymd')),
                'manufacture_date' => $item->batch?->manufacture_date ? $item->batch->manufacture_date->format('Y-m-d') : date('Y-m-d', strtotime('-1 month')),
                'expiry_date' => $item->batch?->expiry_date ? $item->batch->expiry_date->format('Y-m-d') : date('Y-m-d', strtotime('+3 years')),
            ];
        }

        $this->showModal = true;
    }

    public function saveGrn(): void
    {
        $this->validate();

        DB::transaction(function () {
            // Check for discrepancies in quantity received vs ordered
            $detectedDiscrepancy = false;
            foreach ($this->items as $item) {
                if ((float) $item['quantity_received'] !== (float) $item['quantity_ordered']) {
                    $detectedDiscrepancy = true;
                    break;
                }
            }

            $grnData = [
                'grn_number' => $this->grn_number,
                'po_id' => $this->po_id,
                'shipment_id' => $this->shipment_id ?: null,
                'warehouse_id' => $this->warehouse_id,
                'received_date' => $this->received_date,
                'received_by' => $this->received_by ?: Auth::id(),
                'has_discrepancy' => $detectedDiscrepancy || $this->has_discrepancy,
            ];

            if ($this->isEdit && $this->grn_id) {
                $grn = GoodsReceiptNote::findOrFail($this->grn_id);
                $grn->update($grnData);
                // Delete existing items to recreate
                GoodsReceiptItem::where('grn_id', $grn->grn_id)->delete();
            } else {
                $grn = GoodsReceiptNote::create($grnData);
            }

            $stockService = app(StockLedgerService::class);

            foreach ($this->items as $itemData) {
                $qtyRec = (float) ($itemData['quantity_received'] ?? 0);

                // Find or Create StockBatch
                $batch = StockBatch::firstOrCreate(
                    [
                        'batch_number' => $itemData['batch_number'] ?? ('BATCH-' . $grn->grn_id . '-' . $itemData['product_id']),
                        'product_id' => $itemData['product_id'],
                    ],
                    [
                        'manufacture_date' => $itemData['manufacture_date'] ?? date('Y-m-d', strtotime('-1 month')),
                        'expiry_date' => $itemData['expiry_date'] ?? date('Y-m-d', strtotime('+3 years')),
                        'source_grn_id' => $grn->grn_id,
                        'duty_status' => 'DUTY_FREE_BONDED',
                    ]
                );

                // Save GoodsReceiptItem
                GoodsReceiptItem::create([
                    'grn_id' => $grn->grn_id,
                    'po_item_id' => $itemData['po_item_id'] ?? null,
                    'product_id' => $itemData['product_id'],
                    'batch_id' => $batch->batch_id,
                    'quantity_received' => $qtyRec,
                    'quantity_ordered' => (float) ($itemData['quantity_ordered'] ?? 0),
                ]);

                // Update PurchaseOrderItem total received quantity
                if (!empty($itemData['po_item_id'])) {
                    $poItem = PurchaseOrderItem::find($itemData['po_item_id']);
                    if ($poItem) {
                        $totalRec = GoodsReceiptItem::where('po_item_id', $poItem->po_item_id)->sum('quantity_received');
                        $poItem->update(['quantity_received' => $totalRec]);
                    }
                }

                // Record stock intake into warehouse
                if ($qtyRec > 0) {
                    $stockService->recordIntake(
                        warehouseId: (int) $this->warehouse_id,
                        productId: (int) $itemData['product_id'],
                        batchId: (int) $batch->batch_id,
                        quantity: $qtyRec
                    );
                }
            }

            // Auto-update PurchaseOrder Status based on receipt totals
            $po = PurchaseOrder::with('items')->find($this->po_id);
            if ($po) {
                $allCompleted = true;
                $anyReceived = false;

                foreach ($po->items as $pi) {
                    $rec = GoodsReceiptItem::where('po_item_id', $pi->po_item_id)->sum('quantity_received');
                    if ($rec > 0) $anyReceived = true;
                    if ($rec < (float) $pi->quantity_ordered) $allCompleted = false;
                }

                if ($allCompleted) {
                    $po->update(['status' => 'COMPLETED']);
                } elseif ($anyReceived) {
                    $po->update(['status' => 'PARTIALLY_RECEIVED']);
                }
            }

            // Also advance Shipment stage if linked
            if ($this->shipment_id) {
                $shipment = ImportShipment::find($this->shipment_id);
                if ($shipment && $shipment->stage !== 'RECEIVED') {
                    $shipment->update(['stage' => 'RECEIVED']);
                }
            }
        });

        $msg = $this->isEdit ? 'Goods Receipt Note updated successfully.' : 'Goods Receipt Note created & inventory updated successfully.';
        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: $this->isEdit ? 'GRN Updated' : 'GRN Created', message: "Goods Receipt Note '{$this->grn_number}' " . ($this->isEdit ? 'updated' : 'created') . ' successfully.');
        $this->showModal = false;
    }

    public function openViewModal(int $grnId): void
    {
        $this->view_grn_id = $grnId;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_grn_id = null;
    }

    public function confirmDelete(int $grnId): void
    {
        $grn = GoodsReceiptNote::find($grnId);
        if (!$grn) return;

        $this->itemToDeleteId = $grn->grn_id;
        $this->itemToDeleteNumber = $grn->grn_number;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        DB::transaction(function () {
            $grn = GoodsReceiptNote::find($this->itemToDeleteId);
            if ($grn) {
                GoodsReceiptItem::where('grn_id', $grn->grn_id)->delete();
                $grn->delete();
            }
        });

        $msg = "Goods Receipt Note {$this->itemToDeleteNumber} deleted successfully.";
        session()->flash('message', $msg);
        $this->dispatch('notify', type: 'success', title: 'GRN Deleted', message: $msg);
        $this->showItemDeleteModal = false;
    }

    public function render()
    {
        // Metric KPIs
        $totalGrnsCount = GoodsReceiptNote::count();
        $cleanReceiptsCount = GoodsReceiptNote::where('has_discrepancy', false)->count();
        $discrepancyReceiptsCount = GoodsReceiptNote::where('has_discrepancy', true)->count();
        $totalUnitsIntake = (float) GoodsReceiptItem::sum('quantity_received');

        // Query list
        $grns = GoodsReceiptNote::with(['purchaseOrder.supplier', 'shipment', 'warehouse', 'receiver', 'items.product', 'items.batch'])
            ->when($this->search, function ($q) {
                $s = '%' . $this->search . '%';
                return $q->where('grn_number', 'like', $s)
                    ->orWhereHas('purchaseOrder', function ($pq) use ($s) {
                        $pq->where('po_number', 'like', $s)
                            ->orWhereHas('supplier', function ($sq) use ($s) {
                                $sq->where('supplier_name', 'like', $s);
                            });
                    })
                    ->orWhereHas('warehouse', function ($wq) use ($s) {
                        $wq->where('warehouse_name', 'like', $s)->orWhere('warehouse_code', 'like', $s);
                    });
            })
            ->when($this->warehouse_filter, function ($q) {
                return $q->where('warehouse_id', $this->warehouse_filter);
            })
            ->when($this->discrepancy_filter !== '', function ($q) {
                if ($this->discrepancy_filter === 'clean') {
                    return $q->where('has_discrepancy', false);
                } elseif ($this->discrepancy_filter === 'discrepancy') {
                    return $q->where('has_discrepancy', true);
                }
            })
            ->when($this->date_from, function ($q) {
                return $q->whereDate('received_date', '>=', $this->date_from);
            })
            ->when($this->date_to, function ($q) {
                return $q->whereDate('received_date', '<=', $this->date_to);
            })
            ->orderBy('grn_id', 'desc')
            ->paginate(10);

        // Options for selects
        $purchaseOrders = PurchaseOrder::with(['supplier', 'warehouse', 'items.product'])
            ->whereIn('status', ['APPROVED', 'SHIPPED', 'PARTIALLY_RECEIVED', 'DRAFT'])
            ->orderBy('po_id', 'desc')
            ->get();

        $warehouses = Warehouse::orderBy('warehouse_name')->get();
        $shipments = ImportShipment::orderBy('shipment_id', 'desc')->get();
        $users = User::orderBy('full_name')->get();

        $selectedGrnDetails = $this->view_grn_id ? GoodsReceiptNote::with(['purchaseOrder.supplier', 'shipment', 'warehouse', 'receiver', 'items.product', 'items.batch'])->find($this->view_grn_id) : null;

        return view('livewire.purchase-import.goods-receipt-management', [
            'grns' => $grns,
            'metrics' => [
                'total_grns' => $totalGrnsCount,
                'clean_receipts' => $cleanReceiptsCount,
                'discrepancies' => $discrepancyReceiptsCount,
                'total_intake' => $totalUnitsIntake,
            ],
            'purchaseOrders' => $purchaseOrders,
            'warehouses' => $warehouses,
            'shipments' => $shipments,
            'users' => $users,
            'selectedGrnDetails' => $selectedGrnDetails,
        ])->layout('layouts.app');
    }
}
