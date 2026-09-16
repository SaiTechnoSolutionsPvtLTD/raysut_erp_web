@props(['size' => 'md', 'showText' => true])

@php
    $company = \App\Models\Company::first();
    $logoPath = $company?->logo_path ? asset($company->logo_path) : asset('images/rmsc-logo-mark-square.png');
@endphp

<div class="flex items-center space-x-3">
    <div class="w-10 h-10 rounded-xl overflow-hidden shadow-md shadow-rmsc-red/20 border border-slate-700/50 bg-white flex items-center justify-center shrink-0">
        <img src="{{ $logoPath }}" alt="RMSC Logo Mark" class="w-full h-full object-cover">
    </div>
    @if($showText)
        <div>
            <span class="font-heading font-bold text-xl text-white tracking-wide block leading-none">RMSC ERP</span>
            <span class="text-[10px] text-slate-300 font-medium tracking-widest uppercase block mt-1">Enterprise Core</span>
        </div>
    @endif
</div>
