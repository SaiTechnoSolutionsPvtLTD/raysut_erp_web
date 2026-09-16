<?php

use App\Livewire\Forms\LoginForm;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.guest')] class extends Component
{
    public LoginForm $form;

    /**
     * Handle an incoming authentication request.
     */
    public function login(): void
    {
        $this->validate();

        $this->form->authenticate();

        Session::regenerate();

        $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="space-y-6">
    <div class="text-center space-y-3">
        <!-- RMSC 4-Tile Lockup Logo -->
        <div class="flex justify-center">
            <img src="{{ asset('images/rmsc-logo-icon-row.png') }}" alt="RMSC 4-Tile Logo" class="h-16 w-auto object-contain drop-shadow-sm">
        </div>
        <h2 class="font-heading font-extrabold text-2xl text-slate-900 tracking-wide">RMSC ERP</h2>
        <p class="text-xs text-slate-500 font-medium">Enterprise Core Portal</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form wire:submit="login" class="space-y-4">
        <!-- Username or Email -->
        <div>
            <label for="email" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Username or Email</label>
            <input wire:model="form.email" id="email" class="w-full rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm font-medium shadow-2xs" type="text" name="email" required autofocus autocomplete="username" placeholder="e.g. admin or admin@rmsc.om" />
            <x-input-error :messages="$errors->get('form.email')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <!-- Password -->
        <div x-data="{ showPassword: false }">
            <label for="password" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Password</label>
            <div class="relative">
                <input wire:model="form.password" id="password" type="password" x-bind:type="showPassword ? 'text' : 'password'" class="w-full pr-10 rounded-xl border-slate-200 focus:border-rmsc-red focus:ring-rmsc-red text-sm shadow-2xs"
                                name="password"
                                required autocomplete="current-password" placeholder="••••••••" />
                <button type="button" @click="showPassword = !showPassword" class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-hidden">
                    <svg x-show="!showPassword" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <svg x-show="showPassword" class="w-4 h-4 text-rmsc-red" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="display: none;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.842-.813c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                </button>
            </div>
            <x-input-error :messages="$errors->get('form.password')" class="mt-2 text-xs text-rmsc-red" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember" class="inline-flex items-center cursor-pointer">
                <input wire:model="form.remember" id="remember" type="checkbox" class="rounded border-slate-300 text-rmsc-red focus:ring-rmsc-red" name="remember">
                <span class="ms-2 text-xs text-slate-600 font-medium">{{ __('Remember me') }}</span>
            </label>
        </div>

        <div>
            <button type="submit" class="w-full py-3 bg-rmsc-red hover:bg-rmsc-red-dark text-white font-bold text-sm rounded-xl shadow-lg shadow-rmsc-red/20 transition duration-150">
                Sign In to RMSC ERP
            </button>
        </div>
    </form>

    <!-- Full Legal Lockup Footer -->
    <div class="pt-4 border-t border-slate-100 text-center">
        <img src="{{ asset('images/rmsc-logo-full.jpg') }}" alt="Raysut Marketing & Supply Co. LLC Full Logo" class="h-12 w-auto mx-auto object-contain opacity-90">
    </div>
</div>
