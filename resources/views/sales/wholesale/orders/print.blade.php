<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sales Order - {{ $order->so_no }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 13px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; }
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
        .totals { width: 280px; margin-left: auto; margin-bottom: 30px; }
        .totals-row { display: flex; justify-content: space-between; padding: 6px 0; border-bottom: 1px solid #f1f5f9; }
        .grand-total { border-top: 2px solid #0f172a; border-bottom: 2px solid #0f172a; font-weight: bold; font-size: 15px; color: #991b1b; padding: 8px 0; }
        .terms { border-top: 1px solid #e2e8f0; padding-top: 16px; font-size: 11px; color: #64748b; }
        .signatures { display: flex; justify-content: space-between; margin-top: 50px; }
        .sig-box { width: 40%; border-top: 1px solid #94a3b8; text-align: center; padding-top: 8px; font-size: 11px; font-weight: bold; }
        @media print {
            body { padding: 0; }
            .invoice-box { border: none; padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
        @if(Route::has('wholesale.orders.show'))
            <a href="{{ route('wholesale.orders.show', $order->order_id) }}" style="color: #64748b; text-decoration: none; font-size: 13px;">&larr; Back to Order Details</a>
        @endif
        <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div>
                <div class="company-name">RAYSUT MARINE SUPPLY & SERVICES CO. SAOG</div>
                <div style="font-size: 11px; color: #64748b;">Port Operations & Domestic Distribution</div>
                <div style="font-size: 11px; color: #64748b;">Company VAT Identification: {{ config('rmsc.company_vat_no', 'OM1100000001') }}</div>
            </div>
            <div>
                <div class="doc-title">SALES ORDER</div>
                <div class="doc-no">{{ $order->so_no }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Status: <strong>{{ str_replace('_', ' ', $order->status) }}</strong></div>
            </div>
        </div>

        <div class="grid">
            <div class="col">
                <div class="label">Customer Information</div>
                <div class="val">{{ $order->customer?->customer_name ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">Customer Code: {{ $order->customer?->customer_code ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">VAT Number: {{ $order->customer?->vat_number ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">Phone: {{ $order->customer?->phone ?? 'N/A' }}</div>
            </div>
            <div class="col" style="text-align: right;">
                <div class="label">Order Details</div>
                <div style="font-size: 12px; color: #475569;">Order Date: {{ $order->order_date ? $order->order_date->format('d/m/Y') : ($order->created_at ? $order->created_at->format('d/m/Y') : now()->format('d/m/Y')) }}</div>
                <div style="font-size: 12px; color: #475569;">Payment Terms: {{ $order->payment_terms_days ?? 0 }} Calendar Days</div>
                <div style="font-size: 12px; color: #475569;">Fulfillment Hub: {{ $order->warehouse?->warehouse_name ?? 'Domestic Depot' }}</div>
                <div style="font-size: 12px; color: #475569;">Branch: {{ $order->branch?->branch_name ?? 'Salalah HQ' }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Product Description</th>
                    <th class="text-center" style="width: 60px;">UOM</th>
                    <th class="text-right" style="width: 70px;">Qty</th>
                    <th class="text-right" style="width: 90px;">Price (OMR)</th>
                    <th class="text-right" style="width: 60px;">VAT %</th>
                    <th class="text-right" style="width: 80px;">VAT (OMR)</th>
                    <th class="text-right" style="width: 100px;">Total (OMR)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($order->items as $i => $item)
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">{{ $i + 1 }}</td>
                        <td>
                            <strong>{{ $item->product?->product_name ?? 'N/A' }}</strong>
                            <div style="font-size: 10px; color: #64748b; font-family: monospace;">SKU: {{ $item->product?->sku_code ?? '' }}</div>
                        </td>
                        <td class="text-center">{{ $item->uom?->uom_code ?? ($item->product?->uom?->uom_code ?? 'PCS') }}</td>
                        <td class="text-right" style="font-family: monospace; font-weight: bold;">{{ number_format((float) ($item->quantity ?? 0), 2) }}</td>
                        <td class="text-right" style="font-family: monospace;">{{ number_format((float) ($item->unit_price ?? 0), 3) }}</td>
                        <td class="text-right" style="font-family: monospace;">{{ number_format((float) ($item->vat_rate ?? 0), 1) }}%</td>
                        <td class="text-right" style="font-family: monospace;">{{ number_format((float) ($item->vat_amount ?? 0), 3) }}</td>
                        <td class="text-right" style="font-family: monospace; font-weight: bold;">{{ number_format((float) ($item->line_total ?? 0), 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 20px; color: #94a3b8;">No order items recorded.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Subtotal (Net):</span>
                <span style="font-family: monospace; font-weight: bold;">{{ number_format((float) ($order->subtotal ?? 0), 3) }} OMR</span>
            </div>
            <div class="totals-row">
                <span>Applicable VAT:</span>
                <span style="font-family: monospace; font-weight: bold;">{{ number_format((float) ($order->vat_total ?? 0), 3) }} OMR</span>
            </div>
            <div class="totals-row grand-total">
                <span>Grand Total:</span>
                <span style="font-family: monospace;">{{ number_format((float) ($order->grand_total ?? 0), 3) }} OMR</span>
            </div>
        </div>

        @if($order->notes)
            <div class="terms">
                <strong>Order Instructions / Notes:</strong><br>
                {{ $order->notes }}
            </div>
        @endif

        <div class="signatures">
            <div class="sig-box">
                Issued By (RMSC Sales)<br>
                {{ $order->creator?->full_name ?? 'Authorized Officer' }}
            </div>
            <div class="sig-box">
                Customer Confirmation<br>
                Date: ________________________
            </div>
        </div>
    </div>
</body>
</html>

