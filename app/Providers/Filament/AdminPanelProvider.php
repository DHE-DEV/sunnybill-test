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
            ->brandName(function () {
                try {
                    $settings = CompanySetting::current();
                    return $settings->company_name ?? 'VoltMaster';
                } catch (\Exception $e) {
                    return 'VoltMaster';
                }
            })
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
            ->favicon(asset('images/voltmaster-favicon.svg'))
            ->navigationGroups([
                'Kunden',
                'Lieferanten',
                'Solar Management',
                'Fakturierung',
                'Rechnungen',
                'Stammdaten',
                \Filament\Navigation\NavigationGroup::make('System')
                    ->collapsed(),
                \Filament\Navigation\NavigationGroup::make('Debug')
                    ->collapsed(),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->maxContentWidth('full');
    }
}
