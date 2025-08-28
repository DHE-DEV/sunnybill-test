<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use App\Filament\Widgets\SolarStatsWidget;
use App\Filament\Pages\Auth\Login;
use App\Models\CompanySetting;
use Filament\Navigation\UserMenuItem;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;
use Filament\Navigation\NavigationBuilder;
use Filament\Navigation\NavigationItem;
use App\Models\GmailEmail;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login(Login::class)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                \App\Filament\Pages\Dashboard::class,
                \App\Filament\Pages\NotificationsPage::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                SolarStatsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandName('')
            ->brandLogo(function () {
                try {
                    $settings = CompanySetting::current();
                    return $settings->logo_path
                        ? asset('storage/' . $settings->logo_path)
                        : asset('images/voltmaster-logo.svg');
                } catch (\Exception $e) {
                    return asset('images/voltmaster-logo.svg');
                }
            })
            ->brandLogoHeight('3rem')
            ->favicon(asset('images/voltmaster-favicon.svg'))
            ->navigationGroups([
                'Javier\'s $ Sot Machine',
                'Kunden',
                'Lieferanten',
                'Solar Management',
                'Projektverwaltung',
                'Fakturierung',
                'Rechnungen',
                'Stammdaten',
                \Filament\Navigation\NavigationGroup::make('System')
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make('Debug')
                    ->collapsed(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full')
            ->userMenuItems([
                'notifications' => UserMenuItem::make()
                    ->label(function () {
                        if (Auth::check()) {
                            $user = Auth::user();
                            $unreadCount = $user->unread_notifications_count;
                            return 'Benachrichtigungen' . ($unreadCount > 0 ? " ({$unreadCount})" : '');
                        }
                        return 'Benachrichtigungen';
                    })
                    ->url('/admin/notifications')
                    ->icon('heroicon-o-bell')
                    ->sort(1),
            ])
            ->renderHook(
                'panels::head.end',
                fn (): string => $this->getCustomCssLink()
            )
            ->renderHook(
                'panels::body.end',
                fn (): string => !request()->routeIs('filament.admin.auth.login')
                    ? view('layouts.filament-notifications')->render()
                    : ''
            )
            ->renderHook(
                'panels::sidebar.footer',
                fn (): string => view('vendor.filament.components.version')->render()
            );
    }

    private function getCustomCssLink(): string
    {
        try {
            // Versuche zuerst Vite zu verwenden
            $viteManifestPath = public_path('build/manifest.json');
            if (file_exists($viteManifestPath)) {
                return '<link rel="stylesheet" href="' . \Illuminate\Support\Facades\Vite::asset('resources/css/admin-custom.css') . '">';
            }
        } catch (\Exception $e) {
            // Fallback wenn Vite nicht verfügbar ist
        }
        
        // Fallback: CSS direkt laden falls verfügbar
        $cssPath = public_path('css/admin-custom.css');
        if (file_exists($cssPath)) {
            return '<link rel="stylesheet" href="' . asset('css/admin-custom.css') . '">';
        }
        
        // Als letztes Fallback: Inline CSS
        return '<style>
            [data-filament-table-bulk-actions-container] .fi-ta-bulk-actions {
                min-width: 280px !important;
            }
            [data-filament-table-bulk-actions-container] .fi-ta-bulk-actions .fi-dropdown-list-item {
                min-width: 260px !important;
                white-space: nowrap !important;
            }
        </style>';
    }
}
