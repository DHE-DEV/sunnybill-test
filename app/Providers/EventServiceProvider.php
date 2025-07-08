<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Events\NewGmailReceived;
use App\Events\GmailNotificationReceived;
use App\Jobs\SendGmailNotification;
use App\Services\GmailNotificationService;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        
        // Gmail Events
        NewGmailReceived::class => [
            // Wird über Job-Queue verarbeitet
        ],
        
        GmailNotificationReceived::class => [
            // Wird über Job-Queue verarbeitet
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        // Gmail Event Listeners
        Event::listen(NewGmailReceived::class, function (NewGmailReceived $event) {
            // Use GmailNotificationService to handle notifications
            $notificationService = app(GmailNotificationService::class);
            $notificationService->sendNotifications($event->email, $event->users);
        });
        
        Event::listen(GmailNotificationReceived::class, function (GmailNotificationReceived $event) {
            // Handle Gmail notification received event
            // This can be used for additional processing if needed
        });
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
