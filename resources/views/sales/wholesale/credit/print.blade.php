<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Credit Statement - {{ $customer->customer_name }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Arial, sans-serif; color: #1e293b; margin: 0; padding: 24px; font-size: 13px; }
        .invoice-box { max-width: 800px; margin: auto; padding: 30px; border: 1px solid #e2e8f0; border-radius: 8px; }
        .header { display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 16px; margin-bottom: 24px; }
        .company-name { font-size: 20px; font-weight: bold; color: #0f172a; margin-bottom: 4px; }
        .doc-title { font-size: 22px; font-weight: 800; color: #0f172a; text-align: right; }
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
        @if(Route::has('wholesale.credit.show'))
            <a href="{{ route('wholesale.credit.show', $customer) }}" style="color: #64748b; text-decoration: none; font-size: 13px;">&larr; Back to Credit Details</a>
        @endif
        <button onclick="window.print()" style="background: #0f172a; color: #fff; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">Print / Save as PDF</button>
    </div>

    <div class="invoice-box">
        <div class="header">
            <div>
                <div class="company-name">RAYSUT MARINE SUPPLY & SERVICES CO. SAOG</div>
                <div style="font-size: 11px; color: #64748b;">Credit & Accounts Receivable Department</div>
                <div style="font-size: 11px; color: #64748b;">Company VAT Identification: {{ config('rmsc.company_vat_no', 'OM1100000001') }}</div>
            </div>
            <div>
                <div class="doc-title">CREDIT STATEMENT</div>
                <div class="doc-no">{{ $customer->customer_code }}</div>
                <div style="font-size: 11px; color: #64748b; margin-top: 4px;">Status: <strong>{{ $customer->credit_status ?? 'ACTIVE' }}</strong></div>
            </div>
        </div>

        <div class="grid">
            <div class="col">
                <div class="label">Customer Master Account</div>
                <div class="val">{{ $customer->customer_name }}</div>
                <div style="font-size: 12px; color: #475569;">Customer Code: {{ $customer->customer_code }}</div>
                <div style="font-size: 12px; color: #475569;">VAT Number: {{ $customer->vat_number ?? 'N/A' }}</div>
                <div style="font-size: 12px; color: #475569;">Phone: {{ $customer->phone ?? 'N/A' }}</div>
            </div>
            <div class="col" style="text-align: right;">
                <div class="label">Credit Terms & Limits</div>
                <div style="font-size: 12px; color: #475569;">Statement Date: {{ now()->format('d/m/Y') }}</div>
                <div style="font-size: 12px; color: #475569;">Credit Limit: <strong>{{ number_format((float) ($limit ?? $customer->getEffectiveCreditLimit()), 3) }} OMR</strong></div>
                <div style="font-size: 12px; color: #475569;">Payment Terms: {{ $customer->payment_terms_days ?? 30 }} Days</div>
                <div style="font-size: 12px; color: #475569;">Aging Status: {{ !empty($hasOverdue) ? 'OVERDUE' : 'CURRENT' }}</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width: 30px;">#</th>
                    <th>Invoice No</th>
                    <th>Invoice Date</th>
                    <th>Due Date</th>
                    <th class="text-right">Total (OMR)</th>
                    <th class="text-right">Balance Due (OMR)</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($invoices as $i => $inv)
                    <tr>
                        <td class="text-center" style="color: #94a3b8;">{{ $i + 1 }}</td>
                        <td style="font-family: monospace; font-weight: bold;">{{ $inv->invoice_no }}</td>
                        <td>{{ $inv->invoice_date ? $inv->invoice_date->format('d/m/Y') : 'N/A' }}</td>
                        <td>{{ $inv->due_date ? $inv->due_date->format('d/m/Y') : 'N/A' }}</td>
                        <td class="text-right" style="font-family: monospace;">{{ number_format((float) ($inv->grand_total ?? 0), 3) }}</td>
                        <td class="text-right" style="font-family: monospace; font-weight: bold; color: #991b1b;">{{ number_format((float) ($inv->balance_due ?? 0), 3) }}</td>
                        <td>{{ $inv->status }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center" style="padding: 20px; color: #94a3b8;">No open invoices for this customer.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="totals">
            <div class="totals-row">
                <span>Credit Limit:</span>
                <span style="font-family: monospace; font-weight: bold;">{{ number_format((float) ($limit ?? 0), 3) }} OMR</span>
            </div>
            <div class="totals-row">
                <span>Total Outstanding:</span>
                <span style="font-family: monospace; font-weight: bold; color: #991b1b;">{{ number_format((float) ($outstanding ?? 0), 3) }} OMR</span>
            </div>
            <div class="totals-row grand-total">
                <span>Available Credit:</span>
                <span style="font-family: monospace; color: #047857;">{{ number_format((float) ($available ?? 0), 3) }} OMR</span>
            </div>
        </div>

        <div class="signatures">
            <div class="sig-box">
                Credit Control Officer<br>
                Raysut Marine Supply & Services
            </div>
            <div class="sig-box">
                Finance Director<br>
                Raysut Marine Supply & Services
            </div>
        </div>
    </div>
</body>
</html>

