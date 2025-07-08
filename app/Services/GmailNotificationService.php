<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\User;
use App\Models\GmailEmail;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use App\Events\NewGmailReceived;
use App\Jobs\SendGmailNotification;

class GmailNotificationService
{
    private CompanySetting $settings;

    public function __construct()
    {
        $this->settings = CompanySetting::current();
    }

    /**
     * Verarbeitet eine neue Gmail-E-Mail für Benachrichtigungen
     */
    public function processNewEmail(GmailEmail $email): void
    {
        try {
            // Prüfe ob Benachrichtigungen aktiviert sind
            if (!$this->settings->areGmailNotificationsEnabled()) {
                return;
            }

            // Prüfe Zeitfenster
            if (!$this->settings->isGmailNotificationTimeActive()) {
                Log::info('Gmail notification skipped - outside time window', [
                    'email_id' => $email->id,
                    'subject' => $email->subject
                ]);
                return;
            }

            // Konvertiere E-Mail-Daten für Filter-Prüfung
            $emailData = $this->convertEmailToArray($email);

            // Prüfe Filter
            if (!$this->settings->doesEmailMatchNotificationFilters($emailData)) {
                Log::info('Gmail notification skipped - does not match filters', [
                    'email_id' => $email->id,
                    'subject' => $email->subject
                ]);
                return;
            }

            // Hole berechtigte Benutzer
            $users = $this->getEligibleUsers();

            if (empty($users)) {
                Log::info('Gmail notification skipped - no eligible users', [
                    'email_id' => $email->id
                ]);
                return;
            }

            // Sende Benachrichtigungen
            $this->sendNotifications($email, $users);

            // Aktualisiere Statistiken
            $this->settings->updateGmailNotificationLastSent();
            $this->settings->incrementGmailNotificationsSentCount();

            // Feuere Event
            event(new NewGmailReceived($email, $users));

        } catch (\Exception $e) {
            Log::error('Error processing Gmail notification', [
                'email_id' => $email->id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Konvertiert GmailEmail Model zu Array für Filter-Prüfung
     */
    private function convertEmailToArray(GmailEmail $email): array
    {
        return [
            'subject' => $email->subject,
            'from' => $email->from ?? [],
            'to' => $email->to ?? [],
            'cc' => $email->cc ?? [],
            'bcc' => $email->bcc ?? [],
            'is_important' => $email->is_important,
            'is_starred' => $email->is_starred,
            'has_attachments' => $email->has_attachments,
            'labels' => $email->labels ?? [],
            'snippet' => $email->snippet,
        ];
    }

    /**
     * Holt alle berechtigten Benutzer für Benachrichtigungen
     */
    private function getEligibleUsers(): array
    {
        $configuredUserIds = $this->settings->getGmailNotificationUsers();
        
        // Wenn keine spezifischen Benutzer konfiguriert sind, alle aktiven Benutzer
        if (empty($configuredUserIds)) {
            $users = User::active()->get();
        } else {
            $users = User::active()->whereIn('id', $configuredUserIds)->get();
        }

        // Filtere Benutzer die individuelle Benachrichtigungen deaktiviert haben
        return $users->filter(function ($user) {
            return $this->shouldUserReceiveNotification($user);
        })->toArray();
    }

    /**
     * Prüft ob ein Benutzer Benachrichtigungen erhalten soll
     */
    private function shouldUserReceiveNotification(User $user): bool
    {
        try {
            // Prüfe ob Benutzer grundsätzlich Benachrichtigungen aktiviert hat
            if (!$user->gmail_notifications_enabled) {
                return false;
            }

            // Prüfe Company-Setting
            if (!$this->settings->shouldUserReceiveGmailNotifications($user->id)) {
                return false;
            }

            return true;
        } catch (\Exception $e) {
            // Fallback bei Fehlern in der Spalten-Abfrage
            return true;
        }
    }

    /**
     * Sendet Benachrichtigungen an alle berechtigten Benutzer
     */
    public function sendNotifications(GmailEmail $email, array $users): void
    {
        $notificationTypes = $this->settings->getGmailNotificationTypes();
        $template = $this->settings->getGmailNotificationTemplate();
        
        foreach ($users as $user) {
            try {
                // Erstelle Benachrichtigungsinhalt
                $notificationData = $this->createNotificationData($email, $template);
                
                // Sende verschiedene Benachrichtigungstypen
                foreach ($notificationTypes as $type) {
                    $this->sendNotificationByType($user, $email, $notificationData, $type);
                }

                // Aktualisiere User-Statistiken
                $this->updateUserNotificationStats($user);

            } catch (\Exception $e) {
                Log::error('Error sending notification to user', [
                    'user_id' => $user['id'],
                    'email_id' => $email->id,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }

    /**
     * Erstellt Benachrichtigungsdaten basierend auf Template
     */
    private function createNotificationData(GmailEmail $email, string $template): array
    {
        // Extrahiere Sender-Information
        $sender = 'Unbekannt';
        if (!empty($email->from)) {
            $firstSender = is_array($email->from) ? $email->from[0] : $email->from;
            if (is_array($firstSender)) {
                $sender = $firstSender['name'] ?: $firstSender['email'];
            } else {
                $sender = $firstSender;
            }
        }

        // Template-Variablen ersetzen
        $message = str_replace([
            '{sender}',
            '{subject}',
            '{snippet}',
            '{time}',
            '{date}'
        ], [
            $sender,
            $email->subject ?: 'Kein Betreff',
            $email->snippet ?: '',
            now()->format('H:i'),
            now()->format('d.m.Y')
        ], $template);

        return [
            'title' => 'Neue Gmail E-Mail',
            'message' => $message,
            'sender' => $sender,
            'subject' => $email->subject,
            'snippet' => $email->snippet,
            'email_id' => $email->id,
            'gmail_id' => $email->gmail_id,
            'has_attachments' => $email->has_attachments,
            'is_important' => $email->is_important,
            'received_at' => $email->received_at,
            'url' => route('filament.admin.resources.gmail-emails.view', $email->id),
        ];
    }

    /**
     * Sendet Benachrichtigung nach Typ
     */
    private function sendNotificationByType(array $user, GmailEmail $email, array $data, string $type): void
    {
        switch ($type) {
            case 'browser':
                $this->sendBrowserNotification($user, $data);
                break;
                
            case 'in_app':
                $this->sendInAppNotification($user, $data);
                break;
                
            case 'email':
                $this->sendEmailNotification($user, $data);
                break;
                
            case 'push':
                $this->sendPushNotification($user, $data);
                break;
                
            default:
                Log::warning('Unknown notification type', ['type' => $type]);
        }
    }

    /**
     * Sendet Browser-Benachrichtigung (Web Push API)
     */
    private function sendBrowserNotification(array $user, array $data): void
    {
        try {
            // Prüfe User-Einstellungen
            $userModel = User::find($user['id']);
            if (!$userModel || !$userModel->gmail_browser_notifications) {
                return;
            }

            // Verwende Job für asynchrone Verarbeitung
            SendGmailNotification::dispatch($user, $data, 'browser')
                ->onQueue('notifications');

        } catch (\Exception $e) {
            Log::error('Error sending browser notification', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sendet In-App-Benachrichtigung
     */
    private function sendInAppNotification(array $user, array $data): void
    {
        try {
            // Erstelle In-App-Benachrichtigung in der Datenbank
            Notification::create([
                'user_id' => $user['id'],
                'type' => 'gmail_email',
                'title' => $data['title'],
                'message' => $data['message'],
                'icon' => 'heroicon-o-envelope',
                'color' => 'primary',
                'priority' => $data['is_important'] ? 'high' : 'normal',
                'action_url' => $data['url'],
                'action_text' => 'E-Mail anzeigen',
                'data' => [
                    'sender' => $data['sender'],
                    'subject' => $data['subject'],
                    'snippet' => $data['snippet'],
                    'email_id' => $data['email_id'],
                    'gmail_id' => $data['gmail_id'],
                    'has_attachments' => $data['has_attachments'],
                    'received_at' => $data['received_at'],
                ],
                'expires_at' => now()->addDays(30), // Benachrichtigung läuft nach 30 Tagen ab
            ]);

            // Verwende Laravel Broadcasting für Real-time Updates
            broadcast(new \App\Events\GmailNotificationReceived($user['id'], $data));

        } catch (\Exception $e) {
            Log::error('Error sending in-app notification', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sendet E-Mail-Benachrichtigung
     */
    private function sendEmailNotification(array $user, array $data): void
    {
        try {
            // Prüfe User-Einstellungen
            $userModel = User::find($user['id']);
            if (!$userModel || !$userModel->gmail_email_notifications) {
                return;
            }

            // Verwende Job für asynchrone Verarbeitung
            SendGmailNotification::dispatch($user, $data, 'email')
                ->onQueue('notifications');

        } catch (\Exception $e) {
            Log::error('Error sending email notification', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Sendet Push-Benachrichtigung (Mobile)
     */
    private function sendPushNotification(array $user, array $data): void
    {
        try {
            // Implementierung für Mobile Push Notifications
            // z.B. Firebase Cloud Messaging (FCM)
            
            Log::info('Push notification would be sent', [
                'user_id' => $user['id'],
                'message' => $data['message']
            ]);

        } catch (\Exception $e) {
            Log::error('Error sending push notification', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Aktualisiert Benutzer-Benachrichtigungsstatistiken
     */
    private function updateUserNotificationStats(array $user): void
    {
        try {
            $userModel = User::find($user['id']);
            if ($userModel) {
                $userModel->update([
                    'gmail_last_notification_at' => now(),
                    'gmail_notifications_received_count' => ($userModel->gmail_notifications_received_count ?? 0) + 1
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error updating user notification stats', [
                'user_id' => $user['id'],
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Testet das Benachrichtigungssystem
     */
    public function testNotifications(array $userIds = []): array
    {
        $results = [];
        
        try {
            // Erstelle Test-E-Mail-Daten
            $testEmailData = [
                'subject' => 'Test-Benachrichtigung',
                'from' => [['name' => 'Test Sender', 'email' => 'test@example.com']],
                'snippet' => 'Dies ist eine Test-Benachrichtigung für das Gmail-System.',
                'is_important' => false,
                'has_attachments' => false,
            ];

            // Hole Benutzer
            if (empty($userIds)) {
                $users = $this->getEligibleUsers();
            } else {
                $users = User::active()->whereIn('id', $userIds)->get()->toArray();
            }

            $notificationTypes = $this->settings->getGmailNotificationTypes();
            $template = $this->settings->getGmailNotificationTemplate();

            // Erstelle Test-E-Mail
            $testEmail = new GmailEmail([
                'gmail_id' => 'test_' . time(),
                'subject' => $testEmailData['subject'],
                'from' => $testEmailData['from'],
                'snippet' => $testEmailData['snippet'],
                'is_important' => false,
                'has_attachments' => false,
                'received_at' => now(),
            ]);

            $notificationData = $this->createNotificationData($testEmail, $template);

            foreach ($users as $user) {
                $userResults = [];
                
                foreach ($notificationTypes as $type) {
                    try {
                        $this->sendNotificationByType($user, $testEmail, $notificationData, $type);
                        $userResults[$type] = 'success';
                    } catch (\Exception $e) {
                        $userResults[$type] = 'error: ' . $e->getMessage();
                    }
                }
                
                $results[$user['id']] = [
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'notifications' => $userResults
                ];
            }

        } catch (\Exception $e) {
            $results['error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Gibt Statistiken über Benachrichtigungen zurück
     */
    public function getNotificationStats(): array
    {
        return [
            'system' => $this->settings->getGmailNotificationStatus(),
            'users' => User::active()
                ->select('id', 'name', 'email', 'gmail_notifications_enabled', 
                        'gmail_last_notification_at', 'gmail_notifications_received_count')
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'enabled' => $user->gmail_notifications_enabled ?? true,
                        'last_notification' => $user->gmail_last_notification_at?->format('d.m.Y H:i:s'),
                        'received_count' => $user->gmail_notifications_received_count ?? 0,
                    ];
                })
                ->toArray(),
        ];
    }

    /**
     * Aktiviert/Deaktiviert Benachrichtigungen für einen Benutzer
     */
    public function toggleUserNotifications(int $userId, bool $enabled): bool
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return false;
            }

            $user->update(['gmail_notifications_enabled' => $enabled]);
            return true;

        } catch (\Exception $e) {
            Log::error('Error toggling user notifications', [
                'user_id' => $userId,
                'enabled' => $enabled,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Setzt Benutzer-Benachrichtigungseinstellungen
     */
    public function setUserNotificationPreferences(int $userId, array $preferences): bool
    {
        try {
            $user = User::find($userId);
            if (!$user) {
                return false;
            }

            $updateData = [];
            
            if (isset($preferences['enabled'])) {
                $updateData['gmail_notifications_enabled'] = (bool) $preferences['enabled'];
            }
            
            if (isset($preferences['browser'])) {
                $updateData['gmail_browser_notifications'] = (bool) $preferences['browser'];
            }
            
            if (isset($preferences['email'])) {
                $updateData['gmail_email_notifications'] = (bool) $preferences['email'];
            }
            
            if (isset($preferences['sound'])) {
                $updateData['gmail_sound_notifications'] = (bool) $preferences['sound'];
            }
            
            if (isset($preferences['preferences'])) {
                $updateData['gmail_notification_preferences'] = $preferences['preferences'];
            }

            if (!empty($updateData)) {
                $user->update($updateData);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Error setting user notification preferences', [
                'user_id' => $userId,
                'preferences' => $preferences,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
}
