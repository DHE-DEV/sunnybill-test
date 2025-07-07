<x-filament-panels::page.simple>
    <style>
        /* Solar Panel Background für Login-Seite */
        .fi-simple-layout {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.6)), 
                       url('https://images.unsplash.com/photo-1509391366360-2e959784a276?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2072&q=80') center/cover no-repeat;
            min-height: 100vh;
        }

        /* Login-Form Styling */
        .fi-simple-main {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        /* VoltMaster Logo Styling */
        .fi-simple-header h1 {
            color: #ffd700 !important;
            font-weight: 800 !important;
            font-size: 3rem !important;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.8), 0 0 10px rgba(255, 215, 0, 0.3) !important;
            margin-bottom: 0.5rem !important;
            letter-spacing: 1px !important;
            text-align: center !important;
        }

        /* Subheading Styling */
        .fi-simple-header p {
            color: #4a5568;
            font-size: 1.1rem;
            margin-bottom: 2rem;
        }

        /* Form Input Styling */
        .fi-input {
            background: rgba(255, 255, 255, 0.9) !important;
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            backdrop-filter: blur(5px);
        }

        .fi-input:focus {
            border-color: #ffd700 !important;
            box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.1) !important;
        }

        /* Login Button Styling */
        .fi-btn-primary {
            background: linear-gradient(135deg, #f53003, #ff6b35) !important;
            border: none !important;
            box-shadow: 0 8px 25px rgba(245, 48, 3, 0.3) !important;
            transition: all 0.3s ease !important;
        }

        .fi-btn-primary:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 12px 35px rgba(245, 48, 3, 0.4) !important;
            background: linear-gradient(135deg, #d42a02, #e55a2b) !important;
        }

        /* Dark mode adjustments */
        .dark .fi-simple-main {
            background: rgba(17, 24, 39, 0.95) !important;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .dark .fi-simple-header p {
            color: #d1d5db;
        }

        .dark .fi-input {
            background: rgba(31, 41, 55, 0.9) !important;
            border: 2px solid rgba(255, 255, 255, 0.1) !important;
            color: #f9fafb !important;
        }
    </style>

    @if (filament()->hasRegistration())
        <x-slot name="subheading">
            {{ __('filament-panels::pages/auth/login.actions.register.before') }}

            {{ $this->registerAction }}
        </x-slot>
    @endif

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE, scopes: $this->getRenderHookScopes()) }}

    <x-filament-panels::form id="form" wire:submit="authenticate">
        {{ $this->form }}

        <x-filament-panels::form.actions
            :actions="$this->getCachedFormActions()"
            :full-width="$this->hasFullWidthFormActions()"
        />
    </x-filament-panels::form>

    {{ \Filament\Support\Facades\FilamentView::renderHook(\Filament\View\PanelsRenderHook::AUTH_LOGIN_FORM_AFTER, scopes: $this->getRenderHookScopes()) }}
</x-filament-panels::page.simple>
