<x-app-layout>
    <style>
        :root, .pos-root { --pos-red:#ED1C24; --pos-red-dark:#C8151C; --pos-ink:#0F172A; --pos-muted:#64748B; --pos-line:#E2E8F0; --pos-soft:#F8FAFC; }

        @keyframes pos-spin { to { transform: rotate(360deg); } }
        .pos-spin { animation: pos-spin .8s linear infinite; }

        /* Layout */
        .pos-shell { display:grid; grid-template-columns:minmax(0,1fr) 410px; gap:16px; align-items:start; }
        @media (max-width:1023px){ .pos-shell { grid-template-columns:1fr; } }
        .pos-catalog { min-width:0; }
        .pos-ticket { position:sticky; top:12px; display:flex; flex-direction:column; height:calc(100vh - 130px); min-height:520px; background:#fff; border:1px solid var(--pos-line); border-radius:18px; box-shadow:0 1px 2px rgba(15,23,42,.05); overflow:hidden; }
        @media (max-width:1023px){ .pos-ticket { position:static; height:auto; min-height:0; } .pos-ticket-items { max-height:340px; } }

        /* Toolbar */
        .pos-toolbar { background:#fff; border:1px solid var(--pos-line); border-radius:18px; padding:12px; box-shadow:0 1px 2px rgba(15,23,42,.05); }
        .pos-scan { position:relative; }
        .pos-scan svg { position:absolute; left:14px; top:50%; transform:translateY(-50%); width:20px; height:20px; color:#94A3B8; pointer-events:none; }
        .pos-scan input { width:100%; height:48px; padding:0 44px 0 46px; border:1px solid var(--pos-line); border-radius:14px; background:var(--pos-soft); font-size:14px; }
        .pos-scan input:focus { background:#fff; outline:none; border-color:var(--pos-red); box-shadow:0 0 0 3px rgba(237,28,36,.12); }
        .pos-scan-clear { position:absolute; right:10px; top:50%; transform:translateY(-50%); width:28px; height:28px; border-radius:8px; color:#94A3B8; display:flex; align-items:center; justify-content:center; }
        .pos-scan-clear:hover { background:#E2E8F0; color:#334155; }
        .pos-chips { display:flex; gap:8px; overflow-x:auto; padding:10px 2px 2px; scrollbar-width:none; }
        .pos-chips::-webkit-scrollbar { display:none; }
        .pos-chip { flex:none; padding:7px 14px; border-radius:999px; font-size:12px; font-weight:600; background:#F1F5F9; color:#334155; border:1px solid transparent; cursor:pointer; white-space:nowrap; transition:all .15s; }
        .pos-chip:hover { background:#E2E8F0; }
        .pos-chip.is-active { background:var(--pos-ink); color:#fff; }

        /* Product grid */
        .pos-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(172px,1fr)); gap:12px; margin-top:12px; max-height:calc(100vh - 240px); overflow-y:auto; padding:2px 4px 8px 2px; }
        @media (max-width:1023px){ .pos-grid { max-height:none; } }
        .pos-card { position:relative; text-align:left; background:#fff; border:1px solid var(--pos-line); border-radius:16px; padding:12px; display:flex; flex-direction:column; justify-content:space-between; gap:12px; min-height:150px; cursor:pointer; transition:transform .08s, box-shadow .15s, border-color .15s; }
        .pos-card:hover { border-color:var(--pos-red); box-shadow:0 6px 16px rgba(15,23,42,.08); }
        .pos-card:active { transform:scale(.97); }
        .pos-card-top { display:flex; align-items:center; justify-content:space-between; gap:6px; }
        .pos-sku { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:10.5px; color:#94A3B8; font-weight:600; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pos-uom { font-size:10px; font-weight:700; color:#475569; background:#F1F5F9; border-radius:6px; padding:2px 6px; text-transform:uppercase; flex:none; }
        .pos-name { font-size:13.5px; font-weight:700; color:var(--pos-ink); line-height:1.3; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; margin-top:8px; }
        .pos-cat { font-size:11px; color:var(--pos-muted); margin-top:2px; }
        .pos-card-bottom { display:flex; align-items:flex-end; justify-content:space-between; gap:6px; padding-top:10px; border-top:1px dashed var(--pos-line); }
        .pos-price-label { font-size:10px; color:#94A3B8; text-transform:uppercase; letter-spacing:.04em; font-weight:600; }
        .pos-price { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:15px; font-weight:800; color:var(--pos-ink); }
        .pos-pill { display:inline-flex; align-items:center; gap:4px; border-radius:999px; padding:2px 8px; font-size:10px; font-weight:700; border:1px solid transparent; white-space:nowrap; }
        .pos-pill-vat { background:#FFFBEB; color:#92400E; border-color:#FDE68A; }
        .pos-pill-zero { background:#ECFDF5; color:#047857; border-color:#A7F3D0; }
        .pos-pill-permit { background:#F5F3FF; color:#6D28D9; border-color:#DDD6FE; }
        .pos-incart { position:absolute; top:-8px; right:-8px; min-width:26px; height:26px; padding:0 7px; border-radius:999px; background:var(--pos-red); color:#fff; font-size:12px; font-weight:800; display:flex; align-items:center; justify-content:center; box-shadow:0 2px 6px rgba(237,28,36,.4); }
        .pos-empty-grid { grid-column:1/-1; text-align:center; padding:48px 12px; color:#94A3B8; font-size:13px; }

        /* Ticket */
        .pos-ticket-head { display:flex; align-items:center; justify-content:space-between; padding:14px 16px; border-bottom:1px solid #F1F5F9; }
        .pos-ticket-title { font-size:14px; font-weight:800; color:var(--pos-ink); }
        .pos-ticket-sub { font-size:11px; color:#94A3B8; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
        .pos-ticket-items { flex:1; min-height:120px; overflow-y:auto; padding:10px 12px; display:flex; flex-direction:column; gap:8px; }
        .pos-line { display:grid; grid-template-columns:minmax(0,1fr) auto; gap:6px 10px; padding:10px; border:1px solid #F1F5F9; background:var(--pos-soft); border-radius:12px; }
        .pos-line-name { font-size:12.5px; font-weight:700; color:var(--pos-ink); overflow:hidden; text-overflow:ellipsis; white-space:nowrap; display:flex; align-items:center; gap:6px; }
        .pos-line-meta { font-size:11px; color:var(--pos-muted); grid-column:1; }
        .pos-line-total { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:12.5px; font-weight:800; color:var(--pos-ink); text-align:right; }
        .pos-line-actions { grid-column:1/-1; display:flex; align-items:center; justify-content:space-between; }
        .pos-stepper { display:inline-flex; align-items:center; background:#fff; border:1px solid var(--pos-line); border-radius:10px; overflow:hidden; }
        .pos-stepper button { width:34px; height:32px; font-size:16px; font-weight:700; color:#334155; cursor:pointer; }
        .pos-stepper button:hover { background:#F1F5F9; }
        .pos-stepper span { min-width:34px; text-align:center; font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-weight:800; font-size:13px; }
        .pos-remove { font-size:11px; font-weight:600; color:#E11D48; cursor:pointer; padding:4px 6px; border-radius:6px; }
        .pos-remove:hover { background:#FFF1F2; }
        .pos-ticket-empty { flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; color:#94A3B8; padding:24px; gap:6px; }
        .pos-ticket-empty strong { font-size:13px; color:#64748B; }
        .pos-ticket-empty span { font-size:11.5px; }

        /* Permit status row */
        .pos-permit { margin:0 12px 10px; border-radius:14px; padding:10px 12px; border:1px solid; display:flex; align-items:center; gap:10px; }
        .pos-permit.is-required { background:#FFFBEB; border-color:#FCD34D; }
        .pos-permit.is-verified { background:#ECFDF5; border-color:#6EE7B7; }
        .pos-permit.is-exceeded { background:#FFF1F2; border-color:#FDA4AF; }
        .pos-permit-dot { width:10px; height:10px; border-radius:999px; flex:none; }
        .is-required .pos-permit-dot { background:#F59E0B; } .is-verified .pos-permit-dot { background:#10B981; } .is-exceeded .pos-permit-dot { background:#F43F5E; }
        .pos-permit-body { flex:1; min-width:0; }
        .pos-permit-title { font-size:12px; font-weight:800; color:var(--pos-ink); }
        .pos-permit-text { font-size:11px; color:#475569; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
        .pos-permit-btn { flex:none; font-size:11.5px; font-weight:700; padding:6px 12px; border-radius:10px; background:var(--pos-ink); color:#fff; cursor:pointer; }
        .pos-permit-btn:hover { background:#1E293B; }

        /* Totals */
        .pos-totals { border-top:1px solid var(--pos-line); background:var(--pos-soft); padding:14px 16px 16px; }
        .pos-row { display:flex; justify-content:space-between; font-size:12px; color:#475569; padding:2px 0; }
        .pos-row b { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; color:var(--pos-ink); font-weight:700; }
        .pos-row.is-permit { color:#6D28D9; }
        .pos-grand { display:flex; justify-content:space-between; align-items:baseline; margin-top:8px; padding-top:10px; border-top:1px solid var(--pos-line); }
        .pos-grand span { font-size:13px; font-weight:800; color:var(--pos-ink); }
        .pos-grand b { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; font-size:24px; font-weight:800; color:var(--pos-red); }
        .pos-pay { width:100%; margin-top:12px; height:52px; border-radius:14px; background:var(--pos-red); color:#fff; font-weight:800; font-size:14px; display:flex; align-items:center; justify-content:center; gap:8px; cursor:pointer; transition:background .15s, transform .08s; }
        .pos-pay:hover:not(:disabled) { background:var(--pos-red-dark); }
        .pos-pay:active:not(:disabled) { transform:scale(.99); }
        .pos-pay:disabled { opacity:.4; cursor:not-allowed; }
        .pos-hint { margin-top:6px; text-align:center; font-size:11px; font-weight:600; color:#B45309; min-height:14px; }
        .pos-kbd { font-size:10px; font-weight:700; padding:1px 6px; border-radius:6px; background:rgba(255,255,255,.2); }

        /* Modals */
        .pos-overlay { position:fixed; inset:0; z-index:9999; background:rgba(15,23,42,.65); backdrop-filter:blur(4px); display:flex; align-items:center; justify-content:center; padding:16px; }
        .pos-modal { width:100%; background:#ffffff; border-radius:18px; border:1px solid #CBD5E1; box-shadow:0 25px 50px -12px rgba(15,23,42,.35); display:flex; flex-direction:column; max-height:calc(100vh - 40px); overflow:hidden; }
        .pos-modal-head { flex-shrink:0; padding:16px 22px; background:#F8FAFC; border-bottom:1px solid #E2E8F0; display:flex; align-items:center; justify-content:space-between; border-radius:18px 18px 0 0; }
        .pos-modal-body { flex:1 1 auto; min-height:0; padding:20px 22px; overflow-y:auto; display:flex; flex-direction:column; gap:14px; }
        .pos-modal-foot { flex-shrink:0; padding:16px 22px; border-top:1px solid #E2E8F0; background:#ffffff; display:flex; justify-content:flex-end; align-items:center; gap:12px; border-radius:0 0 18px 18px; }
        .pos-label { display:block; font-size:11px; font-weight:700; color:#334155; text-transform:uppercase; letter-spacing:.05em; margin-bottom:4px; }
        .pos-input { width:100%; height:38px; padding:0 12px; border:1px solid var(--pos-line); border-radius:12px; background:var(--pos-soft); font-size:13px; }
        .pos-input:focus { outline:none; background:#fff; border-color:var(--pos-red); box-shadow:0 0 0 3px rgba(237,28,36,.1); }
        .pos-input.is-warn { border-color:#FCD34D; background:#FFFBEB; }
        .pos-input.is-bad { border-color:#FB7185; background:#FFF1F2; color:#BE123C; }
        .pos-form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
        @media (max-width:560px){ .pos-form-grid { grid-template-columns:1fr; } }
        .pos-tabs { display:flex; gap:4px; background:#F1F5F9; padding:4px; border-radius:12px; }
        .pos-tab { flex:1; padding:8px 12px; border-radius:9px; font-size:12px; font-weight:700; color:#64748B; cursor:pointer; display:flex; align-items:center; justify-content:center; gap:6px; }
        .pos-tab.is-active { background:#fff; color:var(--pos-ink); box-shadow:0 1px 2px rgba(15,23,42,.1); }
        .pos-drop { position:relative; border:2px dashed #CBD5E1; border-radius:14px; padding:14px; background:var(--pos-soft); text-align:center; }
        .pos-drop:hover { border-color:#94A3B8; }
        .pos-drop input[type=file] { position:absolute; inset:0; width:100%; height:100%; opacity:0; cursor:pointer; }
        .pos-btn { height:40px; padding:0 20px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:1px solid #CBD5E1; background:#ffffff; color:#334155; display:inline-flex; align-items:center; justify-content:center; gap:8px; transition:all .15s ease; }
        .pos-btn:hover { background:#F8FAFC; }
        .pos-btn-primary { background:#ED1C24 !important; border-color:#ED1C24 !important; color:#ffffff !important; box-shadow:0 2px 6px rgba(237,28,36,.3); }
        .pos-btn-primary:hover:not(:disabled) { background:#C8151C !important; border-color:#C8151C !important; }
        .pos-btn-success { background:#059669 !important; border-color:#059669 !important; color:#ffffff !important; box-shadow:0 2px 6px rgba(5,150,105,.3); }
        .pos-btn-success:hover:not(:disabled) { background:#047857 !important; border-color:#047857 !important; }
        .pos-btn:disabled { opacity:.45 !important; cursor:not-allowed !important; }
        .pos-alert { padding:10px 12px; border-radius:12px; border:1px solid; font-size:12px; }
        .pos-alert-bad { background:#FFF1F2; border-color:#FDA4AF; color:#BE123C; }
        .pos-alert-ok { background:#ECFDF5; border-color:#A7F3D0; color:#065F46; }
        .pos-alert-warn { background:#FFFBEB; border-color:#FCD34D; color:#92400E; }
        .pos-method { display:grid; grid-template-columns:repeat(3,1fr); gap:8px; }
        .pos-method button { padding:11px; border-radius:12px; font-size:13px; font-weight:700; background:#F1F5F9; color:#334155; cursor:pointer; }
        .pos-method button.is-active { background:var(--pos-ink); color:#fff; }
        .pos-box { padding:12px; border:1px solid var(--pos-line); background:var(--pos-soft); border-radius:12px; display:flex; justify-content:space-between; align-items:center; font-size:12px; }
        .pos-mono { font-family:ui-monospace,SFMono-Regular,Menlo,monospace; }
    </style>

    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rmsc-red text-white flex items-center justify-center shadow-xs" style="background:#ED1C24;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 tracking-tight leading-tight">Retail POS Billing</h1>
                    <p class="text-xs text-slate-500">Dynamic Oman VAT &bull; Government permit verification</p>
                </div>
            </div>

            <!-- Shift status -->
            <div class="flex items-center flex-wrap gap-2 bg-white px-3 py-2 rounded-xl border border-slate-200/80 shadow-xs">
                @if($currentShift)
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                        <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        SHIFT #{{ $currentShift->shift_id }} OPEN
                    </span>
                    <span class="text-xs text-slate-600">Counter <strong class="text-slate-800 font-mono">{{ $currentShift->counter?->counter_name ?? $currentShift->counter?->counter_code }}</strong></span>
                    <span class="text-xs text-slate-600">Float <strong class="text-emerald-700 font-mono">OMR {{ number_format($currentShift->opening_float, 3) }}</strong></span>
                    <a href="{{ route('shifts.index') }}" class="px-2.5 py-1 text-xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 rounded-lg transition">Close Shift</a>
                @else
                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">NO OPEN SHIFT</span>
                    <a href="{{ route('shifts.index') }}" class="px-3 py-1 text-xs font-semibold text-white rounded-lg transition" style="background:#ED1C24;">+ Open Shift</a>
                @endif
            </div>
        </div>
    </x-slot>

    <div x-data="posBillingSystem({
        csrfToken: '{{ csrf_token() }}',
        checkoutUrl: '{{ route('pos.checkout') }}',
        extractUrl: '{{ route('pos.permit.extract') }}',
        permitLookupUrl: '{{ route('pos.permit-lookup') }}',
        minConfidence: {{ config('rmsc.permit_min_confidence', 0.8) }},
        blockWhenExceeds: {{ config('rmsc.permit_block_when_exceeds', true) ? 'true' : 'false' }},
        counterId: {{ $currentShift?->counter_id ?? 'null' }},
        products: {{ Js::from($products->map(function($p) {
            $retailPrice = $p->pricingTiers->where('price_tier', 'RETAIL')->first()?->unit_price 
                ?? $p->pricingTiers->first()?->unit_price 
                ?? 1.000;
            return [
                'id' => $p->product_id,
                'name' => $p->product_name,
                'sku' => $p->sku_code,
                'barcode' => $p->barcode ?? '',
                'category' => $p->category?->category_name ?? 'General',
                'uom_code' => $p->uom?->uom_code ?? 'PCS',
                'uom_name' => $p->uom?->uom_name ?? 'Pieces',
                'price' => (float) $retailPrice,
                'tax_rate_id' => $p->tax_rate_id,
                'tax_name' => $p->taxRate?->tax_name ?? 'Zero-Rated VAT',
                'tax_percent' => (float) ($p->taxRate?->rate_percent ?? 0.0),
                'requires_permit' => $p->requiresPermit(),
            ];
        })) }}
    })"
         @keydown.f4.window.prevent="openPaymentModal()"
         @keydown.escape.window="showPaymentModal = false; showPermitModal = false"
         class="pos-root">
        <!-- Top Terminal Bar -->
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 bg-white p-4 rounded-2xl border border-slate-200/80 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-rmsc-red/10 flex items-center justify-center text-rmsc-red">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                </div>
                <div>
                    <h1 class="text-base font-extrabold text-slate-900 tracking-tight">Retail POS Billing Terminal</h1>
                    <div class="flex items-center gap-2 text-xs text-slate-500 font-mono mt-0.5">
                        @if($currentShift)
                            <span class="inline-flex items-center gap-1 font-bold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-md border border-emerald-200">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                SHIFT #{{ $currentShift->shift_id }} OPEN
                            </span>
                            <span>&bull;</span>
                            <span class="font-bold text-slate-700">{{ $currentShift->counter?->counter_code ?? 'POS-01' }}</span>
                            <span>&bull;</span>
                            <span>{{ $currentShift->counter?->counter_name }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3 text-xs">
                <span class="text-slate-500 font-medium">Master Data:</span>
                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 font-semibold border border-slate-200">
                    Calculated Oman VAT
                </span>
                <a href="{{ route('shifts.index') }}" class="px-3 py-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold rounded-xl text-xs transition">
                    Shift Control
                </a>
            </div>
        </div>

        <div class="pos-shell">

            <!-- LEFT: Catalog -->
            <section class="pos-catalog">
                <div class="pos-toolbar">
                    <div class="pos-scan">
                        <svg fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/></svg>
                        <input type="text"
                               x-ref="barcodeInput"
                               x-model="barcodeScanText"
                               @keydown.enter.prevent="handleBarcodeScan()"
                               placeholder="Scan barcode, or type SKU / product name and press Enter"
                               class="pos-mono"
                               autofocus>
                        <button type="button" x-show="barcodeScanText || searchQuery" @click="barcodeScanText = ''; searchQuery = ''; $refs.barcodeInput.focus()" class="pos-scan-clear" title="Clear">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="pos-chips">
                        <button type="button" @click="selectedCategory = 'ALL'" :class="selectedCategory === 'ALL' ? 'is-active' : ''" class="pos-chip">All Products</button>
                        <template x-for="cat in categories" :key="cat">
                            <button type="button" @click="selectedCategory = cat" :class="selectedCategory === cat ? 'is-active' : ''" class="pos-chip" x-text="cat"></button>
                        </template>
                    </div>
                </div>

                <div class="pos-grid">
                    <template x-for="product in filteredProducts" :key="product.id">
                        <button type="button" @click="addToCart(product)" class="pos-card">
                            <template x-if="cart.find(i => i.id === product.id)">
                                <span class="pos-incart" x-text="cart.find(i => i.id === product.id).qty"></span>
                            </template>
                            <div>
                                <div class="pos-card-top">
                                    <span class="pos-sku" x-text="product.sku"></span>
                                    <span class="pos-uom" x-text="product.uom_code"></span>
                                </div>
                                <div class="pos-name" x-text="product.name"></div>
                                <div class="pos-cat" x-text="product.category"></div>
                            </div>
                            <div class="pos-card-bottom">
                                <div>
                                    <div class="pos-price-label">Price</div>
                                    <div class="pos-price" x-text="'OMR ' + product.price.toFixed(3)"></div>
                                </div>
                                <div style="display:flex; flex-direction:column; align-items:flex-end; gap:4px;">
                                    <template x-if="product.requires_permit">
                                        <span class="pos-pill pos-pill-permit">PERMIT</span>
                                    </template>
                                    <span class="pos-pill" :class="product.tax_percent > 0 ? 'pos-pill-vat' : 'pos-pill-zero'" x-text="product.tax_percent + '% VAT'"></span>
                                </div>
                            </div>
                        </button>
                    </template>

                    <template x-if="filteredProducts.length === 0">
                        <div class="pos-empty-grid">No products match your search.</div>
                    </template>
                </div>
            </section>

            <!-- RIGHT: Ticket -->
            <aside class="pos-ticket">
                <div class="pos-ticket-head">
                    <div>
                        <div class="pos-ticket-title">Current Sale</div>
                        <div class="pos-ticket-sub" x-text="cart.length + ' item(s)'"></div>
                    </div>
                    <button type="button" @click="clearCart()" x-show="cart.length > 0" class="pos-remove">Clear all</button>
                </div>

                <div class="pos-ticket-items">
                    <template x-if="cart.length === 0">
                        <div class="pos-ticket-empty">
                            <svg style="width:40px;height:40px;color:#CBD5E1;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
                            <strong>Cart is empty</strong>
                            <span>Scan a barcode or tap a product to add it</span>
                        </div>
                    </template>

                    <template x-for="(item, index) in cart" :key="item.id">
                        <div class="pos-line">
                            <div style="min-width:0;">
                                <div class="pos-line-name">
                                    <span style="overflow:hidden;text-overflow:ellipsis;" x-text="item.name"></span>
                                    <template x-if="item.requires_permit"><span class="pos-pill pos-pill-permit">P</span></template>
                                </div>
                                <div class="pos-line-meta"><span class="pos-mono" x-text="'OMR ' + item.price.toFixed(3)"></span> &bull; <span x-text="item.tax_percent + '% VAT'"></span></div>
                            </div>
                            <div class="pos-line-total" x-text="'OMR ' + (item.price * item.qty * (1 + item.tax_percent / 100)).toFixed(3)"></div>
                            <div class="pos-line-actions">
                                <div class="pos-stepper">
                                    <button type="button" @click="decrementQty(index)">&minus;</button>
                                    <span x-text="item.qty"></span>
                                    <button type="button" @click="incrementQty(index)">+</button>
                                </div>
                                <button type="button" @click="removeItem(index)" class="pos-remove">Remove</button>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Permit status row -->
                <div x-show="permitState !== 'NONE'" x-cloak class="pos-permit"
                     :class="{'is-required': permitState === 'REQUIRED', 'is-verified': permitState === 'VERIFIED', 'is-exceeded': permitState === 'EXCEEDED' || permitState === 'EXPIRED'}">
                    <span class="pos-permit-dot"></span>
                    <div class="pos-permit-body">
                        <div class="pos-permit-title" x-text="permitState === 'VERIFIED' ? 'Permit verified' : (permitState === 'EXCEEDED' ? 'Exceeds remaining quota' : (permitState === 'EXPIRED' ? 'Permit is expired or invalid' : 'Permit verification required'))"></div>
                        <div class="pos-permit-text">
                            <template x-if="permitState === 'REQUIRED'"><span>Check the customer on the Oman government site, then enter or upload the result.</span></template>
                            <template x-if="permitState === 'EXPIRED'"><span style="color:#BE123C;font-weight:600;">The permit is expired or suspended. Verification cannot be accepted.</span></template>
                            <template x-if="permitState !== 'REQUIRED' && permitState !== 'EXPIRED'"><span x-text="(permitForm.holder_name || '-') + ' • ' + maskedCivil + ' • Remaining OMR ' + Number(permitForm.remaining_amount || 0).toFixed(3)"></span></template>
                        </div>
                    </div>
                    <button type="button" class="pos-permit-btn" @click="showPermitModal = true" x-text="permitState === 'REQUIRED' ? 'Verify' : 'Edit'"></button>
                </div>

                <!-- Totals + pay -->
                <div class="pos-totals">
                    <div class="pos-row"><span>Subtotal (excl. VAT)</span><b x-text="'OMR ' + subtotal.toFixed(3)"></b></div>
                    <div class="pos-row"><span>VAT</span><b x-text="'OMR ' + totalVat.toFixed(3)"></b></div>
                    <div class="pos-row is-permit" x-show="permitControlledTotal > 0"><span>Permit-controlled portion</span><b style="color:#6D28D9;" x-text="'OMR ' + permitControlledTotal.toFixed(3)"></b></div>
                    <div class="pos-grand"><span>Grand Total</span><b x-text="'OMR ' + grandTotal.toFixed(3)"></b></div>

                    <button type="button" @click="openPaymentModal()" :disabled="!canProceedToPayment" class="pos-pay">
                        <svg style="width:18px;height:18px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        <span>Proceed to Payment</span><span class="pos-kbd">F4</span>
                    </button>
                    <div class="pos-hint" x-text="permitState === 'REQUIRED' ? 'Verify the permit to continue' : (permitState === 'EXCEEDED' ? 'Order exceeds the remaining permit quota' : (permitState === 'EXPIRED' ? 'Permit is expired or invalid. Checkout blocked.' : ''))"></div>
                </div>
            </aside>
        </div>

        <!-- Permit Verification Modal -->
        <template x-teleport="body">
            <div x-show="showPermitModal" x-cloak class="pos-overlay" style="display:none;">
                <div @click.away="showPermitModal = false" class="pos-modal" style="max-width:640px;">
                    <div class="pos-modal-head">
                        <div>
                            <h3 style="font-size:15px;font-weight:800;color:#0F172A;">Oman Government Permit Verification</h3>
                            <p style="font-size:11px;color:#64748B;margin-top:2px;">Verify the customer on the government website first, then record the result here.</p>
                        </div>
                        <button type="button" @click="showPermitModal = false" class="pos-scan-clear" style="position:static;transform:none;" title="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="pos-modal-body">
                        <!-- Tabs -->
                        <div class="pos-tabs">
                            <button type="button" @click="setPermitTab('MANUAL')" :class="permitTab === 'MANUAL' ? 'is-active' : ''" class="pos-tab">Enter Manually</button>
                            <button type="button" @click="setPermitTab('SCREENSHOT')" :class="permitTab === 'SCREENSHOT' ? 'is-active' : ''" class="pos-tab">
                                <svg style="width:14px;height:14px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                Upload Screenshot
                            </button>
                        </div>

                        <!-- Screenshot upload (auto-extracts on select/drop) -->
                        <div x-show="permitTab === 'SCREENSHOT'" style="display:flex;flex-direction:column;gap:10px;">
                            <div class="pos-drop" @dragover.prevent="" @drop.prevent="handleScreenshotDrop($event)">
                                <input type="file" x-ref="screenshotInput" @change="handleScreenshotSelect($event)" accept="image/jpeg,image/png,image/webp" :disabled="isExtracting" x-show="!screenshotPreviewUrl">

                                <template x-if="!screenshotPreviewUrl">
                                    <div style="padding:10px 0;">
                                        <svg style="width:34px;height:34px;color:#94A3B8;margin:0 auto 4px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        <p style="font-size:12.5px;font-weight:700;color:#334155;">Drop the government page screenshot here, or <span style="color:#ED1C24;">browse</span></p>
                                        <p style="font-size:11px;color:#94A3B8;margin-top:2px;">JPEG, PNG or WebP, up to 5 MB. Details are read automatically.</p>
                                    </div>
                                </template>

                                <template x-if="screenshotPreviewUrl">
                                    <div style="display:flex;align-items:center;gap:12px;">
                                        <img :src="screenshotPreviewUrl" style="width:64px;height:64px;object-fit:cover;border-radius:10px;border:1px solid #E2E8F0;">
                                        <div style="flex:1;min-width:0;text-align:left;">
                                            <p style="font-size:12.5px;font-weight:700;color:#0F172A;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" x-text="screenshotFileName"></p>
                                            <p style="font-size:11px;color:#94A3B8;" x-text="screenshotFileSize"></p>
                                            <button type="button" @click.stop="clearScreenshot()" class="pos-remove" style="padding-left:0;">Remove</button>
                                        </div>

                                        <!-- Auto-extraction status -->
                                        <div style="flex:none;display:flex;align-items:center;gap:8px;">
                                            <template x-if="isExtracting">
                                                <span style="display:inline-flex;align-items:center;gap:8px;font-size:12px;font-weight:700;color:#334155;">
                                                    <svg class="pos-spin" style="width:18px;height:18px;color:#ED1C24;" fill="none" viewBox="0 0 24 24"><circle style="opacity:.25;" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.85;" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path></svg>
                                                    Reading screenshot...
                                                </span>
                                            </template>
                                            <template x-if="!isExtracting && extractionDone">
                                                <span style="display:inline-flex;align-items:center;gap:6px;font-size:12px;font-weight:700;color:#047857;">
                                                    <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                    Details extracted
                                                </span>
                                            </template>
                                            <template x-if="!isExtracting && !extractionDone && extractionError">
                                                <button type="button" @click.stop="extractScreenshot()" class="pos-btn" style="height:34px;padding:0 14px;font-size:12px;">Retry</button>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                            </div>

                            <div x-show="extractionError" class="pos-alert pos-alert-bad">
                                <span x-text="extractionError"></span>
                                <div style="margin-top:2px;font-size:11px;color:#475569;">You can retry, upload a clearer screenshot, or enter the details manually.</div>
                            </div>

                            <template x-if="extractionConfidence !== null && !isExtracting">
                                <div class="pos-alert" :class="extractionConfidence >= minConfidence ? 'pos-alert-ok' : 'pos-alert-warn'" style="display:flex;justify-content:space-between;gap:8px;">
                                    <span>Extraction confidence: <strong class="pos-mono" x-text="Math.round(extractionConfidence * 100) + '%'"></strong></span>
                                    <span style="font-size:11px;" x-text="extractionConfidence >= minConfidence ? 'High accuracy. Please still confirm the values.' : 'Review highlighted fields carefully'"></span>
                                </div>
                            </template>

                            <template x-if="extractionWarnings.length > 0 && !isExtracting">
                                <div class="pos-alert" :class="isPermitExpired ? 'pos-alert-bad' : 'pos-alert-warn'">
                                    <template x-for="(w, i) in extractionWarnings" :key="i">
                                        <div x-text="w"></div>
                                    </template>
                                </div>
                            </template>
                        </div>

                        <!-- Review / manual form -->
                        <div class="pos-form-grid">
                            <div>
                                <label class="pos-label">Civil ID *</label>
                                <input type="text" x-model="permitForm.civil_id" @input="markFieldEdited('civil_id')" @keydown.enter.prevent="checkShiftReuse()" placeholder="6-12 digits" class="pos-input pos-mono" :class="((confidences.civil_id && confidences.civil_id < minConfidence) || (extractionDone && !permitForm.civil_id)) ? 'is-warn' : ''">
                            </div>
                            <div>
                                <label class="pos-label">Holder Name *</label>
                                <input type="text" x-model="permitForm.holder_name" @input="markFieldEdited('holder_name')" placeholder="Full name" class="pos-input" :class="((confidences.holder_name && confidences.holder_name < minConfidence) || (extractionDone && !permitForm.holder_name)) ? 'is-warn' : ''">
                            </div>
                            <div>
                                <label class="pos-label">Remaining Quota (OMR) *</label>
                                <input type="text" inputmode="decimal" x-model="permitForm.remaining_amount" @blur="formatAmountField('remaining_amount')" @input="markFieldEdited('remaining_amount')" placeholder="0.000" class="pos-input pos-mono" :class="isQuotaExceeded ? 'is-bad' : (((confidences.remaining_amount && confidences.remaining_amount < minConfidence) || (extractionDone && (permitForm.remaining_amount === '' || permitForm.remaining_amount === null))) ? 'is-warn' : '')">
                            </div>
                            <div>
                                <label class="pos-label">Permit No (optional)</label>
                                <input type="text" x-model="permitForm.permit_no" @input="markFieldEdited('permit_no')" placeholder="Permit number" class="pos-input pos-mono">
                            </div>
                            <div>
                                <label class="pos-label">Valid From</label>
                                <input type="date" x-model="permitForm.valid_from" @input="markFieldEdited('valid_from')" class="pos-input" :class="isPermitDateInvalid ? 'is-bad' : ''">
                            </div>
                            <div>
                                <label class="pos-label">Valid To</label>
                                <input type="date" x-model="permitForm.valid_to" @input="markFieldEdited('valid_to')" class="pos-input" :class="(isPermitExpired || isPermitDateInvalid) ? 'is-bad' : ''">
                            </div>
                        </div>

                        <!-- Live quota check -->
                        <div class="pos-alert" :class="(isQuotaExceeded || isPermitExpired || isPermitDateInvalid) ? 'pos-alert-bad' : ''" :style="(isQuotaExceeded || isPermitExpired || isPermitDateInvalid) ? '' : 'background:#F8FAFC;border-color:#E2E8F0;color:#334155;'">
                            <div class="pos-mono" style="display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap;">
                                <span>Controlled lines: <strong x-text="'OMR ' + permitControlledTotal.toFixed(3)"></strong></span>
                                <span>Remaining verified: <strong x-text="'OMR ' + Number(permitForm.remaining_amount || 0).toFixed(3)"></strong></span>
                            </div>
                            <div x-show="isQuotaExceeded" style="margin-top:4px;font-weight:700;font-size:11.5px;color:#BE123C;">ORDER EXCEEDS REMAINING QUOTA. Checkout is blocked.</div>
                            <div x-show="isPermitExpired" style="margin-top:4px;font-weight:700;font-size:11.5px;color:#BE123C;">PERMIT IS EXPIRED OR SUSPENDED. Verification and checkout are blocked.</div>
                            <div x-show="!isPermitExpired && isPermitDateInvalid" style="margin-top:4px;font-weight:700;font-size:11.5px;color:#BE123C;">PERMIT VALIDITY DATES ARE INVALID. Verification and checkout are blocked.</div>
                        </div>

                        <!-- Attestation -->
                        <label class="pos-alert" style="background:#F5F3FF;border-color:#DDD6FE;color:#4C1D95;display:flex;gap:10px;align-items:flex-start;cursor:pointer;" :style="(isPermitExpired || isPermitDateInvalid) ? 'opacity:0.5;cursor:not-allowed;' : ''">
                            <input type="checkbox" x-model="permitForm.attested" :disabled="isPermitExpired || isPermitDateInvalid" style="margin-top:2px;">
                            <span>
                                <strong style="font-size:12.5px;">I have verified these details on the Oman government website. *</strong>
                                <span style="display:block;font-size:11px;color:#6D28D9;margin-top:2px;">Cashier attestation is logged permanently in the audit trail.</span>
                            </span>
                        </label>
                    </div>

                    <div class="pos-modal-foot" style="flex-shrink:0; padding:16px 22px; border-top:1px solid #E2E8F0; background:#ffffff; display:flex; justify-content:flex-end; align-items:center; gap:12px; border-radius:0 0 18px 18px;">
                        <button type="button" 
                                @click="showPermitModal = false" 
                                class="pos-btn" 
                                style="height:42px; padding:0 22px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:1px solid #CBD5E1; background:#ffffff; color:#334155;">
                            Cancel
                        </button>
                        <button type="button" 
                                @click="savePermitVerification()" 
                                :disabled="!isPermitVerifiedAndValid || isExtracting" 
                                class="pos-btn pos-btn-primary" 
                                style="height:42px; padding:0 24px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:1px solid #ED1C24; background-color:#ED1C24; color:#ffffff; display:inline-flex; align-items:center; gap:8px; box-shadow:0 2px 8px rgba(237,28,36,0.35);" 
                                :style="(!isPermitVerifiedAndValid || isExtracting) ? 'opacity:0.45; cursor:not-allowed;' : 'opacity:1; cursor:pointer;'">
                            <svg style="width:16px;height:16px;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                            <span>Save Verification</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>

        <!-- Payment Modal -->
        <template x-teleport="body">
            <div x-show="showPaymentModal" x-cloak class="pos-overlay" style="display:none;">
                <div @click.away="showPaymentModal = false" class="pos-modal" style="max-width:480px;">
                    <div class="pos-modal-head">
                        <div>
                            <h3 style="font-size:15px;font-weight:800;color:#0F172A;">Complete Payment</h3>
                            <p style="font-size:11px;color:#64748B;margin-top:2px;">Total payable <strong class="pos-mono" style="color:#0F172A;" x-text="'OMR ' + grandTotal.toFixed(3)"></strong></p>
                        </div>
                        <button type="button" @click="showPaymentModal = false" class="pos-scan-clear" style="position:static;transform:none;" title="Close">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="pos-modal-body">
                        <div x-show="checkoutError" class="pos-alert pos-alert-bad" x-text="checkoutError"></div>

                        <div class="pos-method">
                            <button type="button" @click="paymentMethod = 'CASH'" :class="paymentMethod === 'CASH' ? 'is-active' : ''">Cash</button>
                            <button type="button" @click="paymentMethod = 'CARD'" :class="paymentMethod === 'CARD' ? 'is-active' : ''">Card</button>
                            <button type="button" @click="paymentMethod = 'SPLIT'" :class="paymentMethod === 'SPLIT' ? 'is-active' : ''">Split</button>
                        </div>

                        <!-- Cash -->
                        <div x-show="paymentMethod === 'CASH'" style="display:flex;flex-direction:column;gap:12px;">
                            <div>
                                <label class="pos-label">Cash Tendered (OMR) *</label>
                                <input type="number" step="0.001" x-model.number="cashTendered" class="pos-input pos-mono" style="height:46px;font-size:16px;font-weight:800;">
                            </div>
                            <div class="pos-box">
                                <span style="color:#64748B;font-weight:600;">Change due</span>
                                <strong class="pos-mono" style="font-size:16px;" :style="changeDue >= 0 ? 'color:#047857;' : 'color:#BE123C;'" x-text="'OMR ' + Math.max(0, changeDue).toFixed(3)"></strong>
                            </div>
                        </div>

                        <!-- Card -->
                        <div x-show="paymentMethod === 'CARD'" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="pos-box">
                                <span style="color:#64748B;font-weight:600;">Amount to charge</span>
                                <strong class="pos-mono" style="font-size:16px;color:#0F172A;" x-text="'OMR ' + grandTotal.toFixed(3)"></strong>
                            </div>
                            <div>
                                <label class="pos-label">Card Approval Reference</label>
                                <input type="text" x-model="cardRefInput" placeholder="e.g. TXN-109283" class="pos-input pos-mono">
                            </div>
                        </div>

                        <!-- Split -->
                        <div x-show="paymentMethod === 'SPLIT'" style="display:flex;flex-direction:column;gap:12px;">
                            <div class="pos-form-grid">
                                <div>
                                    <label class="pos-label">Cash (OMR) *</label>
                                    <input type="number" step="0.001" x-model.number="cashTendered" class="pos-input pos-mono" style="font-weight:800;">
                                </div>
                                <div>
                                    <label class="pos-label">Card (OMR) *</label>
                                    <input type="number" step="0.001" x-model.number="cardTendered" class="pos-input pos-mono" style="font-weight:800;">
                                </div>
                            </div>
                            <div>
                                <label class="pos-label">Card Approval Reference</label>
                                <input type="text" x-model="cardRefInput" placeholder="e.g. TXN-109283" class="pos-input pos-mono">
                            </div>
                            <div class="pos-box">
                                <span style="color:#64748B;font-weight:600;">Split total paid</span>
                                <strong class="pos-mono" style="font-size:15px;" :style="(cashTendered + cardTendered) >= grandTotal ? 'color:#047857;' : 'color:#BE123C;'" x-text="'OMR ' + (cashTendered + cardTendered).toFixed(3)"></strong>
                            </div>
                        </div>
                    </div>

                    <div class="pos-modal-foot" style="flex-shrink:0; padding:16px 22px; border-top:1px solid #E2E8F0; background:#ffffff; display:flex; justify-content:flex-end; align-items:center; gap:12px; border-radius:0 0 18px 18px;">
                        <button type="button" 
                                @click="showPaymentModal = false" 
                                class="pos-btn" 
                                style="height:42px; padding:0 22px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:1px solid #CBD5E1; background:#ffffff; color:#334155;">
                            Cancel
                        </button>
                        <button type="button" 
                                @click="executeCheckout()" 
                                :disabled="!isTenderValid || isSubmittingCheckout" 
                                class="pos-btn pos-btn-success" 
                                style="height:42px; padding:0 24px; border-radius:12px; font-size:13px; font-weight:700; cursor:pointer; border:1px solid #059669; background-color:#059669; color:#ffffff; display:inline-flex; align-items:center; gap:8px; box-shadow:0 2px 8px rgba(5,150,105,0.35);" 
                                :style="(!isTenderValid || isSubmittingCheckout) ? 'opacity:0.45; cursor:not-allowed;' : 'opacity:1; cursor:pointer;'">
                            <span x-text="isSubmittingCheckout ? 'Finalizing sale...' : 'Confirm & Print Receipt'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <!-- JavaScript POS Logic -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('posBillingSystem', (config) => ({
                csrfToken: config.csrfToken,
                checkoutUrl: config.checkoutUrl,
                extractUrl: config.extractUrl,
                permitLookupUrl: config.permitLookupUrl,
                minConfidence: config.minConfidence || 0.8,
                blockWhenExceeds: config.blockWhenExceeds,
                counterId: config.counterId,
                allProducts: config.products,

                // Filter & Search
                selectedCategory: 'ALL',
                searchQuery: '',
                barcodeScanText: '',

                // Cart
                cart: [],

                // Permit Verification State
                showPermitModal: false,
                permitTab: 'MANUAL', // 'MANUAL' | 'SCREENSHOT'
                isExtracting: false,
                extractionDone: false,
                extractionError: null,
                extractionWarnings: [],
                extractionAbort: null,
                screenshotFile: null,
                screenshotFileName: '',
                screenshotFileSize: '',
                screenshotPreviewUrl: null,
                screenshotToken: null,
                extractionConfidence: null,
                confidences: {},
                rawSnippet: null,
                initialExtracted: {},
                fieldsEdited: [],

                permitForm: {
                    civil_id: '',
                    holder_name: '',
                    permit_no: '',
                    status: '',
                    annual_limit: '',
                    used_amount: '',
                    remaining_amount: '',
                    valid_from: '',
                    valid_to: '',
                    verified_at: new Date().toISOString().slice(0, 19).replace('T', ' '),
                    attested: false,
                    entry_method: 'MANUAL',
                },

                // Payment Modal
                showPaymentModal: false,
                paymentMethod: 'CASH',
                cashTendered: 0,
                cardTendered: 0,
                cardRefInput: '',
                isSubmittingCheckout: false,
                checkoutError: null,

                init() {
                    this.$nextTick(() => { if (this.$refs.barcodeInput) this.$refs.barcodeInput.focus(); });
                },

                get categories() {
                    const set = new Set();
                    this.allProducts.forEach(p => {
                        if (p.category) set.add(p.category);
                    });
                    return Array.from(set);
                },

                get filteredProducts() {
                    return this.allProducts.filter(p => {
                        const matchesCat = this.selectedCategory === 'ALL' || p.category === this.selectedCategory;
                        const q = this.searchQuery.toLowerCase().trim();
                        const matchesQuery = !q || p.name.toLowerCase().includes(q) || p.sku.toLowerCase().includes(q) || (p.barcode && p.barcode.toLowerCase().includes(q));
                        return matchesCat && matchesQuery;
                    });
                },

                handleBarcodeScan() {
                    const scan = this.barcodeScanText.trim().toLowerCase();
                    if (!scan) return;

                    const match = this.allProducts.find(p => (p.barcode && p.barcode.toLowerCase() === scan) || p.sku.toLowerCase() === scan);
                    if (match) {
                        this.addToCart(match);
                        this.barcodeScanText = '';
                        this.searchQuery = '';
                    } else {
                        this.searchQuery = this.barcodeScanText;
                    }
                    this.$refs.barcodeInput.focus();
                },

                addToCart(product) {
                    const existing = this.cart.find(item => item.id === product.id);
                    if (existing) {
                        existing.qty += 1;
                    } else {
                        this.cart.push({
                            id: product.id,
                            name: product.name,
                            sku: product.sku,
                            price: product.price,
                            uom_code: product.uom_code,
                            tax_rate_id: product.tax_rate_id,
                            tax_name: product.tax_name,
                            tax_percent: product.tax_percent,
                            requires_permit: product.requires_permit,
                            qty: 1
                        });
                    }
                },

                incrementQty(index) {
                    this.cart[index].qty += 1;
                },

                decrementQty(index) {
                    if (this.cart[index].qty > 1) {
                        this.cart[index].qty -= 1;
                    } else {
                        this.cart.splice(index, 1);
                    }
                },

                removeItem(index) {
                    this.cart.splice(index, 1);
                },

                clearCart() {
                    this.cart = [];
                },

                get subtotal() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty), 0);
                },

                get totalVat() {
                    return this.cart.reduce((sum, item) => sum + (item.price * item.qty * (item.tax_percent / 100)), 0);
                },

                get grandTotal() {
                    return this.subtotal + this.totalVat;
                },

                get permitControlledTotal() {
                    return this.cart.reduce((sum, item) => {
                        if (item.requires_permit) {
                            const lineSub = item.price * item.qty;
                            const lineVat = lineSub * (item.tax_percent / 100);
                            return sum + (lineSub + lineVat);
                        }
                        return sum;
                    }, 0);
                },

                get isQuotaExceeded() {
                    if (this.permitControlledTotal <= 0.001) return false;
                    if (!this.blockWhenExceeds) return false;
                    const rem = Number(this.permitForm.remaining_amount || 0);
                    return rem < (this.permitControlledTotal - 0.001);
                },

                get isPermitExpired() {
                    const status = String(this.permitForm.status || '').toUpperCase().trim();
                    if (status === 'EXPIRED' || status === 'SUSPENDED') {
                        return true;
                    }
                    if (this.extractionWarnings && this.extractionWarnings.some(w => /EXPIRED|SUSPENDED|has expired/i.test(String(w)))) {
                        if (status !== 'ACTIVE') {
                            return true;
                        }
                    }
                    if (this.permitForm.valid_to) {
                        const d = new Date();
                        const todayStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                        if (this.permitForm.valid_to < todayStr) {
                            return true;
                        }
                    }
                    return false;
                },

                get isPermitDateInvalid() {
                    if (this.permitForm.valid_from && this.permitForm.valid_to) {
                        if (this.permitForm.valid_to < this.permitForm.valid_from) {
                            return true;
                        }
                    }
                    if (this.permitForm.valid_from) {
                        const d = new Date();
                        const todayStr = `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
                        if (this.permitForm.valid_from > todayStr) {
                            return true; // Not yet effective
                        }
                    }
                    return false;
                },

                get isPermitVerifiedAndValid() {
                    if (this.permitControlledTotal <= 0) return true;
                    const cid = (this.permitForm.civil_id || '').trim();
                    const name = (this.permitForm.holder_name || '').trim();
                    const rem = this.permitForm.remaining_amount;
                    if (!cid || !name || rem === '' || rem === null || rem === undefined) return false;
                    if (!this.permitForm.attested) return false;
                    if (this.isQuotaExceeded) return false;
                    if (this.isPermitExpired) return false;
                    if (this.isPermitDateInvalid) return false;
                    return true;
                },

                get permitState() {
                    if (this.permitControlledTotal <= 0) return 'NONE';
                    if (this.isQuotaExceeded) return 'EXCEEDED';
                    if (this.isPermitExpired) return 'EXPIRED';
                    return this.isPermitVerifiedAndValid ? 'VERIFIED' : 'REQUIRED';
                },

                get maskedCivil() {
                    const c = (this.permitForm.civil_id || '').trim();
                    return c ? '••••' + c.slice(-4) : '';
                },

                get canProceedToPayment() {
                    if (this.cart.length === 0) return false;
                    if (this.permitControlledTotal > 0 && !this.isPermitVerifiedAndValid) return false;
                    return true;
                },

                // The tab is only a view. The entry method is decided by whether extracted data is attached.
                setPermitTab(tab) {
                    this.permitTab = tab;
                },

                markFieldEdited(fieldName) {
                    if (this.screenshotToken && this.initialExtracted[fieldName] !== undefined) {
                        const current = String(this.permitForm[fieldName] ?? '').trim();
                        const initial = String(this.initialExtracted[fieldName] ?? '').trim();
                        if (current !== initial && !this.fieldsEdited.includes(fieldName)) {
                            this.fieldsEdited.push(fieldName);
                        }
                    }
                },

                handleScreenshotDrop(e) {
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        this.processScreenshotFile(files[0]);
                    }
                },

                handleScreenshotSelect(e) {
                    const files = e.target.files;
                    if (files.length > 0) {
                        this.processScreenshotFile(files[0]);
                    }
                },

                // Validates the file, shows the preview and extracts immediately.
                processScreenshotFile(file) {
                    const allowed = ['image/jpeg', 'image/png', 'image/webp'];
                    if (!allowed.includes(file.type)) {
                        this.extractionError = 'Unsupported file type. Please use a JPEG, PNG or WebP image.';
                        return;
                    }
                    if (file.size > 5 * 1024 * 1024) {
                        this.extractionError = 'The image is larger than 5 MB. Please upload a smaller screenshot.';
                        return;
                    }

                    this.resetExtractionState();
                    this.screenshotFile = file;
                    this.screenshotFileName = file.name;
                    this.screenshotFileSize = (file.size / 1024).toFixed(1) + ' KB';
                    this.screenshotPreviewUrl = URL.createObjectURL(file);

                    this.extractScreenshot();
                },

                // Cancels any running request and clears extraction results (not the typed form values).
                resetExtractionState() {
                    if (this.extractionAbort) {
                        this.extractionAbort.abort();
                        this.extractionAbort = null;
                    }
                    if (this.screenshotPreviewUrl) {
                        URL.revokeObjectURL(this.screenshotPreviewUrl);
                    }
                    this.screenshotFile = null;
                    this.screenshotFileName = '';
                    this.screenshotFileSize = '';
                    this.screenshotPreviewUrl = null;
                    this.screenshotToken = null;
                    this.isExtracting = false;
                    this.extractionDone = false;
                    this.extractionError = null;
                    this.extractionWarnings = [];
                    this.extractionConfidence = null;
                    this.confidences = {};
                    this.rawSnippet = null;
                    this.initialExtracted = {};
                    this.fieldsEdited = [];
                },

                clearScreenshot() {
                    const hadExtracted = this.extractionDone;
                    this.resetExtractionState();
                    if (this.$refs.screenshotInput) {
                        this.$refs.screenshotInput.value = '';
                    }
                    // Values that came from the removed screenshot are cleared so no orphan data is left behind.
                    if (hadExtracted) {
                        this.permitForm.civil_id = '';
                        this.permitForm.holder_name = '';
                        this.permitForm.permit_no = '';
                        this.permitForm.status = '';
                        this.permitForm.annual_limit = '';
                        this.permitForm.used_amount = '';
                        this.permitForm.remaining_amount = '';
                        this.permitForm.valid_from = '';
                        this.permitForm.valid_to = '';
                        this.permitForm.attested = false;
                    }
                },

                async extractScreenshot() {
                    if (!this.screenshotFile || this.isExtracting) return;

                    this.isExtracting = true;
                    this.extractionDone = false;
                    this.extractionError = null;
                    this.extractionWarnings = [];

                    const controller = new AbortController();
                    this.extractionAbort = controller;
                    let timedOut = false;
                    const timer = setTimeout(() => { timedOut = true; controller.abort(); }, 45000);

                    const formData = new FormData();
                    formData.append('screenshot', this.screenshotFile);

                    try {
                        const response = await fetch(this.extractUrl, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: formData,
                            signal: controller.signal
                        });

                        const data = await response.json();

                        if (response.ok && data.success) {
                            const f = data.fields || {};
                            const hasAnything = f.civil_id || f.holder_name || (f.remaining_amount !== null && f.remaining_amount !== undefined && f.remaining_amount !== '');

                            if (!hasAnything) {
                                this.extractionError = 'No permit details could be read from this image.';
                                return;
                            }

                            this.screenshotToken = data.screenshot_token;
                            this.extractionConfidence = data.overall_confidence;
                            this.confidences = data.confidences || {};
                            this.rawSnippet = data.raw_snippet;
                            this.extractionWarnings = Array.isArray(data.warnings) ? data.warnings : [];

                            // Auto-fill the form
                            this.permitForm.civil_id = f.civil_id || '';
                            this.permitForm.holder_name = f.holder_name || '';
                            this.permitForm.permit_no = f.permit_no || '';
                            this.permitForm.status = f.status || '';
                            this.permitForm.annual_limit = (f.annual_limit !== null && f.annual_limit !== undefined && f.annual_limit !== '') ? Number(f.annual_limit).toFixed(3) : '';
                            this.permitForm.used_amount = (f.used_amount !== null && f.used_amount !== undefined && f.used_amount !== '') ? Number(f.used_amount).toFixed(3) : '';
                            this.permitForm.remaining_amount = (f.remaining_amount !== null && f.remaining_amount !== undefined && f.remaining_amount !== '') ? Number(f.remaining_amount).toFixed(3) : '';
                            this.permitForm.valid_from = f.valid_from || '';
                            this.permitForm.valid_to = f.valid_to || '';
                            if (f.verified_at) this.permitForm.verified_at = f.verified_at;

                            // New extracted data always needs a fresh cashier confirmation.
                            this.permitForm.attested = false;
                            this.permitForm.entry_method = 'SCREENSHOT';
                            this.initialExtracted = { ...this.permitForm };
                            this.fieldsEdited = [];
                            this.extractionDone = true;
                        } else {
                            this.extractionError = data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Extraction failed.');
                        }
                    } catch (err) {
                        if (err.name === 'AbortError') {
                            if (timedOut) {
                                this.extractionError = 'Reading the screenshot took too long.';
                            } else {
                                return; // replaced by a newer upload or removed
                            }
                        } else {
                            this.extractionError = 'Connection error during extraction: ' + err.message;
                        }
                    } finally {
                        clearTimeout(timer);
                        if (this.extractionAbort === controller) {
                            this.extractionAbort = null;
                            this.isExtracting = false;
                        }
                    }
                },

                formatAmountField(fieldName) {
                    const val = this.permitForm[fieldName];
                    if (val !== '' && val !== null && val !== undefined) {
                        const num = parseFloat(val);
                        if (!isNaN(num)) {
                            this.permitForm[fieldName] = num.toFixed(3);
                        }
                    }
                },

                async checkShiftReuse() {
                    const cid = (this.permitForm.civil_id || '').trim();
                    if (!cid) return;

                    try {
                        const response = await fetch(`${this.permitLookupUrl}?civil_id=${encodeURIComponent(cid)}`, {
                            headers: { 'Accept': 'application/json' }
                        });
                        const data = await response.json();

                        if (data.success && data.found && data.verification) {
                            const v = data.verification;
                            this.permitForm.holder_name = v.holder_name;
                            this.permitForm.permit_no = v.permit_no || '';
                            this.permitForm.status = v.status || '';
                            this.permitForm.annual_limit = v.annual_limit;
                            this.permitForm.used_amount = v.used_amount;
                            this.permitForm.remaining_amount = v.remaining_amount;
                            this.permitForm.valid_from = v.valid_from || '';
                            this.permitForm.valid_to = v.valid_to || '';
                            this.permitForm.verified_at = v.verified_at;
                            this.permitForm.attested = true;
                        }
                    } catch (e) {
                        // Ignore lookup errors
                    }
                },

                savePermitVerification() {
                    if (!this.isPermitVerifiedAndValid || this.isExtracting) return;
                    this.showPermitModal = false;
                },

                openPaymentModal() {
                    if (!this.canProceedToPayment) {
                        if (this.permitState === 'REQUIRED') this.showPermitModal = true;
                        return;
                    }
                    this.paymentMethod = 'CASH';
                    this.cashTendered = Number(this.grandTotal.toFixed(3));
                    this.cardTendered = 0;
                    this.checkoutError = null;
                    this.showPaymentModal = true;
                },

                get changeDue() {
                    return this.cashTendered - this.grandTotal;
                },

                get isTenderValid() {
                    if (this.paymentMethod === 'CASH') {
                        return this.cashTendered >= (this.grandTotal - 0.001);
                    }
                    if (this.paymentMethod === 'CARD') {
                        return true;
                    }
                    if (this.paymentMethod === 'SPLIT') {
                        return (this.cashTendered + this.cardTendered) >= (this.grandTotal - 0.001);
                    }
                    return false;
                },

                async executeCheckout() {
                    if (!this.isTenderValid || this.isSubmittingCheckout) return;

                    this.isSubmittingCheckout = true;
                    this.checkoutError = null;

                    const payload = {
                        cart: this.cart.map(item => ({
                            product_id: item.id,
                            quantity: item.qty,
                            unit_price: item.price
                        })),
                        payment: {
                            method: this.paymentMethod,
                            cash_amount: this.paymentMethod === 'CARD' ? 0 : this.cashTendered,
                            card_amount: this.paymentMethod === 'CASH' ? 0 : (this.paymentMethod === 'CARD' ? this.grandTotal : this.cardTendered),
                            card_reference: this.cardRefInput || null,
                        },
                        counter_id: this.counterId,
                        civil_id: this.permitControlledTotal > 0 ? this.permitForm.civil_id : null,
                        permit_verification: this.permitControlledTotal > 0 ? {
                            civil_id: this.permitForm.civil_id,
                            holder_name: this.permitForm.holder_name,
                            permit_no: this.permitForm.permit_no || null,
                            status: this.permitForm.status || null,
                            annual_limit: (this.permitForm.annual_limit !== '' && this.permitForm.annual_limit !== null) ? Number(this.permitForm.annual_limit) : null,
                            used_amount: (this.permitForm.used_amount !== '' && this.permitForm.used_amount !== null) ? Number(this.permitForm.used_amount) : null,
                            remaining_amount: (this.permitForm.remaining_amount !== '' && this.permitForm.remaining_amount !== null) ? Number(this.permitForm.remaining_amount) : null,
                            valid_from: this.permitForm.valid_from || null,
                            valid_to: this.permitForm.valid_to || null,
                            verified_at: this.permitForm.verified_at,
                            entry_method: this.screenshotToken ? 'SCREENSHOT' : 'MANUAL',
                            screenshot_token: this.screenshotToken,
                            extraction_confidence: this.extractionConfidence,
                            fields_edited_after_extraction: this.fieldsEdited,
                            attested: this.permitForm.attested,
                        } : null
                    };

                    try {
                        const response = await fetch(this.checkoutUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken
                            },
                            body: JSON.stringify(payload)
                        });

                        const data = await response.json();

                        if (response.ok && data.success && data.receipt_url) {
                            window.location.href = data.receipt_url;
                        } else {
                            this.checkoutError = 'Checkout failed: ' + (data.error || (data.errors ? Object.values(data.errors).flat().join(' ') : 'Unknown error.'));
                            this.isSubmittingCheckout = false;
                        }
                    } catch (err) {
                        this.checkoutError = 'System error during checkout: ' + err.message;
                        this.isSubmittingCheckout = false;
                    }
                }
            }));
        });
    </script>
</x-app-layout>