<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\CompanySetting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use App\Mail\GmailNotificationMail;

class SendGmailNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $user;
    public array $notificationData;
    public string $type;

    /**
     * Create a new job instance.
     */
    public function __construct(array $user, array $notificationData, string $type)
    {
        $this->user = $user;
        $this->notificationData = $notificationData;
        $this->type = $type;
        
        // Job-Konfiguration
        $this->onQueue('notifications');
        $this->delay(now()->addSeconds(1)); // Kleine Verzögerung für bessere Performance
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            Log::info('Processing Gmail notification job', [
                'user_id' => $this->user['id'],
                'type' => $this->type,
                'email_id' => $this->notificationData['email_id'] ?? null
            ]);

            switch ($this->type) {
                case 'browser':
                    $this->handleBrowserNotification();
                    break;
                    
                case 'email':
                    $this->handleEmailNotification();
                    break;
                    
                case 'push':
                    $this->handlePushNotification();
                    break;
                    
                default:
                    Log::warning('Unknown notification type in job', ['type' => $this->type]);
            }

        } catch (\Exception $e) {
            Log::error('Error in SendGmailNotification job', [
                'user_id' => $this->user['id'],
                'type' => $this->type,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Job als fehlgeschlagen markieren
            $this->fail($e);
        }
    }

    /**
     * Verarbeitet Browser-Benachrichtigungen
     */
    private function handleBrowserNotification(): void
    {
        try {
            // Prüfe User-Einstellungen
            $user = User::find($this->user['id']);
            if (!$user || !$user->gmail_browser_notifications) {
                Log::info('Browser notification skipped - user settings', [
                    'user_id' => $this->user['id']
                ]);
                return;
            }

            // Hier würde die Web Push API Integration stehen
            // Für jetzt loggen wir nur die Benachrichtigung
            Log::info('Browser notification would be sent', [
                'user_id' => $this->user['id'],
                'title' => $this->notificationData['title'],
                'message' => $this->notificationData['message'],
                'url' => $this->notificationData['url'] ?? null
            ]);

            // Simuliere Browser-Benachrichtigung durch JavaScript-Event
            // In einer echten Implementierung würde hier die Web Push API verwendet
            $this->triggerBrowserNotification();

        } catch (\Exception $e) {
            Log::error('Error handling browser notification', [
                'user_id' => $this->user['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verarbeitet E-Mail-Benachrichtigungen
     */
    private function handleEmailNotification(): void
    {
        try {
            // Prüfe User-Einstellungen
            $user = User::find($this->user['id']);
            if (!$user || !$user->gmail_email_notifications) {
                Log::info('Email notification skipped - user settings', [
                    'user_id' => $this->user['id']
                ]);
                return;
            }

            // Sende E-Mail-Benachrichtigung
            Mail::to($user->email)->send(new GmailNotificationMail(
                $user,
                $this->notificationData
            ));

            Log::info('Email notification sent successfully', [
                'user_id' => $this->user['id'],
                'user_email' => $user->email,
                'subject' => $this->notificationData['subject'] ?? 'Gmail Notification'
            ]);

        } catch (\Exception $e) {
            Log::error('Error handling email notification', [
                'user_id' => $this->user['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Verarbeitet Push-Benachrichtigungen (Mobile)
     */
    private function handlePushNotification(): void
    {
        try {
            // Hier würde die Mobile Push Notification Integration stehen
            // z.B. Firebase Cloud Messaging (FCM) oder Apple Push Notification Service (APNS)
            
            Log::info('Push notification would be sent', [
                'user_id' => $this->user['id'],
                'title' => $this->notificationData['title'],
                'message' => $this->notificationData['message'],
                'data' => [
                    'email_id' => $this->notificationData['email_id'] ?? null,
                    'url' => $this->notificationData['url'] ?? null,
                    'type' => 'gmail_notification'
                ]
            ]);

            // Simuliere Push-Benachrichtigung
            $this->sendMobilePushNotification();

        } catch (\Exception $e) {
            Log::error('Error handling push notification', [
                'user_id' => $this->user['id'],
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Triggert Browser-Benachrichtigung (Simulation)
     */
    private function triggerBrowserNotification(): void
    {
        // In einer echten Implementierung würde hier die Web Push API verwendet
        // Für jetzt erstellen wir einen Cache-Eintrag, den das Frontend abfragen kann
        
        $settings = CompanySetting::current();
        $duration = $settings->getGmailNotificationDuration();
        $soundEnabled = $settings->areGmailSoundNotificationsEnabled();

        $notificationPayload = [
            'type' => 'browser',
            'title' => $this->notificationData['title'],
            'message' => $this->notificationData['message'],
            'icon' => '/favicon.ico', // App-Icon
            'badge' => '/favicon.ico',
            'url' => $this->notificationData['url'] ?? null,
            'timestamp' => now()->toISOString(),
            'duration' => $duration,
            'sound' => $soundEnabled,
            'data' => [
                'email_id' => $this->notificationData['email_id'] ?? null,
                'gmail_id' => $this->notificationData['gmail_id'] ?? null,
                'sender' => $this->notificationData['sender'] ?? null,
                'subject' => $this->notificationData['subject'] ?? null,
            ]
        ];

        // Speichere Benachrichtigung für Frontend-Abfrage
        cache()->put(
            "browser_notification_{$this->user['id']}_" . time(),
            $notificationPayload,
            now()->addMinutes(5) // 5 Minuten Cache
        );

        Log::info('Browser notification cached for frontend', [
            'user_id' => $this->user['id'],
            'cache_key' => "browser_notification_{$this->user['id']}_" . time()
        ]);
    }

    /**
     * Sendet Mobile Push-Benachrichtigung (Simulation)
     */
    private function sendMobilePushNotification(): void
    {
        // Hier würde die echte Mobile Push Implementation stehen
        // z.B. mit Firebase Cloud Messaging:
        
        /*
        $fcm = new FCM($serverKey);
        $notification = new Notification(
            $this->notificationData['title'],
            $this->notificationData['message']
        );
        
        $data = [
            'email_id' => $this->notificationData['email_id'],
            'url' => $this->notificationData['url'],
            'type' => 'gmail_notification'
        ];
        
        $message = new Message();
        $message->setTo($userDeviceToken)
                ->setNotification($notification)
                ->setData($data);
                
        $response = $fcm->send($message);
        */

        // Für jetzt nur Logging
        Log::info('Mobile push notification simulated', [
            'user_id' => $this->user['id'],
            'payload' => [
                'title' => $this->notificationData['title'],
                'body' => $this->notificationData['message'],
                'data' => [
                    'email_id' => $this->notificationData['email_id'] ?? null,
                    'url' => $this->notificationData['url'] ?? null,
                    'type' => 'gmail_notification'
                ]
            ]
        ]);
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('SendGmailNotification job failed', [
            'user_id' => $this->user['id'],
            'type' => $this->type,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString()
        ]);

        // Optional: Benachrichtige Administratoren über fehlgeschlagene Jobs
        // Mail::to('admin@example.com')->send(new JobFailedMail($this, $exception));
    }

    /**
     * Determine the time at which the job should timeout.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(5); // Job nach 5 Minuten aufgeben
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return [1, 5, 10]; // Retry nach 1, 5, 10 Sekunden
    }

    /**
     * Get the tags that should be assigned to the job.
     */
    public function tags(): array
    {
        return [
            'gmail-notification',
            'user:' . $this->user['id'],
            'type:' . $this->type,
            'email:' . ($this->notificationData['email_id'] ?? 'unknown')
        ];
    }
}
