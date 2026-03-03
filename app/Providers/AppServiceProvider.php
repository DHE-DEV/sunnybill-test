<?php

namespace App\Providers;

use App\Listeners\ThrottleMailSending;
use App\Models\Task;
use App\Observers\TaskObserver;
use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Observer für Task-Model registrieren
        Task::observe(TaskObserver::class);

        // Mail-Throttle: Verhindert SMTP Rate-Limit-Fehler (450 4.7.0)
        Event::listen(MessageSending::class, ThrottleMailSending::class);
    }
}
