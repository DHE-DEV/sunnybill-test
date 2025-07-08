<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Facades\Filament;
use Filament\Navigation\UserMenuItem;
use Illuminate\Support\Facades\Auth;
use App\Models\Notification;

class FilamentNotificationServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Füge Benachrichtigungen zum User-Menü hinzu
        Filament::serving(function () {
            if (Auth::check()) {
                $user = Auth::user();
                $unreadCount = $user->unread_notifications_count;
                
                Filament::registerUserMenuItems([
                    UserMenuItem::make()
                        ->label('Benachrichtigungen' . ($unreadCount > 0 ? " ({$unreadCount})" : ''))
                        ->url(route('filament.admin.pages.notifications'))
                        ->icon('heroicon-o-bell')
                        ->sort(1),
                ]);
            }
        });
    }
}
