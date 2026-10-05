<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shift Summary #{{ $shift->shift_id }} - RMSC ERP</title>
    <style>
        body { font-family: 'Courier New', Courier, monospace; font-size: 13px; color: #000; width: 300px; margin: 0 auto; padding: 15px; }
        .header { text-align: center; border-bottom: 1px dashed #000; padding-bottom: 10px; margin-bottom: 10px; }
        .header h2 { font-size: 16px; margin: 0 0 5px 0; text-transform: uppercase; }
        .row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .bold { font-weight: bold; }
        .border-top { border-top: 1px dashed #000; padding-top: 8px; margin-top: 8px; }
        .footer { text-align: center; margin-top: 15px; border-top: 1px dashed #000; padding-top: 10px; font-size: 11px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 15px; text-align: center;">
        <button onclick="window.print()" style="padding: 6px 12px; background: #000; color: #fff; border: none; border-radius: 4px; cursor: pointer;">🖨️ Print Summary Slip</button>
    </div>

    <div class="header">
        <h2>RMSC ERP - SHIFT SUMMARY</h2>
        <div>{{ $shift->branch->branch_name ?? 'HQ' }}</div>
        <div>Terminal: {{ $shift->counter->counter_code ?? 'CNT' }}</div>
    </div>

    <div class="row"><span class="bold">Shift ID:</span> <span>#{{ $shift->shift_id }}</span></div>
    <div class="row"><span class="bold">Cashier:</span> <span>{{ $shift->user->full_name }}</span></div>
    <div class="row"><span class="bold">Opened:</span> <span>{{ $shift->opened_at->format('Y-m-d H:i') }}</span></div>
    <div class="row"><span class="bold">Closed:</span> <span>{{ $shift->closed_at ? $shift->closed_at->format('Y-m-d H:i') : 'OPEN' }}</span></div>

    <div class="border-top"></div>
    <div class="row"><span class="bold">Opening Float:</span> <span>OMR {{ number_format($shift->opening_float, 3) }}</span></div>
    <div class="row"><span class="bold">Expected Cash:</span> <span>OMR {{ number_format($shift->expected_cash, 3) }}</span></div>
    <div class="row"><span class="bold">Counted Cash:</span> <span>OMR {{ number_format($shift->counted_cash ?? 0, 3) }}</span></div>
    <div class="row"><span class="bold">Card Slips Total:</span> <span>OMR {{ number_format($shift->card_total ?? 0, 3) }}</span></div>

    <div class="border-top"></div>
    <div class="row bold">
        <span>Variance (Diff):</span>
        <span>OMR {{ number_format($shift->variance ?? 0, 3) }}</span>
    </div>
    <div class="row">
        <span>Variance Status:</span>
        <span class="bold">{{ $shift->variance_status }}</span>
    </div>

    @if($shift->approved_by)
        <div class="border-top"></div>
        <div class="row"><span>Approved By:</span> <span>{{ $shift->approver->full_name }}</span></div>
        <div class="row"><span>Approved At:</span> <span>{{ $shift->approved_at ? $shift->approved_at->format('Y-m-d H:i') : '' }}</span></div>
    @endif

    @if($shift->notes)
        <div class="border-top"></div>
        <div class="bold">Notes:</div>
        <div>{{ $shift->notes }}</div>
    @endif

    <div class="footer">
        <div>Printed: {{ now()->format('Y-m-d H:i:s') }}</div>
        <div>End of Shift Report - Cashier Sign: ____________</div>
    </div>
</body>
</html>
