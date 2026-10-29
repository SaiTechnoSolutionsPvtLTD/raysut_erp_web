<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt {{ $sale->receipt_no }} - RMSC Retail POS</title>
    <style>
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
            width: 300px;
            margin: 0 auto;
            padding: 15px 10px;
            line-height: 1.3;
        }
        .header {
            text-align: center;
            border-bottom: 1px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .header h1 {
            font-size: 15px;
            margin: 0 0 4px 0;
            text-transform: uppercase;
            font-weight: bold;
        }
        .header .meta {
            font-size: 11px;
            color: #333;
        }
        .row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }
        .bold {
            font-weight: bold;
        }
        .border-top {
            border-top: 1px dashed #000;
            padding-top: 6px;
            margin-top: 6px;
        }
        .border-double {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 6px 0;
            margin: 6px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 6px 0;
        }
        th, td {
            font-size: 11px;
            padding: 3px 0;
        }
        th {
            border-bottom: 1px solid #000;
            text-align: left;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .permit-box {
            background: #f7f7f7;
            border: 1px solid #000;
            padding: 6px;
            margin: 8px 0;
            font-size: 11px;
        }
        .footer {
            text-align: center;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #000;
            font-size: 10px;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                width: 100%;
                margin: 0;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 7px 14px; background: #000; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">🖨️ Print 80mm Receipt</button>
        <a href="{{ route('pos.terminal') }}" style="display: inline-block; margin-left: 8px; padding: 7px 12px; background: #e2e8f0; color: #1e293b; text-decoration: none; border-radius: 4px; font-weight: bold;">&larr; POS Terminal</a>
    </div>

    <div class="header">
        <h1>Raysut Marketing & Supply Co.</h1>
        <div class="meta">{{ $sale->branch->branch_name ?? 'RMSC Retail' }}</div>
        <div class="meta">Terminal: {{ $sale->counter->counter_code ?? 'POS-01' }} | Cashier: {{ $sale->cashier->full_name ?? 'Staff' }}</div>
        <div class="meta">Receipt: <span class="bold">{{ $sale->receipt_no }}</span></div>
        <div class="meta">Date: {{ $sale->created_at->format('Y-m-d H:i:s') }}</div>
    </div>

    <!-- Line items -->
    <table>
        <thead>
            <tr>
                <th>Item / Description</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Price</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
                <tr>
                    <td colspan="4" class="bold" style="padding-top: 4px;">{{ $item->product->product_name }}</td>
                </tr>
                <tr>
                    <td style="font-size: 10px; color: #555;">VAT {{ number_format($item->vat_rate, 0) }}% ({{ $item->uom->uom_code ?? 'PCS' }})</td>
                    <td class="text-right">{{ number_format($item->quantity, 0) }}</td>
                    <td class="text-right">{{ number_format($item->unit_price, 3) }}</td>
                    <td class="text-right bold">{{ number_format($item->line_total, 3) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="border-top"></div>
    <div class="row">
        <span>Subtotal (Excl. VAT):</span>
        <span class="bold">OMR {{ number_format($sale->subtotal, 3) }}</span>
    </div>
    <div class="row">
        <span>Oman VAT Total:</span>
        <span class="bold">OMR {{ number_format($sale->vat_total, 3) }}</span>
    </div>

    <div class="border-double">
        <div class="row bold" style="font-size: 14px;">
            <span>TOTAL PAYABLE:</span>
            <span>OMR {{ number_format($sale->grand_total, 3) }}</span>
        </div>
    </div>

    <!-- Tender & Payment Breakdown -->
    <div class="row">
        <span>Payment Method:</span>
        <span class="bold">{{ $sale->payment_method }}</span>
    </div>
    @if($sale->payment_method === 'CASH' || $sale->cash_amount > 0)
        <div class="row">
            <span>Cash Tendered:</span>
            <span>OMR {{ number_format($sale->cash_amount, 3) }}</span>
        </div>
        <div class="row">
            <span>Change Returned:</span>
            <span class="bold">OMR {{ number_format($sale->change_given, 3) }}</span>
        </div>
    @endif
    @if($sale->payment_method === 'CARD' || $sale->card_amount > 0)
        <div class="row">
            <span>Card Charged:</span>
            <span>OMR {{ number_format($sale->card_amount, 3) }}</span>
        </div>
        @if($sale->card_reference)
            <div class="row" style="font-size: 10px;">
                <span>Card Ref:</span>
                <span>{{ $sale->card_reference }}</span>
            </div>
        @endif
    @endif

    <!-- Oman Permit Info Section -->
    @if($sale->permitVerification)
        <div class="permit-box">
            <div class="bold text-center" style="margin-bottom: 4px; border-bottom: 1px dashed #aaa; padding-bottom: 2px;">
                OMAN LIQUOR PERMIT COMPLIANCE
            </div>
            <div class="row"><span>Holder:</span> <span class="bold">{{ $sale->permitVerification->holder_name }}</span></div>
            <div class="row"><span>Civil ID:</span> <span>{{ $sale->permitVerification->masked_civil_id }}</span></div>
            @if($sale->permitVerification->permit_no)
                <div class="row"><span>Permit No:</span> <span>{{ $sale->permitVerification->permit_no }}</span></div>
            @endif
            @if($sale->permitVerification->remaining_amount !== null)
                <div class="row bold">
                    <span>Remaining at Verification:</span>
                    <span>OMR {{ number_format($sale->permitVerification->remaining_amount, 3) }}</span>
                </div>
            @endif
            <div class="row"><span>Verified At:</span> <span>{{ $sale->permitVerification->verified_at ? $sale->permitVerification->verified_at->format('Y-m-d H:i') : '-' }}</span></div>
            <div class="row"><span>Entry Method:</span> <span>{{ $sale->permitVerification->entry_method }}</span></div>
        </div>
    @elseif($sale->permit)
        <div class="permit-box">
            <div class="bold text-center" style="margin-bottom: 4px; border-bottom: 1px dashed #aaa; padding-bottom: 2px;">
                OMAN LIQUOR PERMIT COMPLIANCE
            </div>
            <div class="row"><span>Holder:</span> <span class="bold">{{ $sale->permit->holder_name }}</span></div>
            <div class="row"><span>Civil ID:</span> <span>{{ $sale->permit->masked_civil_id ?? $sale->masked_civil_id }}</span></div>
            <div class="row"><span>Permit No:</span> <span>{{ $sale->permit->permit_no }}</span></div>
            <div class="row bold">
                <span>Remaining Quota:</span>
                <span>OMR {{ number_format($sale->permit->remaining_quota, 3) }}</span>
            </div>
        </div>
    @endif

    <div class="footer">
        <div>Thank you for your business!</div>
        <div>Goods once sold can only be returned per store policy.</div>
        <div>RMSC Tax Invoice / Official Thermal Receipt</div>
    </div>
</body>
</html>
