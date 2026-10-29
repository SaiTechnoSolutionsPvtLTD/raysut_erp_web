<!-- <div class="lg:sticky lg:top-0 lg:h-screen shrink-0 z-40">
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileSidebarOpen = false" 
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
         style="display: none;">
    </div>

    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed lg:static inset-y-0 left-0 z-50 w-72 bg-rmsc-slate text-slate-100 h-screen flex flex-col border-r border-slate-700/50 shrink-0 transition-transform duration-300 ease-in-out">

        <div class="h-16 px-6 border-b border-slate-700/50 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="block">
                <x-logo />
            </a>
            <button @click="mobileSidebarOpen = false" class="lg:hidden p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <nav class="flex-1 overflow-y-auto px-3 py-6 no-scrollbar">

        @php
            $user = auth()->user();
            $groups = [
                [
                    'title' => 'Overview',
                    'items' => [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'module' => null, 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ],
                ],
                [
                    'title' => 'Branch & Sales Operations',
                    'items' => [
                        ['route' => 'shifts.index', 'label' => 'Cashier Shifts & Floats', 'module' => 'Branch & Shift Operations', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'system.branches', 'label' => 'Branches & Facilities', 'module' => 'Branch & Shift Operations', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'system.counters', 'label' => 'POS Counters', 'module' => 'Branch & Shift Operations', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ],
                ],
                [
                    'title' => 'Retail & Wholesale Sales',
                    'items' => [
                        ['route' => 'pos.terminal', 'label' => 'Retail POS Billing', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        ['route' => 'permits.index', 'label' => 'Oman Permits', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'],
                        ['route' => 'wholesale.quotations.index', 'label' => 'Wholesale Quotations', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'wholesale.orders.index', 'label' => 'Sales Orders', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                        ['route' => 'wholesale.delivery-notes.index', 'label' => 'Delivery Notes', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8'],
                        ['route' => 'wholesale.invoices.index', 'label' => 'Sales Invoices', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['route' => 'wholesale.credit.index', 'label' => 'Customer Credit', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'phase3.demo', 'label' => 'Phase 3 Exit Demo', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
                [
                    'title' => 'Catalog & Partners',
                    'items' => [
                        ['route' => 'master-data.products', 'label' => 'Products & Pricing', 'module' => 'Master Data', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['route' => 'master-data.suppliers', 'label' => 'Suppliers', 'module' => 'Master Data', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'master-data.customers', 'label' => 'Customers & Accounts', 'module' => 'Master Data', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'master-data.uom', 'label' => 'Units of Measure (UOM)', 'module' => 'Master Data', 'icon' => 'M3 6l3 18h12l3-18H3z'],
                        ['route' => 'master-data.tax-rates', 'label' => 'Tax & VAT Setup', 'module' => 'Master Data', 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l5-3 5 3z'],
                    ],
                ],
                [
                    'title' => 'Procurement',
                    'items' => [
                        ['route' => 'purchase-import.purchase-orders', 'label' => 'Purchase Orders', 'module' => 'Purchase & Import', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'purchase-import.shipments', 'label' => 'Shipments & Bayan', 'module' => 'Purchase & Import', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8'],
                        ['route' => 'purchase-import.grns', 'label' => 'Goods Receipt Notes', 'module' => 'Purchase & Import', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['route' => 'purchase-import.duty-assessments', 'label' => 'Duty & Landed Costs', 'module' => 'Purchase & Import', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
                [
                    'title' => 'Inventory',
                    'items' => [
                        ['route' => 'warehouse-inventory.stock', 'label' => 'Stock On Hand', 'module' => 'Warehouse & Inventory', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                        ['route' => 'warehouse-inventory.ledger', 'label' => 'Stock Ledger', 'module' => 'Warehouse & Inventory', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['route' => 'warehouse-inventory.batches', 'label' => 'Batches & Expiry', 'module' => 'Warehouse & Inventory', 'icon' => 'M7 7h10M7 12h10m-5 5h5'],
                        ['route' => 'warehouse-inventory.duty-conversions', 'label' => 'Duty-Paid Conversions', 'module' => 'Warehouse & Inventory', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'warehouse-inventory.transfers', 'label' => 'Stock Transfers', 'module' => 'Warehouse & Inventory', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                        ['route' => 'warehouse-inventory.adjustments', 'label' => 'Physical Adjustments', 'module' => 'Warehouse & Inventory', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                        ['route' => 'warehouse-inventory.stock-flow-demo', 'label' => 'End-to-End Stock Flow Demonstration', 'module' => 'Warehouse & Inventory', 'icon' => 'M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664zM21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
                [
                    'title' => 'System & Setup',
                    'items' => [
                        ['route' => 'system.users', 'label' => 'Users & Staff', 'module' => 'System, Security & Setup', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'system.roles', 'label' => 'Roles & Permissions', 'module' => 'System, Security & Setup', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'system.warehouses', 'label' => 'Warehouses', 'module' => 'Warehouse & Inventory', 'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
                        ['route' => 'system.company', 'label' => 'Company Profile', 'module' => 'System, Security & Setup', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'system.audit-logs', 'label' => 'Audit Trail', 'module' => 'System, Security & Setup', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
            ];
        @endphp

        @foreach ($groups as $i => $group)
            @php
                $visibleItems = array_filter($group['items'], function($item) use ($user) {
                    if (!$item['module']) {
                        return true;
                    }
                    $modules = (array) $item['module'];
                    foreach ($modules as $mod) {
                        if ($user && $user->hasPermission($mod, 'VIEW')) {
                            return true;
                        }
                    }
                    return false;
                });
            @endphp

            @if(count($visibleItems) > 0)
                <div class="{{ $i > 0 ? 'mt-6 border-t border-slate-700/50' : '' }}">
                    <p class="px-3 mb-2 text-[11px] font-semibold text-slate-500 tracking-wide">{{ $group['title'] }}</p>

                    <div class="space-y-0.5">
                        @foreach ($visibleItems as $item)
                            @php $active = request()->routeIs($item['route']); @endphp
                            <a href="{{ route($item['route']) }}"
                                class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                                       {{ $active ? 'bg-rmsc-red text-white font-semibold' : 'text-slate-300 font-medium hover:bg-slate-700/40 hover:text-white' }}">
                                <svg class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-white' : 'text-slate-400' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <div class="p-4 border-t border-slate-700/50 flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-full bg-rmsc-red flex items-center justify-center text-xs font-bold text-white uppercase shrink-0">
                {{ substr(auth()->user()->full_name ?? 'Admin', 0, 2) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate max-w-[120px]">{{ auth()->user()->full_name ?? 'Super Admin' }}</p>
                <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->roles->first()?->role_name ?? 'Super Admin' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Sign Out" class="p-2 rounded-lg text-slate-400 hover:bg-slate-700/50 hover:text-white transition">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
        </form>
    </div>
</aside>
</div> -->

<div class="lg:sticky lg:top-0 lg:h-screen shrink-0 z-40">
    <!-- Mobile Backdrop -->
    <div x-show="mobileSidebarOpen" 
         x-transition:enter="transition-opacity ease-linear duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-linear duration-200"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileSidebarOpen = false" 
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs z-40 lg:hidden"
         style="display: none;">
    </div>

    <!-- Aside Sidebar -->
    <aside :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           class="fixed lg:static inset-y-0 left-0 z-50 w-72 bg-rmsc-slate text-slate-100 h-screen flex flex-col border-r border-slate-700/50 shrink-0 transition-transform duration-300 ease-in-out">
        <!-- Header / Brand -->
        <div class="h-16 px-6 border-b border-slate-700/50 flex items-center justify-between">
            <a href="{{ route('dashboard') }}" class="block">
                <x-logo />
            </a>
            <button @click="mobileSidebarOpen = false" class="lg:hidden p-1 rounded-lg text-slate-400 hover:text-white hover:bg-slate-700/50">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- Navigation List (keeps scroll position + scrolls active item into view) -->
        <nav class="flex-1 overflow-y-auto px-3 py-6 no-scrollbar"
             x-data="{
                key: 'rmsc_sidebar_scroll',
                init() {
                    const nav = this.$el;

                    // 1. Restore the last saved scroll position
                    const saved = sessionStorage.getItem(this.key);
                    if (saved !== null) {
                        nav.scrollTop = parseInt(saved, 10) || 0;
                    }

                    // 2. If the active item is not fully visible, centre it
                    this.$nextTick(() => {
                        const active = nav.querySelector('[data-active=\'true\']');
                        if (!active) return;
                        const n = nav.getBoundingClientRect();
                        const a = active.getBoundingClientRect();
                        if (a.top < n.top || a.bottom > n.bottom) {
                            nav.scrollTop += (a.top - n.top) - (n.height / 2) + (a.height / 2);
                        }
                    });
                },
                save() {
                    sessionStorage.setItem(this.key, this.$el.scrollTop);
                }
             }"
             @scroll.passive="save()">

        @php
            $user = auth()->user();
            $groups = [
                [
                    'title' => 'Overview',
                    'items' => [
                        ['route' => 'dashboard', 'label' => 'Dashboard', 'module' => null, 'icon' => 'M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6'],
                    ],
                ],
                [
                    'title' => 'Branch & Sales Operations',
                    'items' => [
                        ['route' => 'shifts.index', 'match' => ['shifts.*'], 'label' => 'Cashier Shifts & Floats', 'module' => 'Branch & Shift Operations', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                        ['route' => 'system.branches', 'match' => ['system.branches*'], 'label' => 'Branches & Facilities', 'module' => 'Branch & Shift Operations', 'icon' => 'M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z M15 11a3 3 0 11-6 0 3 3 0 016 0z'],
                        ['route' => 'system.counters', 'match' => ['system.counters*'], 'label' => 'POS Counters', 'module' => 'Branch & Shift Operations', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                    ],
                ],
                [
                    'title' => 'Retail & Wholesale Sales',
                    'items' => [
                        ['route' => 'pos.terminal', 'match' => ['pos.terminal*', 'pos.receipt*'], 'label' => 'Retail POS Billing', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z'],
                        ['route' => 'pos.sales', 'match' => ['pos.sales*'], 'label' => 'POS Sales & Voids', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['route' => 'permits.index', 'match' => ['permits.*'], 'label' => 'Oman Permits', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2'],
                        ['route' => 'wholesale.quotations.index', 'match' => ['wholesale.quotations.*'], 'label' => 'Wholesale Quotations', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'wholesale.orders.index', 'match' => ['wholesale.orders.*'], 'label' => 'Sales Orders', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z'],
                        ['route' => 'wholesale.delivery-notes.index', 'match' => ['wholesale.delivery-notes.*'], 'label' => 'Delivery Notes', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8'],
                        ['route' => 'wholesale.invoices.index', 'match' => ['wholesale.invoices.*'], 'label' => 'Sales Invoices', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z'],
                        ['route' => 'wholesale.credit.index', 'match' => ['wholesale.credit.*'], 'label' => 'Customer Credit', 'module' => 'Retail & Wholesale Sales', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
                [
                    'title' => 'Catalog & Partners',
                    'items' => [
                        ['route' => 'master-data.products', 'label' => 'Products & Pricing', 'module' => 'Master Data', 'icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
                        ['route' => 'master-data.suppliers', 'label' => 'Suppliers', 'module' => 'Master Data', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'master-data.customers', 'label' => 'Customers & Accounts', 'module' => 'Master Data', 'icon' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z'],
                        ['route' => 'master-data.uom', 'label' => 'Units of Measure (UOM)', 'module' => 'Master Data', 'icon' => 'M3 6l3 18h12l3-18H3z'],
                        ['route' => 'master-data.tax-rates', 'label' => 'Tax & VAT Setup', 'module' => 'Master Data', 'icon' => 'M9 14l6-6m-5.5.5h.01m4.99 5h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l5-3 5 3z'],
                    ],
                ],
                [
                    'title' => 'Procurement',
                    'items' => [
                        ['route' => 'purchase-import.purchase-orders', 'label' => 'Purchase Orders', 'module' => 'Purchase & Import', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                        ['route' => 'purchase-import.shipments', 'label' => 'Shipments & Bayan', 'module' => 'Purchase & Import', 'icon' => 'M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8'],
                        ['route' => 'purchase-import.grns', 'label' => 'Goods Receipt Notes', 'module' => 'Purchase & Import', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2'],
                        ['route' => 'purchase-import.duty-assessments', 'label' => 'Duty & Landed Costs', 'module' => 'Purchase & Import', 'icon' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
                [
                    'title' => 'Inventory',
                    'items' => [
                        ['route' => 'warehouse-inventory.stock', 'label' => 'Stock On Hand', 'module' => 'Warehouse & Inventory', 'icon' => 'M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4'],
                        ['route' => 'warehouse-inventory.ledger', 'label' => 'Stock Ledger', 'module' => 'Warehouse & Inventory', 'icon' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01'],
                        ['route' => 'warehouse-inventory.batches', 'label' => 'Batches & Expiry', 'module' => 'Warehouse & Inventory', 'icon' => 'M7 7h10M7 12h10m-5 5h5'],
                        ['route' => 'warehouse-inventory.duty-conversions', 'label' => 'Duty-Paid Conversions', 'module' => 'Warehouse & Inventory', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'warehouse-inventory.transfers', 'label' => 'Stock Transfers', 'module' => 'Warehouse & Inventory', 'icon' => 'M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4'],
                        ['route' => 'warehouse-inventory.adjustments', 'label' => 'Physical Adjustments', 'module' => 'Warehouse & Inventory', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                    ],
                ],
                [
                    'title' => 'System & Setup',
                    'items' => [
                        ['route' => 'system.users', 'label' => 'Users & Staff', 'module' => 'System, Security & Setup', 'icon' => 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z'],
                        ['route' => 'system.roles', 'label' => 'Roles & Permissions', 'module' => 'System, Security & Setup', 'icon' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z'],
                        ['route' => 'system.warehouses', 'label' => 'Warehouses', 'module' => 'Warehouse & Inventory', 'icon' => 'M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z'],
                        ['route' => 'system.company', 'label' => 'Company Profile', 'module' => 'System, Security & Setup', 'icon' => 'M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4'],
                        ['route' => 'system.audit-logs', 'label' => 'Audit Trail', 'module' => 'System, Security & Setup', 'icon' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z'],
                    ],
                ],
            ];
        @endphp

        @foreach ($groups as $i => $group)
            @php
                $visibleItems = array_filter($group['items'], function($item) use ($user) {
                    // Skip items whose route is not registered (prevents the whole sidebar from crashing)
                    if (!\Illuminate\Support\Facades\Route::has($item['route'])) {
                        return false;
                    }
                    if (!$item['module']) {
                        return true;
                    }
                    $modules = (array) $item['module'];
                    foreach ($modules as $mod) {
                        if ($user && $user->hasPermission($mod, 'VIEW')) {
                            return true;
                        }
                    }
                    return false;
                });
            @endphp

            @if(count($visibleItems) > 0)

                <div class="{{ $i > 0 ? 'mt-6 border-t border-slate-700/50' : '' }}">
                    <p class="px-3 mb-2 text-[11px] font-semibold text-slate-500 tracking-wide">{{ $group['title'] }}</p>

                    <div class="space-y-0.5">
                        @foreach ($visibleItems as $item)
                            @php $active = request()->routeIs(...($item['match'] ?? [$item['route']])); @endphp
                            <a href="{{ route($item['route']) }}"
                                data-active="{{ $active ? 'true' : 'false' }}"
                                class="relative flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors
                                       {{ $active ? 'bg-rmsc-red text-white font-semibold' : 'text-slate-300 font-medium hover:bg-slate-700/40 hover:text-white' }}">
                                <svg class="w-[18px] h-[18px] shrink-0 {{ $active ? 'text-white' : 'text-slate-400' }}"
                                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}"/>
                                </svg>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>
            @endif
        @endforeach
    </nav>

    <!-- Footer Profile Info -->
    <div class="p-4 border-t border-slate-700/50 flex items-center justify-between">
        <div class="flex items-center gap-3 min-w-0">
            <div class="w-9 h-9 rounded-full bg-rmsc-red flex items-center justify-center text-xs font-bold text-white uppercase shrink-0">
                {{ substr(auth()->user()->full_name ?? 'Admin', 0, 2) }}
            </div>
            <div class="min-w-0">
                <p class="text-sm font-semibold text-white truncate max-w-[120px]">{{ auth()->user()->full_name ?? 'Super Admin' }}</p>
                <p class="text-[11px] text-slate-400 truncate">{{ auth()->user()->roles->first()?->role_name ?? 'Super Admin' }}</p>
            </div>
        </div>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" title="Sign Out" class="p-2 rounded-lg text-slate-400 hover:bg-slate-700/50 hover:text-white transition">
                <svg class="w-[18px] h-[18px]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
        </form>
    </div>
</aside>
</div>