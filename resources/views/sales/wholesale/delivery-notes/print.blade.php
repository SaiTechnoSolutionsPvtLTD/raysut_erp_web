<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delivery Note - {{ $deliveryNote->dn_no }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 13px; }
        .box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        .company-name { font-size: 20px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .doc-title { font-size: 24px; font-weight: 800; color: #0f172a; text-align: right; }
        .doc-no { font-family: monospace; font-size: 14px; font-weight: bold; color: #334155; }
        .grid { display: flex; justify-content: space-between; margin-bottom: 24px; }
        .col { width: 48%; }
        .label { font-size: 10px; font-weight: bold; text-transform: uppercase; color: #64748b; margin-bottom: 2px; }
        .val { font-size: 13px; font-weight: 600; color: #0f172a; margin-bottom: 6px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 24px; }
        th { background: #f8fafc; font-size: 10px; font-weight: bold; text-transform: uppercase; color: #475569; padding: 8px 10px; border-bottom: 1px solid #cbd5e1; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #f1f5f9; font-size: 12px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .signatures { display: flex; justify-content: space-between; margin-top: 60px; }
        .sig-box { width: 40%; border-top: 1px solid #94a3b8; text-align: center; padding-top: 8px; font-size: 11px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .box { border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
        @if(Route::has('wholesale.delivery-notes.show'))
            <a href="{{ route('wholesale.delivery-notes.show', $deliveryNote->delivery_note_id) }}" style="color: #64748b; text-decoration: none; font-size: 13px;">&larr; Back to Delivery Note Details</a>
        @endif
        <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">Print Delivery Note</button>
    </div>

    <div class="box">
        <div class="header">
            <div>
                <div class="company-name">RAYSUT MARINE SUPPLY & SERVICES CO. SAOG</div>
                <div style="font-size: 11px; color: #64748b;">Supply Chain Logistics & Warehouse Operations</div>
                <div style="font-size: 11px; color: #64748b;">Fulfillment Hub: {{ $deliveryNote->warehouse?->warehouse_name ?? 'Domestic Depot' }}</div>
            </div>
            <div>
                <div class="doc-title">DELIVERY NOTE</div>
                <div class="doc-no">{{ $deliveryNote->dn_no }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Order Ref: <strong>{{ $deliveryNote->salesOrder?->so_no ?? 'N/A' }}</strong></div>
            </div>
        </div>

        <div class="grid">
            <div class="col">
                <div class="label">Consignee / Customer</div>
                <div class="val">{{ $deliveryNote->salesOrder?->customer?->customer_name ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">Customer Code: {{ $deliveryNote->salesOrder?->customer?->customer_code ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">Phone: {{ $deliveryNote->salesOrder?->customer?->phone ?? 'N/A' }}</div>
            </div>
            <div class="col" style="text-align: right;">
                <div class="label">Dispatch Logistics</div>
                <div style="font-size: 12px; color: #475569;">Dispatch Date: {{ $deliveryNote->dispatched_at ? $deliveryNote->dispatched_at->format('d/m/Y H:i') : now()->format('d/m/Y') }}</div>
                <div style="font-size: 12px; color: #475569;">Vehicle Plate No: <strong>{{ $deliveryNote->vehicle_no ?? 'N/A' }}</strong></div>
                <div style="font-size: 12px; color: #475569;">Driver / Carrier: {{ $deliveryNote->driver_name ?? 'Customer Direct Pickup' }}</div>
                <div style="font-size: 12px; color: #475569;">Dispatched By: {{ $deliveryNote->dispatcher?->full_name ?? 'Warehouse Supervisor' }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Product Description</th>
                    <th style="width: 140px;">Batch / Lot No</th>
                    <th class="text-center" style="width: 80px;">Duty Status</th>
                    <th class="text-right" style="width: 120px;">Dispatched Qty</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveryNote->items as $i => $item)
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $item->product?->product_name ?? 'N/A' }}</strong>
                            <div style="font-size: 10px; color: #64748b; font-family: monospace;">SKU: {{ $item->product?->sku_code ?? '' }}</div>
                        </td>
                        <td style="font-family: monospace; font-size: 12px;">
                            {{ $item->batch?->batch_no ?? $item->batch?->batch_number ?? ($item->batch_id ? "#{$item->batch_id}" : 'N/A') }}
                        </td>
                        <td class="text-center" style="font-size: 11px;">
                            {{ $item->batch?->duty_status ?? 'DUTY_PAID' }}
                        </td>
                        <td class="text-right" style="font-family: monospace; font-weight: bold; font-size: 14px;">
                            {{ number_format((float) ($item->quantity_dispatched ?? $item->quantity ?? 0), 2) }} {{ $item->product?->uom?->uom_code ?? '' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center" style="padding: 20px; color: #94a3b8;">No items in delivery note.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if($deliveryNote->notes)
            <div style="border-top: 1px solid #e2e8f0; padding-top: 12px; margin-bottom: 24px; font-size: 11px; color: #475569;">
                <strong>Logistics Remarks / Instructions:</strong><br>
                {{ $deliveryNote->notes }}
            </div>
        @endif

        <div class="signatures">
            <div class="sig-box">
                Warehouse Fulfillment Lead<br>
                Raysut Marine Supply & Services
            </div>
            <div class="sig-box">
                Driver / Consignee Signature<br>
                Received in Good Condition: _________________
            </div>
        </div>
    </div>
</body>
</html>
