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

<div class="rmsc-login">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700&display=swap');

        /* Strictly 100vh viewport with 0 scrollbar */
        html, body {
            overflow: hidden !important;
            height: 100vh !important;
            margin: 0 !important;
            padding: 0 !important;
            background-color: #ffffff !important;
        }

        /* Screen Background: Clean White */
        .rmsc-login {
            position: fixed;
            inset: 0;
            width: 100vw;
            height: 100vh;
            z-index: 9999;
            overflow: hidden !important;
            background-color: #ffffff !important;
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            -webkit-font-smoothing: antialiased;
            color: #ffffff;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .rmsc-login *, .rmsc-login *::before, .rmsc-login *::after { 
            box-sizing: border-box; 
        }

        .rmsc-shell {
            width: 100%;
            height: 100%;
            max-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px;
            overflow: hidden;
            background-color: #ffffff;
        }

        /* Entire Login Card: Dark Wine Theme */
        .rmsc-card {
            position: relative;
            width: 100%;
            max-width: 1080px;
            height: 580px;
            max-height: calc(100vh - 36px);
            background: linear-gradient(140deg, #140305 0%, #29060b 45%, #6a0b12 100%);
            border-radius: 28px;
            overflow: hidden;
            display: grid;
            grid-template-columns: 1fr 1.08fr;
            box-shadow: 
                0 30px 80px -15px rgba(106, 11, 18, 0.4),
                0 10px 30px -10px rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        /* Internal ambient glows inside the dark wine card */
        .rmsc-card::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -10%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(237, 28, 36, 0.3) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
        }

        .rmsc-card::after {
            content: '';
            position: absolute;
            bottom: -15%;
            left: -10%;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(224, 160, 48, 0.18) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
        }

        /* Left Side: Form Section inside Dark Card */
        .rmsc-side {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            padding: 36px 44px;
            background: transparent;
            border-right: 1px solid rgba(255, 255, 255, 0.08);
        }

        .rmsc-brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .rmsc-logo-top { 
            display: block; 
            height: 42px; 
            width: auto; 
            object-fit: contain;
            filter: drop-shadow(0 2px 8px rgba(0, 0, 0, 0.4));
        }

        .rmsc-badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            color: #fecdd3;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.02em;
            text-transform: uppercase;
        }

        .rmsc-main { 
            width: 100%; 
            max-width: 380px; 
            margin: auto 0;
        }

        .rmsc-title-group {
            margin-bottom: 20px;
        }

        .rmsc-title {
            margin: 0;
            font-size: 26px;
            line-height: 1.2;
            font-weight: 700;
            letter-spacing: -0.02em;
            color: #ffffff;
        }

        .rmsc-sub { 
            margin: 6px 0 0; 
            font-size: 13px; 
            line-height: 1.5; 
            color: rgba(255, 255, 255, 0.75); 
            font-weight: 400;
        }

        .rmsc-status { 
            margin-top: 8px; 
            font-size: 12px; 
            color: #4ade80; 
            font-weight: 500;
        }

        .rmsc-form { 
            margin-top: 16px; 
        }

        .rmsc-field-group {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        .rmsc-field { 
            position: relative; 
        }

        .rmsc-field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #cbd5e1;
            margin-bottom: 5px;
        }

        .rmsc-input-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .rmsc-ico {
            position: absolute;
            left: 14px;
            width: 16px;
            height: 16px;
            color: rgba(255, 255, 255, 0.5);
            pointer-events: none;
            transition: color 0.15s ease;
        }

        .rmsc-login .rmsc-input {
            display: block;
            width: 100%;
            height: 44px;
            margin: 0;
            padding: 0 40px 0 40px;
            border: 1.5px solid rgba(255, 255, 255, 0.18);
            border-radius: 10px;
            background-color: rgba(255, 255, 255, 0.09);
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.2);
            font-size: 13px;
            font-weight: 500;
            color: #ffffff;
            outline: none;
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .rmsc-login .rmsc-input::placeholder { 
            color: rgba(255, 255, 255, 0.45); 
            font-weight: 400; 
        }

        .rmsc-login .rmsc-input:hover {
            border-color: rgba(255, 255, 255, 0.3);
            background-color: rgba(255, 255, 255, 0.12);
        }

        .rmsc-login .rmsc-input:focus {
            background-color: rgba(255, 255, 255, 0.15);
            border-color: #ed1c24;
            box-shadow: 0 0 0 3px rgba(237, 28, 36, 0.3);
            outline: none;
        }

        .rmsc-input-wrapper:focus-within .rmsc-ico {
            color: #fca5a5;
        }

        .rmsc-eye {
            position: absolute;
            right: 4px;
            height: 36px;
            width: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            border: 0;
            background: transparent;
            color: rgba(255, 255, 255, 0.55);
            cursor: pointer;
            border-radius: 6px;
            transition: color 0.15s ease;
        }

        .rmsc-eye:hover { 
            color: #ffffff; 
        }

        .rmsc-eye:focus-visible { 
            outline: 2px solid #ed1c24; 
        }

        .rmsc-eye svg { 
            width: 16px; 
            height: 16px; 
        }

        .rmsc-error { 
            margin-top: 2px; 
        }

        .rmsc-error ul { 
            list-style: none; 
            margin: 2px 0 0; 
            padding: 0; 
            font-size: 11px; 
            color: #f87171; 
            font-weight: 500;
        }

        .rmsc-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-top: 12px;
        }

        .rmsc-remember { 
            display: inline-flex; 
            align-items: center; 
            gap: 6px; 
            cursor: pointer; 
            font-size: 12px; 
            color: #cbd5e1; 
            font-weight: 500;
            user-select: none;
        }

        .rmsc-login .rmsc-check {
            width: 15px;
            height: 15px;
            margin: 0;
            border-radius: 4px;
            border: 1.5px solid rgba(255, 255, 255, 0.3);
            color: #ed1c24;
            accent-color: #ed1c24;
            cursor: pointer;
            background-color: rgba(255, 255, 255, 0.1);
        }

        .rmsc-forgot { 
            font-size: 12px; 
            color: #f87171; 
            font-weight: 600;
            text-decoration: none; 
            transition: color 0.15s;
        }

        .rmsc-forgot:hover { 
            color: #ef4444; 
            text-decoration: underline; 
        }

        .rmsc-actions { 
            margin-top: 20px; 
        }

        .rmsc-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            height: 46px;
            padding: 0 20px;
            border: 0;
            border-radius: 10px;
            background: linear-gradient(135deg, #ed1c24 0%, #b80f16 100%);
            color: #ffffff;
            font-family: inherit;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 8px 24px -2px rgba(237, 28, 36, 0.5);
            transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .rmsc-btn:hover { 
            background: linear-gradient(135deg, #f02d35 0%, #a40a11 100%);
            transform: translateY(-1px); 
            box-shadow: 0 10px 28px -2px rgba(237, 28, 36, 0.6);
        }

        .rmsc-btn:active {
            transform: translateY(0);
        }

        .rmsc-btn[disabled] { 
            opacity: 0.7; 
            cursor: not-allowed; 
            transform: none; 
        }

        .rmsc-spin { 
            width: 16px; 
            height: 16px; 
            animation: rmsc-spin 800ms linear infinite; 
        }

        @keyframes rmsc-spin { 
            to { transform: rotate(360deg); } 
        }

        .rmsc-footer-logo {
            margin-top: auto;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .rmsc-logo-full { 
            display: block; 
            height: 32px; 
            width: auto; 
            opacity: 0.9; 
            filter: drop-shadow(0 2px 6px rgba(0, 0, 0, 0.4));
            transition: opacity 0.2s;
        }

        .rmsc-logo-full:hover {
            opacity: 1;
        }

        /* Right Side: Hero Showcase inside Dark Card */
        .rmsc-panel { 
            position: relative;
            z-index: 2;
            padding: 36px 40px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow: hidden;
            color: #ffffff;
        }

        .rmsc-panel-header {
            position: relative;
            z-index: 2;
        }

        .rmsc-panel-tag {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 4px 12px;
            border-radius: 999px;
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.04em;
            text-transform: uppercase;
            color: #fecdd3;
            margin-bottom: 12px;
        }

        .rmsc-panel-title {
            font-size: 24px;
            font-weight: 700;
            line-height: 1.3;
            color: #ffffff;
            margin: 0 0 6px 0;
            letter-spacing: -0.01em;
        }

        .rmsc-panel-sub {
            font-size: 13px;
            line-height: 1.5;
            color: rgba(255, 255, 255, 0.78);
            margin: 0;
        }

        /* AI Image Showcase Box */
        .rmsc-hero-container {
            position: relative;
            z-index: 2;
            margin: 16px 0;
        }

        .rmsc-hero-frame {
            position: relative;
            border-radius: 16px;
            overflow: hidden;
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 
                0 20px 45px -10px rgba(0, 0, 0, 0.55),
                0 0 30px rgba(237, 28, 36, 0.15);
        }

        .rmsc-hero-img {
            display: block;
            width: 100%;
            height: 235px;
            object-fit: cover;
            object-position: center;
        }

        .rmsc-hero-overlay {
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg, rgba(0, 0, 0, 0) 45%, rgba(19, 3, 4, 0.88) 100%);
            display: flex;
            align-items: flex-end;
            padding: 16px;
        }

        .rmsc-features-list {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
        }

        .rmsc-feature-chip {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            background: rgba(0, 0, 0, 0.55);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.22);
            border-radius: 999px;
            font-size: 10px;
            font-weight: 600;
            color: #ffffff;
        }

        .rmsc-feature-chip svg {
            width: 12px;
            height: 12px;
            color: #fbbf24;
        }

        .rmsc-panel-footer {
            position: relative;
            z-index: 2;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 14px;
            border-top: 1px solid rgba(255, 255, 255, 0.14);
            font-size: 11px;
            color: rgba(255, 255, 255, 0.65);
        }

        @media (max-width: 991px) {
            .rmsc-card {
                grid-template-columns: 1fr;
                max-width: 440px;
                height: auto;
                max-height: calc(100vh - 20px);
                overflow-y: auto;
            }
            .rmsc-panel {
                display: none;
            }
            .rmsc-side {
                padding: 28px 24px;
                border-right: 0;
            }
        }
    </style>

    <div class="rmsc-shell">
        <div class="rmsc-card">

            <!-- Form Side -->
            <div class="rmsc-side">
                <div class="rmsc-brand-header">
                    <img src="{{ asset('images/rmsc-logo-icon-row-transparent.png') }}" alt="RMSC logo" class="rmsc-logo-top">
                    <span class="rmsc-badge-pill">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                        Bonded ERP
                    </span>
                </div>

                <div class="rmsc-main">
                    <div class="rmsc-title-group">
                        <h1 class="rmsc-title">Welcome to RMSC ERP</h1>
                        <p class="rmsc-sub">Sign in to manage shipments, bonded inventory, duty assessments, and order tracking.</p>
                    </div>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div class="rmsc-status">
                            <x-auth-session-status :status="session('status')" />
                        </div>
                    @endif

                    <form wire:submit="login" class="rmsc-form">
                        <div class="rmsc-field-group">
                            <!-- Username or Email -->
                            <div class="rmsc-field">
                                <label for="email" class="rmsc-field-label">Username or Email</label>
                                <div class="rmsc-input-wrapper">
                                    <svg class="rmsc-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                    </svg>
                                    <input wire:model="form.email" id="email" class="rmsc-input" type="text" name="email" required autofocus autocomplete="username" placeholder="enter your email or username" />
                                </div>
                                <div class="rmsc-error"><x-input-error :messages="$errors->get('form.email')" /></div>
                            </div>

                            <!-- Password -->
                            <div class="rmsc-field" x-data="{ showPassword: false }">
                                <label for="password" class="rmsc-field-label">Password</label>
                                <div class="rmsc-input-wrapper">
                                    <svg class="rmsc-ico" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                    <input wire:model="form.password" id="password" class="rmsc-input" type="password" x-bind:type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password" placeholder="••••••••••••" />
                                    <button type="button" class="rmsc-eye" @click="showPassword = !showPassword" x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'">
                                        <svg x-show="!showPassword" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        <svg x-show="showPassword" style="display: none; color: #ed1c24;" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a10.018 10.018 0 013.842-.813c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18"/></svg>
                                    </button>
                                </div>
                                <div class="rmsc-error"><x-input-error :messages="$errors->get('form.password')" /></div>
                            </div>
                        </div>

                        <!-- Remember Me / Forgot -->
                        <div class="rmsc-row">
                            <label for="remember" class="rmsc-remember">
                                <input wire:model="form.remember" id="remember" class="rmsc-check" type="checkbox" name="remember">
                                <span>Remember me</span>
                            </label>
                            @if (Route::has('password.request'))
                                <a href="{{ route('password.request') }}" wire:navigate class="rmsc-forgot">Forgot password?</a>
                            @endif
                        </div>

                        <div class="rmsc-actions">
                            <button type="submit" class="rmsc-btn" wire:loading.attr="disabled" wire:target="login">
                                <svg wire:loading wire:target="login" class="rmsc-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle style="opacity:.25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path style="opacity:.9" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>
                                <span wire:loading.remove wire:target="login">Sign In</span>
                                <span wire:loading wire:target="login">Signing in…</span>
                            </button>
                        </div>
                    </form>
                </div>

                <div class="rmsc-footer-logo">
                    <img src="{{ asset('images/rmsc-logo-full-transparent.png') }}" alt="Raysut Marketing &amp; Supply Co. LLC full logo" class="rmsc-logo-full">
                </div>
            </div>

            <!-- Brand Side (Alcohol Spirits & Supply Chain Hero Showcase) -->
            <div class="rmsc-panel" aria-hidden="true">
                <div class="rmsc-panel-header">
                    <div class="rmsc-panel-tag">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 22h8M12 11v11M19 3l-7 8-7-8h14z"/></svg>
                        Spirits &amp; Wine Supply Chain
                    </div>
                    <h2 class="rmsc-panel-title">End-to-End Beverage Logistics</h2>
                    <p class="rmsc-panel-sub">From port arrival &amp; customs release to bonded warehouse stock allocation.</p>
                </div>

                <!-- AI Generated Beverage & Spirits Hero Frame -->
                <div class="rmsc-hero-container">
                    <div class="rmsc-hero-frame">
                        <img src="{{ asset('images/alcohol-hero.jpg') }}" alt="RMSC Spirits & Wine Bonded Stock" class="rmsc-hero-img">
                        <div class="rmsc-hero-overlay">
                            <div class="rmsc-features-list">
                                <span class="rmsc-feature-chip">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                                    Bonded Stock
                                </span>
                                <span class="rmsc-feature-chip">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                                    Duty Control
                                </span>
                                <span class="rmsc-feature-chip">
                                    <svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2L15.09 8.26L22 9.27L17 14.14L18.18 21.02L12 17.77L5.82 21.02L7 14.14L2 9.27L8.91 8.26L12 2Z"/></svg>
                                    Batch Traceability
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rmsc-panel-footer">
                    <span>&copy; {{ date('Y') }} Raysut Marketing &amp; Supply Co. LLC</span>
                    <span>Enterprise ERP v2.4</span>
                </div>
            </div>

        </div>
    </div>
</div>