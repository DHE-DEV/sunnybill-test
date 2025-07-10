<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Pages\Page;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\ChartWidget;
use Filament\Support\Colors\Color;

class UserManagement extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-user-group';

    protected static string $view = 'filament.pages.user-management';

    protected static ?string $title = 'Benutzer-Übersicht';

    protected static ?string $navigationLabel = 'Benutzer-Übersicht';

    protected static ?string $navigationGroup = 'System';

    protected static ?int $navigationSort = 2;

    public static function canAccess(): bool
    {
        return auth()->user()?->teams()->whereIn('name', ['Administrator', 'Superadmin'])->exists() ?? false;
    }

    public function getWidgets(): array
    {
        return [
            UserStatsWidget::class,
            UserRoleDistributionWidget::class,
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserStatsWidget::class,
            UserRoleDistributionWidget::class,
        ];
    }
}

class UserStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::where('is_active', true)->count();
        $inactiveUsers = User::where('is_active', false)->count();
        $verifiedUsers = User::whereNotNull('email_verified_at')->count();
        $recentLogins = User::where('last_login_at', '>=', now()->subDays(30))->count();
        $admins = User::where('role', 'admin')->count();

        return [
            BaseWidget\Stat::make('Gesamt Benutzer', $totalUsers)
                ->description('Alle registrierten Benutzer')
                ->descriptionIcon('heroicon-m-users')
                ->color('primary'),

            BaseWidget\Stat::make('Aktive Benutzer', $activeUsers)
                ->description($inactiveUsers . ' inaktiv')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),

            BaseWidget\Stat::make('Verifizierte E-Mails', $verifiedUsers)
                ->description(($totalUsers - $verifiedUsers) . ' nicht verifiziert')
                ->descriptionIcon('heroicon-m-shield-check')
                ->color('info'),

            BaseWidget\Stat::make('Kürzlich aktiv', $recentLogins)
                ->description('Anmeldung in den letzten 30 Tagen')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            BaseWidget\Stat::make('Administratoren', $admins)
                ->description('Benutzer mit Admin-Rechten')
                ->descriptionIcon('heroicon-m-shield-exclamation')
                ->color('danger'),
        ];
    }
}

class UserRoleDistributionWidget extends ChartWidget
{
    protected static ?string $heading = 'Rollenverteilung';

    protected function getData(): array
    {
        $roles = User::getRoles();
        $data = [];
        $labels = [];
        $colors = [];

        foreach ($roles as $roleKey => $roleLabel) {
            $count = User::where('role', $roleKey)->count();
            if ($count > 0) {
                $data[] = $count;
                $labels[] = $roleLabel;
                
                $colors[] = match($roleKey) {
                    'admin' => '#ef4444',
                    'manager' => '#f59e0b',
                    'user' => '#10b981',
                    'viewer' => '#6b7280',
                    default => '#6b7280'
                };
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Benutzer',
                    'data' => $data,
                    'backgroundColor' => $colors,
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                ],
            ],
            'maintainAspectRatio' => false,
        ];
    }
}