<div>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="font-heading font-bold text-2xl text-slate-900 leading-tight">Company Profile & Setup</h2>
                <p class="text-xs text-slate-500 mt-1">Manage Raysut Marketing & Supply Co. LLC legal entity specifications</p>
            </div>
            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-700 border border-slate-200">
                Top Level Entity (Org Hierarchy)
            </span>
        </div>
    </x-slot>

    <div class="max-w-6xl mx-auto space-y-6">
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-8 h-8 rounded-lg bg-rmsc-red/10 text-rmsc-red flex items-center justify-center font-bold">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800 text-base">Entity Details</h3>
                        <p class="text-xs text-slate-400">Legal entity identification and registered details</p>
                    </div>
                </div>
                <div class="flex items-center space-x-3">
                    <span class="text-xs text-slate-400 font-mono bg-slate-100 px-2.5 py-1 rounded-lg border border-slate-200">ID: {{ $company?->company_id ?? 'NEW' }}</span>
                    @if(! $isEditing && auth()->user()?->hasPermission('System, Security & Setup', 'EDIT'))
                        <button wire:click="toggleEdit" type="button" class="px-4 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-xs transition flex items-center space-x-1.5 cursor-pointer">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <span>Edit Profile</span>
                        </button>
                    @endif
                </div>
            </div>

            @if(! $isEditing)
                <!-- Clean Read-Only Profile View -->
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2 p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Registered Legal Name</span>
                            <span class="font-heading font-extrabold text-slate-900 text-xl">{{ $company_name }}</span>
                        </div>

                        <div class="p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Oman Commercial Registration (CR) No.</span>
                            <span class="font-mono font-bold text-slate-800 text-base">{{ $trade_license_no ?: 'Not Provided' }}</span>
                        </div>

                        <div class="p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Liquor Trading License No.</span>
                            <span class="font-mono font-bold text-slate-800 text-base">{{ $liquor_license_no ?: 'Not Provided' }}</span>
                        </div>

                        <div class="p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Base Reporting Currency</span>
                            <div class="flex items-center space-x-2.5 mt-1">
                                <span class="font-mono font-extrabold text-emerald-700 bg-emerald-50 px-2.5 py-0.5 rounded border border-emerald-200 text-xs">{{ $base_currency_code }}</span>
                                <span class="text-xs text-slate-600 font-medium">Omani Rial (3 Decimals)</span>
                            </div>
                        </div>

                        <div class="p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Entity Status</span>
                            <div class="mt-1">
                                @if($is_active)
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                        ● Active Entity
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                        Inactive Entity
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="md:col-span-2 p-5 bg-slate-50/80 rounded-xl border border-slate-200/60">
                            <span class="block text-[11px] font-bold text-slate-400 uppercase tracking-wider mb-1">Registered Address in Oman</span>
                            <p class="text-sm text-slate-700 font-medium leading-relaxed">{{ $address ?: 'No registered address recorded.' }}</p>
                        </div>
                    </div>
                </div>
            @else
                <!-- Form View (Edit Mode) -->
                <form wire:submit.prevent="save" class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Registered Legal Name *</label>
                            <input type="text" wire:model="company_name" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium shadow-2xs" placeholder="e.g. Raysut Marketing & Supply Co. LLC (RMSC)">
                            @error('company_name') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Oman Commercial Registration (CR) No.</label>
                            <input type="text" wire:model="trade_license_no" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium shadow-2xs" placeholder="e.g. CR-1098234">
                            @error('trade_license_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Liquor Trading License No.</label>
                            <input type="text" wire:model="liquor_license_no" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium shadow-2xs" placeholder="e.g. LL-2026-OM">
                            @error('liquor_license_no') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Base Reporting Currency *</label>
                            <select wire:model="base_currency_code" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium shadow-2xs bg-slate-50">
                                <option value="OMR">OMR — Omani Rial (3 Decimals)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1">OMR standard requiring 3 decimal places across all transactions</p>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Status</label>
                            <label class="relative inline-flex items-center cursor-pointer mt-1">
                                <input type="checkbox" wire:model="is_active" class="sr-only peer">
                                <div class="w-11 h-6 bg-slate-200 peer-focus:outline-hidden rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                                <span class="ml-3 text-xs font-bold text-slate-700">{{ $is_active ? 'Active Entity' : 'Inactive' }}</span>
                            </label>
                        </div>

                        <div class="md:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-2">Registered Address in Oman</label>
                            <textarea wire:model="address" rows="3" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs" placeholder="Street, Building, Way No., City, Muscat/Salalah"></textarea>
                            @error('address') <span class="text-xs text-rmsc-red mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="pt-4 border-t border-slate-100 flex justify-end space-x-3">
                        <button type="button" wire:click="cancelEdit" class="px-4 py-2 border border-slate-200 text-slate-700 font-semibold text-xs rounded-xl hover:bg-slate-50 transition cursor-pointer">Cancel</button>
                        <button type="submit" class="px-6 py-2 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-semibold text-xs rounded-xl shadow-md transition flex items-center space-x-2 cursor-pointer">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            <span>Save Company Settings</span>
                        </button>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>
