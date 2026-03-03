<?php

namespace App\Listeners;

use Illuminate\Mail\Events\MessageSending;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ThrottleMailSending
{
    /**
     * Maximale Anzahl E-Mails pro Zeitfenster.
     */
    protected int $maxPerWindow = 30;

    /**
     * Zeitfenster in Sekunden (z.B. 60 = 1 Minute).
     */
    protected int $windowSeconds = 60;

    /**
     * Wartezeit in Sekunden wenn das Limit erreicht ist.
     */
    protected int $pauseSeconds = 30;

    public function handle(MessageSending $event): void
    {
        $cacheKey = 'mail_throttle_timestamps';
        $now = time();

        $timestamps = Cache::get($cacheKey, []);

        // Alte Einträge außerhalb des Zeitfensters entfernen
        $timestamps = array_values(array_filter(
            $timestamps,
            fn (int $ts) => ($now - $ts) < $this->windowSeconds
        ));

        if (count($timestamps) >= $this->maxPerWindow) {
            $oldestInWindow = min($timestamps);
            $waitUntil = $oldestInWindow + $this->windowSeconds;
            $sleepFor = max(1, $waitUntil - $now);

            Log::info("Mail-Throttle: Rate-Limit erreicht ({$this->maxPerWindow}/{$this->windowSeconds}s). Warte {$sleepFor}s.");

            sleep($sleepFor);

            // Nach dem Warten Timestamps neu laden und bereinigen
            $timestamps = Cache::get($cacheKey, []);
            $now = time();
            $timestamps = array_values(array_filter(
                $timestamps,
                fn (int $ts) => ($now - $ts) < $this->windowSeconds
            ));
        }

        $timestamps[] = $now;
        Cache::put($cacheKey, $timestamps, $this->windowSeconds * 2);
    }
}
