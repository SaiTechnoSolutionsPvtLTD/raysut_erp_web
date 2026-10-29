<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tax Invoice - {{ $invoice->invoice_no }}</title>
    <style>
        @page { size: A4; margin: 12mm; }
        * { box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #0f172a; margin: 0; padding: 24px; font-size: 12px; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .page { max-width: 800px; margin: auto; }
        .mono { font-family: 'Courier New', monospace; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* Header */
        .header { display: flex; justify-content: space-between; align-items: flex-start; }
        .logo { height: 64px; width: auto; display: block; margin-bottom: 18px; }
        .company-name { font-size: 17px; font-weight: 800; color: #0f172a; margin-bottom: 4px; }
        .company-sub { font-size: 11px; color: #64748b; margin-bottom: 2px; }
        .company-sub .vat { font-family: 'Courier New', monospace; color: #94a3b8; }
        .doc-title { font-size: 28px; font-weight: 800; color: #c8151c; text-align: right; line-height: 1.05; letter-spacing: .5px; }
        .doc-subtitle { font-size: 10.5px; font-weight: 600; letter-spacing: 2.4px; color: #64748b; text-align: right; text-transform: uppercase; margin-top: 4px; }
        .doc-no { font-family: 'Courier New', monospace; font-size: 15px; font-weight: bold; color: #0f172a; text-align: right; margin-top: 14px; }
        .status-wrap { text-align: right; margin-top: 10px; }
        .status { display: inline-block; background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; border-radius: 999px; padding: 4px 16px; font-size: 10px; font-weight: 800; letter-spacing: 1.5px; text-transform: uppercase; }
        .rule { border-top: 5px solid #c8151c; border-bottom: 1px solid #0f172a; height: 8px; margin: 14px 0 22px; }

        /* Info cards */
        .cards { display: flex; justify-content: space-between; gap: 14px; margin-bottom: 26px; align-items: flex-start; }
        .card { width: 49%; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .card-head { background: #f8fafc; border-bottom: 1px solid #e2e8f0; padding: 9px 14px; font-size: 10px; font-weight: 800; letter-spacing: 2px; color: #475569; text-transform: uppercase; }
        .card-body { padding: 12px 14px; }
        .cust-name { font-size: 15px; font-weight: 800; color: #0f172a; margin-bottom: 8px; }
        .kv { display: flex; justify-content: space-between; padding: 4px 0; font-size: 12px; color: #64748b; }
        .kv b { color: #0f172a; font-weight: 700; text-align: right; }
        .kv b.mono { font-size: 11px; }
        .cust .kv { justify-content: flex-start; }
        .cust .kv span { width: 60px; }

        /* Items table */
        table.items { width: 100%; border-collapse: separate; border-spacing: 0; margin-bottom: 18px; }
        table.items thead th { background: #0f172a; color: #fff; font-size: 9.5px; font-weight: 800; letter-spacing: 1.4px; text-transform: uppercase; padding: 12px 8px; text-align: left; line-height: 1.3; }
        table.items thead th:first-child { border-top-left-radius: 8px; padding-left: 14px; }
        table.items thead th:last-child { border-top-right-radius: 8px; padding-right: 14px; }
        table.items td { padding: 11px 8px; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: middle; }
        table.items td:first-child { padding-left: 14px; color: #94a3b8; }
        table.items td:last-child { padding-right: 14px; }
        table.items tbody tr:nth-child(even) td { background: #f8fafc; }
        .prod { font-weight: 700; color: #0f172a; }
        .sku { font-family: 'Courier New', monospace; font-size: 9px; color: #94a3b8; margin-top: 2px; }
        .num { font-family: 'Courier New', monospace; color: #475569; }
        .num-strong { font-family: 'Courier New', monospace; font-weight: 800; color: #0f172a; }

        /* Terms + totals */
        .bottom { display: flex; justify-content: space-between; gap: 24px; align-items: flex-start; margin-bottom: 38px; }
        .terms { width: 52%; }
        .terms-title { font-size: 10px; font-weight: 800; letter-spacing: 2px; color: #475569; text-transform: uppercase; margin-bottom: 8px; }
        .terms ul { margin: 0; padding-left: 18px; font-size: 11.5px; color: #475569; line-height: 1.6; }
        .terms ul li { margin-bottom: 2px; }
        .bank { margin-top: 12px; font-size: 11px; color: #475569; line-height: 1.6; }
        .bank .mono { color: #0f172a; font-weight: 700; }
        .totals { width: 42%; }
        .totals-box { border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; }
        .t-row { display: flex; justify-content: space-between; padding: 11px 14px; border-bottom: 1px solid #e2e8f0; font-size: 12.5px; color: #0f172a; }
        .t-row .mono { font-weight: 700; }
        .t-grand { background: #0f172a; color: #fff; display: flex; justify-content: space-between; align-items: center; padding: 14px; font-size: 14.5px; font-weight: 800; border-bottom: 0; }
        .t-grand .mono { font-weight: 800; }
        .currency { font-size: 10px; color: #94a3b8; text-align: right; margin-top: 6px; }

        /* Signatures & footer */
        .signatures { display: flex; justify-content: space-between; gap: 40px; margin-top: 20px; }
        .sig { width: 48%; border-top: 1px solid #94a3b8; padding-top: 8px; text-align: center; font-size: 11px; }
        .sig b { display: block; color: #0f172a; font-size: 11px; }
        .sig span { color: #94a3b8; }
        .footer { border-top: 1px solid #e2e8f0; margin-top: 40px; padding-top: 12px; text-align: center; font-size: 10px; color: #94a3b8; }

        @media print {
            body { padding: 0; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="max-width: 800px; margin: 0 auto 16px; display: flex; justify-content: space-between; align-items: center;">
        @if(Route::has('wholesale.invoices.show'))
            <a href="{{ route('wholesale.invoices.show', $invoice->invoice_id) }}" style="color: #64748b; text-decoration: none; font-size: 13px;">&larr; Back to Invoice Details</a>
        @endif
        <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="page">
        {{-- HEADER --}}
        <div class="header">
            <div>
                {{-- Use the same logo path as the quotation view if it differs --}}
                <img class="logo" src="{{ asset('images/rmsc-logo-full.jpg') }}" alt="RMSC">
                <div class="company-name">RAYSUT MARKETING &amp; SUPPLY CO. LLC</div>
                <div class="company-sub">Port of Salalah &amp; Muscat Branches, Sultanate of Oman</div>
                <div class="company-sub">VAT Reg. No: <span class="vat">{{ config('rmsc.company_vat_no', 'OM1100000001') }}</span></div>
            </div>
            <div>
                <div class="doc-title">TAX INVOICE</div>
                <div class="doc-subtitle">فاتورة ضريبية &bull; Official Tax Document</div>
                <div class="doc-no">{{ $invoice->invoice_no }}</div>
                <div class="status-wrap"><span class="status">{{ str_replace('_', ' ', $invoice->status) }}</span></div>
            </div>
        </div>
        <div class="rule"></div>

        {{-- INFO CARDS --}}
        <div class="cards">
            <div class="card cust">
                <div class="card-head">Invoice To</div>
                <div class="card-body">
                    <div class="cust-name">{{ $invoice->customer?->customer_name ?? 'N/A' }}</div>
                    <div class="kv"><span>Code</span><b class="mono">{{ $invoice->customer?->customer_code ?? 'N/A' }}</b></div>
                    <div class="kv"><span>VAT No</span><b class="mono">{{ $invoice->customer?->vat_number ?? 'NOT REGISTERED' }}</b></div>
                    <div class="kv"><span>Phone</span><b class="mono">{{ $invoice->customer?->phone ?? 'N/A' }}</b></div>
                </div>
            </div>
            <div class="card">
                <div class="card-head">Invoice Details</div>
                <div class="card-body">
                    <div class="kv"><span>Date Issued</span><b>{{ $invoice->invoice_date ? $invoice->invoice_date->format('d M Y') : now()->format('d M Y') }}</b></div>
                    <div class="kv"><span>Due Date</span><b>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'N/A' }}</b></div>
                    <div class="kv"><span>Payment Terms</span><b>{{ $invoice->payment_terms_days ?? 0 }} days</b></div>
                    <div class="kv"><span>Sales Order Ref</span><b class="mono">{{ $invoice->salesOrder?->so_no ?? 'N/A' }}</b></div>
                    @if($invoice->deliveryNote)
                        <div class="kv"><span>Delivery Note Ref</span><b class="mono">{{ $invoice->deliveryNote?->dn_no ?? '' }}</b></div>
                    @endif
                </div>
            </div>
        </div>

        {{-- ITEMS --}}
        <table class="items">
            <thead>
                <tr>
                    <th style="width: 28px;">#</th>
                    <th>Product Description</th>
                    <th class="text-center" style="width: 48px;">UOM</th>
                    <th class="text-right" style="width: 52px;">Qty</th>
                    <th class="text-right" style="width: 66px;">Unit<br>Price</th>
                    <th class="text-right" style="width: 46px;">VAT<br>%</th>
                    <th class="text-right" style="width: 58px;">VAT</th>
                    <th class="text-right" style="width: 76px;">Line Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoice->items as $i => $item)
                    @php $batchNo = $item->batch?->batch_no ?? $item->batch?->batch_number ?? ($item->batch_id ? "#{$item->batch_id}" : null); @endphp
                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td>
                            <div class="prod">{{ $item->product?->product_name ?? 'N/A' }}</div>
                            <div class="sku">SKU: {{ $item->product?->sku_code ?? '' }}@if($batchNo) &nbsp;&bull;&nbsp; BATCH: {{ $batchNo }}@endif</div>
                        </td>
                        <td class="text-center">{{ $item->uom?->uom_code ?? ($item->product?->uom?->uom_code ?? 'PCS') }}</td>
                        <td class="text-right num">{{ number_format((float) ($item->quantity ?? 0), 2) }}</td>
                        <td class="text-right num">{{ number_format((float) ($item->unit_price ?? 0), 3) }}</td>
                        <td class="text-right num">{{ number_format((float) ($item->vat_rate ?? 0), 1) }}%</td>
                        <td class="text-right num">{{ number_format((float) ($item->vat_amount ?? 0), 3) }}</td>
                        <td class="text-right num-strong">{{ number_format((float) ($item->line_total ?? 0), 3) }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center" style="padding: 22px; color: #94a3b8;">No line items on invoice.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- TERMS + TOTALS --}}
        <div class="bottom">
            <div class="terms">
                <div class="terms-title">Payment Terms &amp; Bank Details</div>
                <ul>
                    <li>Payment is due by <b>{{ $invoice->due_date ? $invoice->due_date->format('d M Y') : 'the due date' }}</b>.</li>
                    <li>Payment terms: <b>{{ $invoice->payment_terms_days ?? 0 }} days</b> from the invoice date.</li>
                    <li>All amounts are in Omani Rial (OMR); VAT is shown per line.</li>
                    @if($invoice->notes)
                        <li>{{ $invoice->notes }}</li>
                    @endif
                </ul>
                <div class="bank">
                    Bank: <b>Bank Muscat SAOG</b> &mdash; Salalah Corporate Branch<br>
                    Account: <b>Raysut Marketing &amp; Supply Co. LLC</b><br>
                    IBAN: <span class="mono">OM98BMUS0123456789012345</span> &nbsp;|&nbsp; SWIFT: <span class="mono">BMUSOMRX</span>
                </div>
            </div>
            <div class="totals">
                <div class="totals-box">
                    <div class="t-row"><span>Subtotal (excl. VAT)</span><span class="mono">{{ number_format((float) ($invoice->subtotal ?? 0), 3) }}</span></div>
                    <div class="t-row"><span>VAT</span><span class="mono">{{ number_format((float) ($invoice->vat_total ?? 0), 3) }}</span></div>
                    <div class="t-grand"><span>Grand Total (OMR)</span><span class="mono">{{ number_format((float) ($invoice->grand_total ?? 0), 3) }}</span></div>
                </div>
                <div class="currency">Currency: Omani Rial (OMR), 3 decimals</div>
            </div>
        </div>

        {{-- SIGNATURES --}}
        <div class="signatures">
            <div class="sig">
                <b>Authorized Finance Officer</b>
                <span>Raysut Marine Supply &amp; Services</span>
            </div>
            <div class="sig">
                <b>Client Acknowledgment &amp; Stamp</b>
                <span>Date: ______________________</span>
            </div>
        </div>

        <div class="footer">
            System-generated tax invoice {{ $invoice->invoice_no }} &bull; Printed {{ now()->format('d M Y, H:i') }}
        </div>
    </div>
</body>
</html>