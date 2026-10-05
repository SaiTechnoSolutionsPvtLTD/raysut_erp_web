<?php

namespace App\Livewire\PurchaseImport;

use App\Models\ImportShipment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class PurchaseOrderManagement extends Component
{
    use WithPagination;

    // Filters
    public string $search = '';
    public string $supplier_filter = '';
    public string $warehouse_filter = '';
    public string $status_filter = '';
    public string $date_from = '';
    public string $date_to = '';

    // Create / Edit Modal State
    public bool $showModal = false;
    public bool $isEdit = false;
    public ?int $po_id = null;

    // Form fields
    public string $po_number = '';
    public string $supplier_id = '';
    public string $warehouse_id = '';
    public string $order_date = '';
    public string $expected_delivery_date = '';
    public string $currency_code = 'OMR';
    public string $status = 'DRAFT';

    // Line items: array of ['po_item_id' => null, 'product_id' => '', 'quantity_ordered' => 1, 'quantity_received' => 0, 'unit_cost' => 0.000]
    public array $items = [];

    // View Details Modal State
    public bool $showViewModal = false;
    public ?int $view_po_id = null;

    // Delete Confirmation Modal State
    public bool $showItemDeleteModal = false;
    public int $itemToDeleteId = 0;
    public string $itemToDeleteNumber = '';

    protected function rules(): array
    {
        return [
            'po_number' => 'required|string|max:30|unique:purchase_orders,po_number,' . ($this->po_id ?? 'NULL') . ',po_id',
            'supplier_id' => 'required|exists:suppliers,supplier_id',
            'warehouse_id' => 'required|exists:warehouses,warehouse_id',
            'order_date' => 'required|date',
            'expected_delivery_date' => 'nullable|date|after_or_equal:order_date',
            'currency_code' => 'required|string|size:3',
            'status' => 'required|in:DRAFT,APPROVED,SHIPPED,PARTIALLY_RECEIVED,COMPLETED,CANCELLED',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,product_id',
            'items.*.quantity_ordered' => 'required|numeric|min:0.01',
            'items.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    protected $messages = [
        'po_number.required' => 'PO number is required.',
        'po_number.unique' => 'This PO number has already been taken.',
        'supplier_id.required' => 'Please select a supplier.',
        'warehouse_id.required' => 'Please select a destination warehouse.',
        'order_date.required' => 'Order date is required.',
        'order_date.date' => 'Order date must be a valid date.',
        'expected_delivery_date.after_or_equal' => 'Expected delivery date must be on or after the order date.',
        'currency_code.required' => 'Please select a supplier to determine the currency.',
        'currency_code.size' => 'Currency code must be 3 characters.',
        'status.required' => 'Please select an order status.',
        'items.required' => 'At least one product line item is required.',
        'items.min' => 'At least one product line item is required.',
        'items.*.product_id.required' => 'Please select a product for each row.',
        'items.*.quantity_ordered.required' => 'Quantity is required.',
        'items.*.quantity_ordered.min' => 'Quantity must be at least 0.01.',
        'items.*.unit_cost.required' => 'Unit cost is required.',
        'items.*.unit_cost.min' => 'Unit cost cannot be negative.',
    ];

    public function mount(): void
    {
        $this->order_date = date('Y-m-d');
    }

    public function updatedSupplierId($value): void
    {
        if (!empty($value)) {
            $supplier = Supplier::find($value);
            if ($supplier) {
                $this->currency_code = $supplier->currency_code ?? 'OMR';

                // Update unit costs for already selected products if known
                foreach ($this->items as $idx => $item) {
                    if (!empty($item['product_id'])) {
                        $suppProd = SupplierProduct::where('supplier_id', $value)
                            ->where('product_id', $item['product_id'])
                            ->first();
                        if ($suppProd && $suppProd->last_purchase_cost > 0) {
                            $this->items[$idx]['unit_cost'] = (float)$suppProd->last_purchase_cost;
                        }
                    }
                }
            } else {
                $this->currency_code = '';
            }
        } else {
            $this->currency_code = '';
        }

        $this->validateOnly('supplier_id');
        if (!empty($this->currency_code)) {
            $this->validateOnly('currency_code');
        }
    }

    public function updatedOrderDate(): void
    {
        $this->validateOnly('order_date');
        if (!empty($this->expected_delivery_date)) {
            $this->validateOnly('expected_delivery_date');
        }
    }

    public function updatedExpectedDeliveryDate(): void
    {
        if (!empty($this->expected_delivery_date)) {
            $this->validateOnly('expected_delivery_date');
        }
    }

    public function updatedItems($value, $key): void
    {
        if (str_ends_with($key, '.product_id') && !empty($value)) {
            $parts = explode('.', $key);
            $index = (int)$parts[0];

            if (!empty($this->supplier_id)) {
                $suppProd = SupplierProduct::where('supplier_id', $this->supplier_id)
                    ->where('product_id', $value)
                    ->first();
                if ($suppProd && $suppProd->last_purchase_cost > 0) {
                    $this->items[$index]['unit_cost'] = (float)$suppProd->last_purchase_cost;
                }
            }
        }
    }

    protected function generatePoNumber(): string
    {
        $year = date('Y');
        $prefix = "PO-{$year}-";

        $latest = PurchaseOrder::where('po_number', 'like', $prefix . '%')
            ->orderBy('po_id', 'desc')
            ->first();

        if ($latest) {
            $num = (int) substr($latest->po_number, strlen($prefix));
            $nextNum = $num + 1;
        } else {
            $count = PurchaseOrder::count();
            $nextNum = $count + 1;
        }

        do {
            $code = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
            $exists = PurchaseOrder::where('po_number', $code)->exists();
            if ($exists) {
                $nextNum++;
            }
        } while ($exists);

        return $code;
    }

    public function openCreateModal(): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'CREATE')) {
            abort(403, 'Unauthorized action: You do not have permission to CREATE purchase orders.');
        }

        $this->reset([
            'po_id', 'supplier_id', 'warehouse_id', 'expected_delivery_date'
        ]);

        $defaultWarehouse = Warehouse::where('is_active', true)->first();
        $this->warehouse_id = $defaultWarehouse ? (string)$defaultWarehouse->warehouse_id : '';
        $this->po_number = $this->generatePoNumber();
        $this->order_date = date('Y-m-d');
        $this->currency_code = '';
        $this->status = 'DRAFT';
        $this->items = [
            ['po_item_id' => null, 'product_id' => '', 'quantity_ordered' => 100, 'quantity_received' => 0, 'unit_cost' => 0.000]
        ];
        $this->isEdit = false;
        $this->showModal = true;
    }

    public function addItem(): void
    {
        $this->items[] = [
            'po_item_id' => null,
            'product_id' => '',
            'quantity_ordered' => 100,
            'quantity_received' => 0,
            'unit_cost' => 0.000,
        ];
    }

    public function removeItem(int $index): void
    {
        if (count($this->items) > 1) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
        } else {
            $this->items = [
                ['po_item_id' => null, 'product_id' => '', 'quantity_ordered' => 1, 'quantity_received' => 0, 'unit_cost' => 0.000]
            ];
        }
    }

    public function editPurchaseOrder(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to EDIT purchase orders.');
        }

        $po = PurchaseOrder::with(['items'])->findOrFail($id);

        $this->po_id = $po->po_id;
        $this->po_number = $po->po_number;
        $this->supplier_id = (string)$po->supplier_id;
        $this->warehouse_id = (string)$po->warehouse_id;
        $this->order_date = $po->order_date ? $po->order_date->format('Y-m-d') : date('Y-m-d');
        $this->expected_delivery_date = $po->expected_delivery_date ? $po->expected_delivery_date->format('Y-m-d') : '';
        $this->currency_code = $po->currency_code ?? 'OMR';
        $this->status = $po->status ?? 'DRAFT';

        $this->items = [];
        foreach ($po->items as $item) {
            $this->items[] = [
                'po_item_id' => $item->po_item_id,
                'product_id' => (string)$item->product_id,
                'quantity_ordered' => (float)$item->quantity_ordered,
                'quantity_received' => (float)$item->quantity_received,
                'unit_cost' => (float)$item->unit_cost,
            ];
        }

        if (empty($this->items)) {
            $this->items = [
                ['po_item_id' => null, 'product_id' => '', 'quantity_ordered' => 1, 'quantity_received' => 0, 'unit_cost' => 0.000]
            ];
        }

        $this->isEdit = true;
        $this->showModal = true;
    }

    public function viewPurchaseOrder(int $id): void
    {
        $this->view_po_id = $id;
        $this->showViewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->showViewModal = false;
        $this->view_po_id = null;
    }

    public function savePurchaseOrder(): void
    {
        $requiredAction = $this->po_id ? 'EDIT' : 'CREATE';
        if (! auth()->user()?->hasPermission('Purchase & Import', $requiredAction)) {
            abort(403, "Unauthorized action: You do not have permission to {$requiredAction} purchase orders.");
        }

        if (!$this->po_id && empty($this->po_number)) {
            $this->po_number = $this->generatePoNumber();
        }

        $this->validate();

        DB::transaction(function () {
            $isNew = !$this->po_id;
            $userId = auth()->id() ?? 1;

            $approvedBy = null;
            if ($this->status === 'APPROVED') {
                $approvedBy = $userId;
            }

            $po = PurchaseOrder::updateOrCreate(
                ['po_id' => $this->po_id],
                [
                    'po_number' => trim($this->po_number),
                    'supplier_id' => (int)$this->supplier_id,
                    'warehouse_id' => (int)$this->warehouse_id,
                    'order_date' => $this->order_date,
                    'expected_delivery_date' => $this->expected_delivery_date ?: null,
                    'currency_code' => strtoupper(trim($this->currency_code)),
                    'status' => $this->status,
                    'created_by' => $isNew ? $userId : DB::raw('created_by'),
                    'approved_by' => $this->status === 'APPROVED' ? ($approvedBy ?: DB::raw('approved_by')) : ($this->status === 'DRAFT' ? null : DB::raw('approved_by')),
                ]
            );

            // Sync items
            $existingItemIds = [];
            foreach ($this->items as $itemData) {
                $item = PurchaseOrderItem::updateOrCreate(
                    [
                        'po_item_id' => $itemData['po_item_id'] ?? null,
                        'po_id' => $po->po_id,
                    ],
                    [
                        'product_id' => (int)$itemData['product_id'],
                        'quantity_ordered' => (float)$itemData['quantity_ordered'],
                        'quantity_received' => (float)($itemData['quantity_received'] ?? 0),
                        'unit_cost' => (float)$itemData['unit_cost'],
                    ]
                );
                $existingItemIds[] = $item->po_item_id;

                // Also update or seed supplier_products last purchase cost
                SupplierProduct::updateOrCreate(
                    [
                        'supplier_id' => (int)$this->supplier_id,
                        'product_id' => (int)$itemData['product_id'],
                    ],
                    [
                        'last_purchase_cost' => (float)$itemData['unit_cost'],
                    ]
                );
            }

            // Remove deleted items if editing
            if (!$isNew) {
                PurchaseOrderItem::where('po_id', $po->po_id)
                    ->whereNotIn('po_item_id', $existingItemIds)
                    ->delete();
            }

            // Auto-convert/sync to Shipping Manifest when status is SHIPPED
            if ($this->status === 'SHIPPED') {
                $this->syncShipmentForPO($po);
            }
        });

        session()->flash('message', $this->isEdit ? 'Purchase Order updated successfully.' : 'Purchase Order created successfully.');
        $this->dispatch('notify', type: 'success', title: 'Purchase Order Saved', message: 'PO ' . $this->po_number . ' ' . ($this->isEdit ? 'updated' : 'created') . ' successfully.');
        $this->showModal = false;
    }

    protected function syncShipmentForPO(PurchaseOrder $po): ImportShipment
    {
        $existingShipment = ImportShipment::where('po_id', $po->po_id)->first();
        if (!$existingShipment) {
            $year = date('Y');
            $prefix = "SHIP-{$year}-";
            $latest = ImportShipment::where('shipment_ref', 'like', $prefix . '%')
                ->orderBy('shipment_id', 'desc')
                ->first();

            $nextNum = $latest ? ((int) substr($latest->shipment_ref, strlen($prefix)) + 1) : (ImportShipment::count() + 1);

            do {
                $shipmentRef = $prefix . str_pad((string)$nextNum, 4, '0', STR_PAD_LEFT);
                $exists = ImportShipment::where('shipment_ref', $shipmentRef)->exists();
                if ($exists) {
                    $nextNum++;
                }
            } while ($exists);

            return ImportShipment::create([
                'shipment_ref' => $shipmentRef,
                'po_id' => $po->po_id,
                'port_of_entry' => 'Sohar Port Customs Station',
                'stage' => 'IN_TRANSIT',
                'eta_date' => $po->expected_delivery_date ?: date('Y-m-d', strtotime('+14 days')),
            ]);
        } else {
            if ($existingShipment->stage === 'ORDERED') {
                $existingShipment->stage = 'IN_TRANSIT';
                $existingShipment->save();
            }
            return $existingShipment;
        }
    }

    public function quickApprovePO(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to APPROVE purchase orders.');
        }

        $po = PurchaseOrder::findOrFail($id);
        $po->status = 'APPROVED';
        $po->approved_by = auth()->id() ?? 1;
        $po->save();

        $this->dispatch('notify', type: 'success', title: 'PO Approved', message: "Purchase Order {$po->po_number} has been approved.");
    }

    public function quickShipPO(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'EDIT')) {
            abort(403, 'Unauthorized action: You do not have permission to SHIP purchase orders.');
        }

        $po = PurchaseOrder::findOrFail($id);
        $po->status = 'SHIPPED';
        $po->save();

        $shipment = $this->syncShipmentForPO($po);

        $this->dispatch('notify', type: 'success', title: 'PO Dispatched & Shipped', message: "PO {$po->po_number} marked as Shipped and converted to Shipment {$shipment->shipment_ref} in the Shipping Register!");
    }

    public function confirmDeletePO(int $id, string $poNumber): void
    {
        $this->itemToDeleteId = $id;
        $this->itemToDeleteNumber = $poNumber;
        $this->showItemDeleteModal = true;
    }

    public function executeDeleteItem(): void
    {
        $this->showItemDeleteModal = false;
        if ($this->itemToDeleteId) {
            $this->deletePurchaseOrder($this->itemToDeleteId);
        }
        $this->reset(['itemToDeleteId', 'itemToDeleteNumber']);
    }

    public function deletePurchaseOrder(int $id): void
    {
        if (! auth()->user()?->hasPermission('Purchase & Import', 'DELETE')) {
            abort(403, 'Unauthorized action: You do not have permission to DELETE purchase orders.');
        }

        $po = PurchaseOrder::find($id);
        if ($po) {
            if ($po->shipments()->exists() || $po->grns()->exists()) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete PO', message: "Cannot delete PO '{$po->po_number}' because shipments or goods receipts are linked to it.");
                return;
            }

            try {
                $number = $po->po_number;
                $po->items()->delete();
                $po->delete();
                $this->dispatch('notify', type: 'warning', title: 'PO Removed', message: "Purchase Order '{$number}' deleted successfully.");
            } catch (\Throwable $e) {
                $this->dispatch('notify', type: 'error', title: 'Cannot Delete PO', message: "Cannot delete Purchase Order: " . $e->getMessage());
            }
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'supplier_filter', 'warehouse_filter', 'status_filter', 'date_from', 'date_to']);
        $this->resetPage();
    }

    public function render()
    {
        $query = PurchaseOrder::with([
            'supplier:supplier_id,supplier_name,supplier_code,currency_code,country',
            'warehouse:warehouse_id,warehouse_name,warehouse_code',
            'items:po_item_id,po_id,quantity_ordered,unit_cost',
        ]);

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('po_number', 'like', '%' . $this->search . '%')
                  ->orWhereHas('supplier', function ($sq) {
                      $sq->where('supplier_name', 'like', '%' . $this->search . '%')
                        ->orWhere('supplier_code', 'like', '%' . $this->search . '%');
                  })
                  ->orWhereHas('warehouse', function ($wq) {
                      $wq->where('warehouse_name', 'like', '%' . $this->search . '%')
                        ->orWhere('warehouse_code', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if ($this->supplier_filter) {
            $query->where('supplier_id', $this->supplier_filter);
        }

        if ($this->warehouse_filter) {
            $query->where('warehouse_id', $this->warehouse_filter);
        }

        if ($this->status_filter) {
            $query->where('status', $this->status_filter);
        }

        if ($this->date_from) {
            $query->whereDate('order_date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->whereDate('order_date', '<=', $this->date_to);
        }

        // Metrics for summary cards
        $metrics = [
            'total_pos' => PurchaseOrder::count(),
            'approved_pos' => PurchaseOrder::where('status', 'APPROVED')->count(),
            'shipped_pos' => PurchaseOrder::where('status', 'SHIPPED')->count(),
            'draft_pos' => PurchaseOrder::where('status', 'DRAFT')->count(),
            'completed_pos' => PurchaseOrder::where('status', 'COMPLETED')->count(),
        ];

        $suppliers = Supplier::where('is_active', true)
            ->select(['supplier_id', 'supplier_name', 'supplier_code', 'currency_code', 'country'])
            ->orderBy('supplier_name')
            ->get();

        $warehouses = Warehouse::where('is_active', true)
            ->select(['warehouse_id', 'warehouse_name', 'warehouse_code'])
            ->orderBy('warehouse_name')
            ->get();

        $products = Product::where('is_active', true)
            ->select(['product_id', 'sku_code', 'product_name', 'pack_size', 'base_uom_id'])
            ->with(['uom:uom_id,uom_name'])
            ->orderBy('product_name')
            ->get();

        $selectedPoDetails = null;
        if ($this->view_po_id) {
            $selectedPoDetails = PurchaseOrder::with([
                'supplier:supplier_id,supplier_name,supplier_code,currency_code,country',
                'warehouse:warehouse_id,warehouse_name,warehouse_code',
                'items.product:product_id,sku_code,product_name,pack_size,base_uom_id',
                'items.product.uom:uom_id,uom_name',
                'creator:user_id,full_name',
                'approver:user_id,full_name',
                'shipments:shipment_id,po_id,shipment_ref,stage',
                'grns:grn_id,po_id,grn_number,received_date'
            ])->find($this->view_po_id);
        }

        return view('livewire.purchase-import.purchase-order-management', [
            'purchaseOrders' => $query->orderBy('po_id', 'desc')->paginate(10),
            'metrics' => $metrics,
            'suppliers' => $suppliers,
            'warehouses' => $warehouses,
            'products' => $products,
            'selectedPoDetails' => $selectedPoDetails,
        ])->layout('layouts.app');
    }
}
