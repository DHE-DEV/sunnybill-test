<?php

namespace App\Services;

use App\Models\Router;
use App\Notifications\RouterOfflineNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

class RouterNotificationService
{
    /**
     * Parse email addresses from configuration string
     *
     * @param string $emailString
     * @return array
     */
    private function parseEmailAddresses(string $emailString): array
    {
        if (empty($emailString)) {
            return [];
        }

        // Split by comma and clean up
        $emails = array_map('trim', explode(',', $emailString));
        
        // Filter out empty values and validate email format
        return array_filter($emails, function ($email) {
            return !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL);
        });
    }

    /**
     * Get all configured email addresses
     *
     * @return array
     */
    public function getEmailConfiguration(): array
    {
        $config = config('router-notifications.emails');
        
        return [
            'to' => $this->parseEmailAddresses($config['to'] ?? ''),
            'cc' => $this->parseEmailAddresses($config['cc'] ?? ''),
            'bcc' => $this->parseEmailAddresses($config['bcc'] ?? ''),
        ];
    }

    /**
     * Check if notifications are enabled
     *
     * @return bool
     */
    public function isNotificationEnabled(): bool
    {
        return config('router-notifications.enabled', true);
    }

    /**
     * Check if a specific notification type is enabled
     *
     * @param string $type ('offline', 'online', 'delayed')
     * @return bool
     */
    public function isNotificationTypeEnabled(string $type): bool
    {
        return config("router-notifications.send_{$type}_notifications", true);
    }

    /**
     * Send router status notification
     *
     * @param Router $router
     * @param string $statusChange
     * @return bool
     */
    public function sendStatusNotification(Router $router, string $statusChange): bool
    {
        if (!$this->isNotificationEnabled()) {
            Log::info('Router notifications are disabled', [
                'router_id' => $router->id,
                'status_change' => $statusChange
            ]);
            return false;
        }

        // Determine notification type
        $notificationType = $this->getNotificationType($statusChange);
        
        if (!$this->isNotificationTypeEnabled($notificationType)) {
            Log::info("Router {$notificationType} notifications are disabled", [
                'router_id' => $router->id,
                'status_change' => $statusChange
            ]);
            return false;
        }

        $emails = $this->getEmailConfiguration();
        
        // Check if we have any recipients
        if (empty($emails['to'])) {
            Log::warning('No email recipients configured for router notifications', [
                'router_id' => $router->id,
                'status_change' => $statusChange
            ]);
            return false;
        }

        try {
            $notification = new RouterOfflineNotification($router, $statusChange);
            
            // Send to main recipients
            foreach ($emails['to'] as $email) {
                Notification::route('mail', $email)->notify($notification);
            }

            // If we have CC or BCC, we need to use Mail facade for more control
            if (!empty($emails['cc']) || !empty($emails['bcc'])) {
                $this->sendWithCcBcc($router, $statusChange, $emails);
            }

            Log::info('Router status notification sent successfully', [
                'router_id' => $router->id,
                'router_name' => $router->name,
                'status_change' => $statusChange,
                'recipients' => count($emails['to']),
                'cc_count' => count($emails['cc']),
                'bcc_count' => count($emails['bcc'])
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to send router status notification', [
                'router_id' => $router->id,
                'status_change' => $statusChange,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return false;
        }
    }

    /**
     * Send email with CC and BCC support
     *
     * @param Router $router
     * @param string $statusChange
     * @param array $emails
     * @return void
     */
    private function sendWithCcBcc(Router $router, string $statusChange, array $emails): void
    {
        $notification = new RouterOfflineNotification($router, $statusChange);
        $mailMessage = $notification->toMail((object) ['email' => $emails['to'][0] ?? '']);

        Mail::send([], [], function ($message) use ($mailMessage, $emails) {
            $message->to($emails['to']);
            
            if (!empty($emails['cc'])) {
                $message->cc($emails['cc']);
            }
            
            if (!empty($emails['bcc'])) {
                $message->bcc($emails['bcc']);
            }

            $message->subject($mailMessage->subject ?? 'Router Status Update');
            $message->from(
                config('router-notifications.from_email') ?? config('mail.from.address'),
                config('router-notifications.from_name') ?? config('mail.from.name')
            );

            // Convert MailMessage to HTML
            $message->html($this->renderMailMessage($mailMessage));
        });
    }

    /**
     * Render MailMessage to HTML
     *
     * @param \Illuminate\Notifications\Messages\MailMessage $mailMessage
     * @return string
     */
    private function renderMailMessage($mailMessage): string
    {
        // Simple HTML rendering - in production you might want to use a proper template
        $html = '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;">';
        
        if ($mailMessage->greeting) {
            $html .= '<h2>' . $mailMessage->greeting . '</h2>';
        }

        foreach ($mailMessage->introLines as $line) {
            $html .= '<p>' . $line . '</p>';
        }

        if ($mailMessage->actionText && $mailMessage->actionUrl) {
            $html .= '<p><a href="' . $mailMessage->actionUrl . '" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">' . $mailMessage->actionText . '</a></p>';
        }

        foreach ($mailMessage->outroLines as $line) {
            $html .= '<p>' . $line . '</p>';
        }

        if ($mailMessage->salutation) {
            $html .= '<p><br>' . $mailMessage->salutation . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Determine notification type from status change
     *
     * @param string $statusChange
     * @return string
     */
    private function getNotificationType(string $statusChange): string
    {
        if (str_contains($statusChange, '→ offline')) {
            return 'offline';
        } elseif (str_contains($statusChange, '→ online')) {
            return 'online';
        } elseif (str_contains($statusChange, '→ delayed')) {
            return 'delayed';
        }

        return 'offline'; // Default fallback
    }

    /**
     * Get notification summary for admin interface
     *
     * @return array
     */
    public function getNotificationSummary(): array
    {
        $emails = $this->getEmailConfiguration();
        
        return [
            'enabled' => $this->isNotificationEnabled(),
            'total_recipients' => count($emails['to']) + count($emails['cc']) + count($emails['bcc']),
            'to_count' => count($emails['to']),
            'cc_count' => count($emails['cc']),
            'bcc_count' => count($emails['bcc']),
            'notification_types' => [
                'offline' => $this->isNotificationTypeEnabled('offline'),
                'online' => $this->isNotificationTypeEnabled('online'),
                'delayed' => $this->isNotificationTypeEnabled('delayed'),
            ]
        ];
    }
}