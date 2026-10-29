@php
    $logoFile = public_path('images/rmsc-logo-full.jpg');
    $hasLogo = file_exists($logoFile);

    $statusColors = [
        'DRAFT'     => ['#475569', '#F1F5F9', '#CBD5E1'],
        'SENT'      => ['#1D4ED8', '#EFF6FF', '#BFDBFE'],
        'ACCEPTED'  => ['#047857', '#ECFDF5', '#A7F3D0'],
        'CONVERTED' => ['#6D28D9', '#F5F3FF', '#DDD6FE'],
        'REJECTED'  => ['#BE123C', '#FFF1F2', '#FECDD3'],
        'EXPIRED'   => ['#B45309', '#FFFBEB', '#FDE68A'],
    ];
    [$stText, $stBg, $stBorder] = $statusColors[$quotation->status] ?? $statusColors['DRAFT'];

    $watermark = in_array($quotation->status, ['DRAFT', 'REJECTED', 'EXPIRED']) ? $quotation->status : null;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Wholesale Quotation - {{ $quotation->quote_no }}</title>
    <style>
        @page { size: A4; margin: 10mm; }

        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; }
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 12px;
            line-height: 1.45;
            color: #0F172A;
            background: #E2E8F0;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        /* Screen toolbar */
        .toolbar {
            width: 210mm; max-width: 100%; margin: 16px auto 12px;
            display: table;
        }
        .toolbar .left, .toolbar .right { display: table-cell; vertical-align: middle; }
        .toolbar .right { text-align: right; }
        .toolbar a { color: #475569; text-decoration: none; font-size: 13px; font-weight: 600; }
        .toolbar a:hover { color: #0F172A; }
        .btn {
            display: inline-block; background: #C8151C; color: #fff; border: 0; padding: 9px 18px;
            border-radius: 8px; font-size: 13px; font-weight: 700; cursor: pointer;
        }
        .btn:hover { background: #A81218; }

        /* Sheet */
        .sheet {
            position: relative;
            width: 210mm; max-width: 100%;
            min-height: 297mm;
            margin: 0 auto 24px;
            background: #fff;
            padding: 14mm 14mm 12mm;
            box-shadow: 0 10px 30px rgba(15, 23, 42, .15);
            overflow: hidden;
        }

        /* Watermark */
        .watermark {
            position: absolute; top: 42%; left: 0; right: 0; text-align: center; z-index: 0;
            font-size: 120px; font-weight: 800; letter-spacing: 10px;
            color: rgba(15, 23, 42, .045);
            transform: rotate(-28deg);
            pointer-events: none; user-select: none;
        }
        .content { position: relative; z-index: 1; }

        /* Header */
        table { border-collapse: collapse; }
        .header { width: 100%; }
        .header td { vertical-align: top; }
        .logo { max-height: 62px; max-width: 230px; display: block; margin-bottom: 8px; }
        .company-name { font-size: 15px; font-weight: 800; color: #0F172A; letter-spacing: .2px; }
        .company-meta { font-size: 10.5px; color: #64748B; margin-top: 2px; }
        .doc-title { font-size: 25px; font-weight: 800; color: #C8151C; letter-spacing: 1px; text-align: right; line-height: 1.1; }
        .doc-sub { text-align: right; font-size: 10.5px; color: #64748B; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; }
        .doc-no { text-align: right; font-family: 'Courier New', monospace; font-size: 15px; font-weight: 700; margin-top: 8px; }
        .status {
            display: inline-block; margin-top: 6px; padding: 3px 12px; border-radius: 999px;
            font-size: 10px; font-weight: 800; letter-spacing: 1px; border: 1px solid;
        }
        .rule { height: 4px; background: #C8151C; margin: 14px 0 2px; }
        .rule-thin { height: 1px; background: #0F172A; margin-bottom: 16px; }

        /* Info cards */
        .info { width: 100%; margin-bottom: 18px; }
        .info td.card-cell { width: 50%; vertical-align: top; }
        .info td.gap { width: 14px; }
        .card { border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; }
        .card-head {
            background: #F8FAFC; border-bottom: 1px solid #E2E8F0; padding: 7px 12px;
            font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.4px; color: #64748B;
        }
        .card-body { padding: 10px 12px; }
        .client-name { font-size: 14px; font-weight: 800; color: #0F172A; margin-bottom: 4px; }
        .kv { width: 100%; }
        .kv td { padding: 2.5px 0; font-size: 11.5px; vertical-align: top; }
        .kv td.k { color: #64748B; width: 42%; }
        .kv td.v { font-weight: 600; color: #0F172A; text-align: right; }
        .kv.left td.v { text-align: left; }
        .kv.left td.k { width: 30%; }
        .mono { font-family: 'Courier New', monospace; }

        /* Items */
        .items { width: 100%; margin-bottom: 16px; }
        .items thead { display: table-header-group; }
        .items th {
            background: #0F172A; color: #fff; padding: 8px 9px; text-align: left;
            font-size: 9.5px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px;
        }
        .items th:first-child { border-top-left-radius: 6px; }
        .items th:last-child { border-top-right-radius: 6px; }
        .items td { padding: 9px; border-bottom: 1px solid #E2E8F0; font-size: 11.5px; vertical-align: top; }
        .items tbody tr:nth-child(even) td { background: #F8FAFC; }
        .items tr { page-break-inside: avoid; }
        .num { text-align: right; font-family: 'Courier New', monospace; white-space: nowrap; }
        .ctr { text-align: center; }
        .prod { font-weight: 700; color: #0F172A; }
        .sku { font-family: 'Courier New', monospace; font-size: 9.5px; color: #94A3B8; margin-top: 1px; }
        .idx { color: #94A3B8; }

        /* Bottom: terms + totals */
        .bottom { width: 100%; margin-bottom: 20px; page-break-inside: avoid; }
        .bottom td { vertical-align: top; }
        .terms-box { padding-right: 18px; }
        .terms-title { font-size: 9.5px; font-weight: 800; text-transform: uppercase; letter-spacing: 1.4px; color: #64748B; margin-bottom: 6px; }
        .terms-list { margin: 0; padding-left: 16px; font-size: 11px; color: #475569; }
        .terms-list li { margin-bottom: 3px; }
        .notes { margin-top: 10px; padding: 8px 10px; background: #F8FAFC; border-left: 3px solid #C8151C; border-radius: 4px; font-size: 11px; color: #334155; white-space: pre-line; }
        .totals { width: 100%; border: 1px solid #E2E8F0; border-radius: 8px; overflow: hidden; }
        .totals td { padding: 7px 12px; font-size: 12px; border-bottom: 1px solid #F1F5F9; }
        .totals td.r { text-align: right; font-family: 'Courier New', monospace; font-weight: 700; }
        .totals tr.grand td { background: #0F172A; color: #fff; font-size: 14px; font-weight: 800; border-bottom: 0; padding: 10px 12px; }
        .totals tr.grand td.r { color: #fff; }
        .currency { font-size: 10px; color: #94A3B8; text-align: right; margin-top: 4px; }

        /* Signatures */
        .sign { width: 100%; margin-top: 34px; page-break-inside: avoid; }
        .sign td { width: 45%; vertical-align: bottom; }
        .sign td.sp { width: 10%; }
        .sig-line { border-top: 1px solid #94A3B8; padding-top: 6px; text-align: center; font-size: 10.5px; font-weight: 700; color: #334155; height: 62px; }
        .sig-line span { display: block; font-weight: 400; color: #94A3B8; margin-top: 2px; }

        .footer {
            margin-top: 22px; padding-top: 8px; border-top: 1px solid #E2E8F0;
            font-size: 9.5px; color: #94A3B8; text-align: center;
        }

        @media print {
            body { background: #fff; }
            .no-print { display: none !important; }
            .sheet { margin: 0; padding: 0; width: auto; min-height: 0; box-shadow: none; }
        }
    </style>
</head>
<body>
    <!-- Screen-only toolbar -->
    <div class="toolbar no-print">
        <div class="left">
            @if(Route::has('wholesale.quotations.show'))
                <a href="{{ route('wholesale.quotations.show', $quotation->quotation_id) }}">&larr; Back to Quotation Details</a>
            @elseif(Route::has('wholesale.quotations.index'))
                <a href="{{ route('wholesale.quotations.index') }}">&larr; Back to Quotations</a>
            @endif
        </div>
        <div class="right">
            <button type="button" class="btn" onclick="window.print()">Print / Save as PDF</button>
        </div>
    </div>

    <div class="sheet">
        @if($watermark)
            <div class="watermark">{{ $watermark }}</div>
        @endif

        <div class="content">
            <!-- Header -->
            <table class="header">
                <tr>
                    <td style="width: 55%;">
                        @if($hasLogo)
                            <img class="logo" src="{{ asset('images/rmsc-logo-full.jpg') }}?v={{ filemtime($logoFile) }}" alt="Raysut Marine Supply & Services">
                        @endif
                        <div class="company-name">RAYSUT MARKETING &amp; SUPPLY CO. LLC</div>
                        <div class="company-meta">Port of Salalah &amp; Muscat Branches, Sultanate of Oman</div>
                        <div class="company-meta">VAT Reg. No: <span class="mono">{{ config('rmsc.company_vat_no', 'OM1100000001') }}</span></div>
                    </td>
                    <td style="width: 45%;">
                        <div class="doc-title">WHOLESALE QUOTATION</div>
                        <div class="doc-sub">Commercial Price Offer</div>
                        <div class="doc-no">{{ $quotation->quote_no }}</div>
                        <div style="text-align: right;">
                            <span class="status" style="color: {{ $stText }}; background: {{ $stBg }}; border-color: {{ $stBorder }};">{{ $quotation->status }}</span>
                        </div>
                    </td>
                </tr>
            </table>
            <div class="rule"></div>
            <div class="rule-thin"></div>

            <!-- Client + Quotation details -->
            <table class="info">
                <tr>
                    <td class="card-cell">
                        <div class="card">
                            <div class="card-head">Quotation To</div>
                            <div class="card-body">
                                <div class="client-name">{{ $quotation->customer?->customer_name ?? 'N/A' }}</div>
                                <table class="kv left">
                                    <tr><td class="k">Code</td><td class="v mono">{{ $quotation->customer?->customer_code ?? 'N/A' }}</td></tr>
                                    <tr><td class="k">VAT No</td><td class="v mono">{{ $quotation->customer?->vat_number ?? 'N/A' }}</td></tr>
                                    <tr><td class="k">Phone</td><td class="v">{{ $quotation->customer?->phone ?? 'N/A' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </td>
                    <td class="gap"></td>
                    <td class="card-cell">
                        <div class="card">
                            <div class="card-head">Quotation Details</div>
                            <div class="card-body">
                                <table class="kv">
                                    <tr><td class="k">Date Issued</td><td class="v">{{ $quotation->created_at ? $quotation->created_at->format('d M Y') : now()->format('d M Y') }}</td></tr>
                                    <tr><td class="k">Valid Until</td><td class="v">{{ $quotation->valid_until ? $quotation->valid_until->format('d M Y') : 'N/A' }}</td></tr>
                                    <tr><td class="k">Payment Terms</td><td class="v">{{ $quotation->payment_terms_days ?? 0 }} days</td></tr>
                                    <tr><td class="k">Branch</td><td class="v">{{ $quotation->branch?->branch_name ?? '-' }}</td></tr>
                                    <tr><td class="k">Prepared By</td><td class="v">{{ $quotation->creator?->full_name ?? 'System' }}</td></tr>
                                </table>
                            </div>
                        </div>
                    </td>
                </tr>
            </table>

            <!-- Items -->
            <table class="items">
                <thead>
                    <tr>
                        <th style="width: 26px;">#</th>
                        <th>Product Description</th>
                        <th class="ctr" style="width: 46px;">UOM</th>
                        <th style="width: 56px; text-align: right;">Qty</th>
                        <th style="width: 78px; text-align: right;">Unit Price</th>
                        <th style="width: 44px; text-align: right;">VAT %</th>
                        <th style="width: 70px; text-align: right;">VAT</th>
                        <th style="width: 86px; text-align: right;">Line Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($quotation->items as $i => $item)
                        <tr>
                            <td class="idx">{{ $i + 1 }}</td>
                            <td>
                                <div class="prod">{{ $item->product?->product_name ?? 'N/A' }}</div>
                                <div class="sku">SKU: {{ $item->product?->sku_code ?? '-' }}</div>
                            </td>
                            <td class="ctr">{{ $item->uom?->uom_code ?? 'PCS' }}</td>
                            <td class="num">{{ number_format((float) ($item->quantity ?? 0), 2) }}</td>
                            <td class="num">{{ number_format((float) ($item->unit_price ?? 0), 3) }}</td>
                            <td class="num">{{ number_format((float) ($item->vat_rate ?? 0), 1) }}%</td>
                            <td class="num">{{ number_format((float) ($item->vat_amount ?? 0), 3) }}</td>
                            <td class="num" style="font-weight: 700; color: #0F172A;">{{ number_format((float) ($item->line_total ?? 0), 3) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="ctr" style="padding: 22px; color: #94A3B8;">No quotation line items recorded.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Terms + Totals -->
            <table class="bottom">
                <tr>
                    <td class="terms-box" style="width: 56%;">
                        <div class="terms-title">Terms &amp; Conditions</div>
                        <ul class="terms-list">
                            <li>This quotation is valid until <strong>{{ $quotation->valid_until ? $quotation->valid_until->format('d M Y') : 'the date agreed' }}</strong>.</li>
                            <li>Payment terms: <strong>{{ $quotation->payment_terms_days ?? 0 }} days</strong> from the invoice date.</li>
                            <li>All amounts are in Omani Rial (OMR); VAT is shown per line.</li>
                            <li>Prices are locked into the Sales Order once this quotation is accepted and converted.</li>
                        </ul>
                        @if($quotation->notes)
                            <div class="notes"><strong>Notes:</strong> {{ $quotation->notes }}</div>
                        @endif
                    </td>
                    <td style="width: 44%;">
                        <table class="totals">
                            <tr>
                                <td>Subtotal (excl. VAT)</td>
                                <td class="r">{{ number_format((float) ($quotation->subtotal ?? 0), 3) }}</td>
                            </tr>
                            <tr>
                                <td>VAT</td>
                                <td class="r">{{ number_format((float) ($quotation->vat_total ?? 0), 3) }}</td>
                            </tr>
                            <tr class="grand">
                                <td>Grand Total (OMR)</td>
                                <td class="r">{{ number_format((float) ($quotation->grand_total ?? 0), 3) }}</td>
                            </tr>
                        </table>
                        <div class="currency">Currency: Omani Rial (OMR), 3 decimals</div>
                    </td>
                </tr>
            </table>

            <!-- Signatures -->
            <table class="sign">
                <tr>
                    <td>
                        <div class="sig-line">
                            Authorized Commercial Officer
                            <span>Raysut Marine Supply &amp; Services</span>
                        </div>
                    </td>
                    <td class="sp"></td>
                    <td>
                        <div class="sig-line">
                            Client Acceptance &amp; Stamp
                            <span>Date: ____________________</span>
                        </div>
                    </td>
                </tr>
            </table>

            <div class="footer">
                System-generated quotation {{ $quotation->quote_no }} &bull; Printed {{ now()->format('d M Y, H:i') }}
            </div>
        </div>
    </div>
</body>
</html>