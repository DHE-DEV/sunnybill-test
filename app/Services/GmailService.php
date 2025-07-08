<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\GmailEmail;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GmailService
{
    private CompanySetting $settings;
    private string $baseUrl = 'https://www.googleapis.com/gmail/v1';
    private string $oauthUrl = 'https://oauth2.googleapis.com/token';

    public function __construct()
    {
        $this->settings = CompanySetting::current();
    }

    /**
     * Prüft ob die Gmail-Integration konfiguriert ist
     */
    public function isConfigured(): bool
    {
        return $this->settings->hasValidGmailConfig();
    }

    /**
     * Generiert die OAuth2 Authorization URL
     */
    public function getAuthorizationUrl(string $redirectUri): string
    {
        $params = [
            'client_id' => $this->settings->getGmailClientId(),
            'redirect_uri' => $redirectUri,
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
        ];

        return 'https://accounts.google.com/o/oauth2/auth?' . http_build_query($params);
    }

    /**
     * Tauscht den Authorization Code gegen Access Token
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        $response = Http::post($this->oauthUrl, [
            'client_id' => $this->settings->getGmailClientId(),
            'client_secret' => $this->settings->getGmailClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to exchange code for tokens: ' . $response->body());
        }

        $tokens = $response->json();
        
        // E-Mail-Adresse abrufen
        $emailAddress = $this->getUserEmailAddress($tokens['access_token']);
        
        // Tokens speichern
        $this->settings->saveGmailTokens($tokens, $emailAddress);
        
        return $tokens;
    }

    /**
     * Erneuert den Access Token mit dem Refresh Token
     */
    public function refreshAccessToken(): string
    {
        $refreshToken = $this->settings->getGmailRefreshToken();
        
        if (!$refreshToken) {
            throw new \Exception('No refresh token available');
        }

        $response = Http::post($this->oauthUrl, [
            'client_id' => $this->settings->getGmailClientId(),
            'client_secret' => $this->settings->getGmailClientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);

        if (!$response->successful()) {
            throw new \Exception('Failed to refresh access token: ' . $response->body());
        }

        $tokens = $response->json();
        $this->settings->updateGmailAccessToken($tokens['access_token'], $tokens['expires_in']);
        
        return $tokens['access_token'];
    }

    /**
     * Gibt einen gültigen Access Token zurück (erneuert wenn nötig)
     */
    private function getValidAccessToken(): string
    {
        if ($this->settings->isGmailTokenExpired()) {
            return $this->refreshAccessToken();
        }

        return $this->settings->getGmailAccessToken();
    }

    /**
     * Ruft die E-Mail-Adresse des Benutzers ab
     */
    private function getUserEmailAddress(string $accessToken): string
    {
        $response = Http::withToken($accessToken)
            ->get($this->baseUrl . '/users/me/profile');

        if (!$response->successful()) {
            throw new \Exception('Failed to get user email address: ' . $response->body());
        }

        return $response->json()['emailAddress'];
    }

    /**
     * Führt eine authentifizierte Gmail API-Anfrage aus
     */
    private function makeApiRequest(string $endpoint, array $params = []): array
    {
        $accessToken = $this->getValidAccessToken();
        $url = $this->baseUrl . '/users/me' . $endpoint;

        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $response = Http::withToken($accessToken)->get($url);

        if (!$response->successful()) {
            $error = 'Gmail API request failed: ' . $response->body();
            $this->settings->setGmailLastError($error);
            throw new \Exception($error);
        }

        return $response->json();
    }

    /**
     * Führt eine authentifizierte Gmail API-POST-Anfrage aus
     */
    private function makeApiPostRequest(string $endpoint, array $data = []): array
    {
        $accessToken = $this->getValidAccessToken();
        $url = $this->baseUrl . '/users/me' . $endpoint;

        $response = Http::withToken($accessToken)->post($url, $data);

        if (!$response->successful()) {
            $error = 'Gmail API POST request failed: ' . $response->body();
            $this->settings->setGmailLastError($error);
            throw new \Exception($error);
        }

        return $response->json();
    }

    /**
     * Ruft alle Labels ab
     */
    public function getLabels(): array
    {
        try {
            $response = $this->makeApiRequest('/labels');
            return $response['labels'] ?? [];
        } catch (\Exception $e) {
            Log::error('Failed to get Gmail labels: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Ruft E-Mails ab
     */
    public function getMessages(array $options = []): array
    {
        $params = [
            'maxResults' => $options['maxResults'] ?? $this->settings->getGmailMaxResults(),
        ];

        if (isset($options['q'])) {
            $params['q'] = $options['q'];
        }

        if (isset($options['labelIds'])) {
            $params['labelIds'] = $options['labelIds'];
        }

        if (isset($options['pageToken'])) {
            $params['pageToken'] = $options['pageToken'];
        }

        try {
            $response = $this->makeApiRequest('/messages', $params);
            return $response;
        } catch (\Exception $e) {
            Log::error('Failed to get Gmail messages: ' . $e->getMessage());
            return ['messages' => [], 'resultSizeEstimate' => 0];
        }
    }

    /**
     * Ruft eine einzelne E-Mail ab
     */
    public function getMessage(string $messageId): ?array
    {
        try {
            return $this->makeApiRequest("/messages/{$messageId}");
        } catch (\Exception $e) {
            Log::error("Failed to get Gmail message {$messageId}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Synchronisiert E-Mails mit der lokalen Datenbank
     */
    public function syncEmails(array $options = []): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Gmail is not configured');
        }

        $stats = [
            'processed' => 0,
            'new' => 0,
            'updated' => 0,
            'errors' => 0,
        ];

        try {
            // E-Mail-Liste abrufen
            $messagesResponse = $this->getMessages($options);
            $messages = $messagesResponse['messages'] ?? [];

            foreach ($messages as $messageInfo) {
                try {
                    $messageId = $messageInfo['id'];
                    
                    // Prüfen ob E-Mail bereits existiert
                    $existingEmail = GmailEmail::findByGmailId($messageId);
                    
                    // Vollständige E-Mail-Daten abrufen
                    $messageData = $this->getMessage($messageId);
                    
                    if (!$messageData) {
                        $stats['errors']++;
                        continue;
                    }

                    // E-Mail-Daten parsen
                    $emailData = $this->parseEmailData($messageData);
                    
                    // E-Mail erstellen oder aktualisieren
                    if ($existingEmail) {
                        $existingEmail->update($emailData);
                        $stats['updated']++;
                    } else {
                        GmailEmail::create($emailData);
                        $stats['new']++;
                    }

                    // Anhänge herunterladen wenn aktiviert
                    if ($this->settings->shouldDownloadGmailAttachments() && $emailData['has_attachments']) {
                        $this->downloadAttachments($messageId, $emailData['attachments'] ?? []);
                    }

                    $stats['processed']++;

                } catch (\Exception $e) {
                    Log::error("Error processing Gmail message {$messageId}: " . $e->getMessage());
                    $stats['errors']++;
                }
            }

            // Statistiken aktualisieren
            $totalEmails = GmailEmail::count();
            $unreadEmails = GmailEmail::unread()->count();
            $this->settings->updateGmailStats($totalEmails, $unreadEmails);
            $this->settings->updateGmailLastSync();
            $this->settings->setGmailLastError(null);

        } catch (\Exception $e) {
            $this->settings->setGmailLastError($e->getMessage());
            throw $e;
        }

        return $stats;
    }

    /**
     * Parst Gmail-Nachrichtendaten in unser Format
     */
    private function parseEmailData(array $messageData): array
    {
        $payload = $messageData['payload'] ?? [];
        $headers = $this->extractHeaders($payload['headers'] ?? []);

        return [
            'gmail_id' => $messageData['id'],
            'thread_id' => $messageData['threadId'] ?? null,
            'subject' => $headers['Subject'] ?? null,
            'snippet' => $messageData['snippet'] ?? null,
            'from' => $this->parseEmailAddresses($headers['From'] ?? ''),
            'to' => $this->parseEmailAddresses($headers['To'] ?? ''),
            'cc' => $this->parseEmailAddresses($headers['Cc'] ?? ''),
            'bcc' => $this->parseEmailAddresses($headers['Bcc'] ?? ''),
            'body_text' => $this->extractTextBody($payload),
            'body_html' => $this->extractHtmlBody($payload),
            'labels' => $messageData['labelIds'] ?? [],
            'is_read' => !in_array('UNREAD', $messageData['labelIds'] ?? []),
            'is_starred' => in_array('STARRED', $messageData['labelIds'] ?? []),
            'is_important' => in_array('IMPORTANT', $messageData['labelIds'] ?? []),
            'is_draft' => in_array('DRAFT', $messageData['labelIds'] ?? []),
            'is_sent' => in_array('SENT', $messageData['labelIds'] ?? []),
            'is_trash' => in_array('TRASH', $messageData['labelIds'] ?? []),
            'is_spam' => in_array('SPAM', $messageData['labelIds'] ?? []),
            'has_attachments' => $this->hasAttachments($payload),
            'attachment_count' => $this->countAttachments($payload),
            'attachments' => $this->extractAttachmentInfo($payload),
            'gmail_date' => $this->parseGmailDate($headers['Date'] ?? null),
            'received_at' => now(),
            'raw_headers' => $headers,
            'message_id_header' => $headers['Message-ID'] ?? null,
            'in_reply_to' => $headers['In-Reply-To'] ?? null,
            'references' => $headers['References'] ?? null,
            'size_estimate' => $messageData['sizeEstimate'] ?? null,
            'payload' => $payload,
        ];
    }

    /**
     * Extrahiert Header-Informationen
     */
    private function extractHeaders(array $headers): array
    {
        $result = [];
        foreach ($headers as $header) {
            $result[$header['name']] = $header['value'];
        }
        return $result;
    }

    /**
     * Parst E-Mail-Adressen
     */
    private function parseEmailAddresses(string $addressString): array
    {
        if (empty($addressString)) {
            return [];
        }

        $addresses = [];
        $parts = explode(',', $addressString);

        foreach ($parts as $part) {
            $part = trim($part);
            if (preg_match('/^(.+?)\s*<(.+?)>$/', $part, $matches)) {
                $addresses[] = [
                    'name' => trim($matches[1], '"'),
                    'email' => $matches[2],
                ];
            } else {
                $addresses[] = [
                    'name' => '',
                    'email' => $part,
                ];
            }
        }

        return $addresses;
    }

    /**
     * Extrahiert Text-Body
     */
    private function extractTextBody(array $payload): ?string
    {
        return $this->extractBodyByMimeType($payload, 'text/plain');
    }

    /**
     * Extrahiert HTML-Body
     */
    private function extractHtmlBody(array $payload): ?string
    {
        return $this->extractBodyByMimeType($payload, 'text/html');
    }

    /**
     * Extrahiert Body nach MIME-Type
     */
    private function extractBodyByMimeType(array $payload, string $mimeType): ?string
    {
        if (isset($payload['mimeType']) && $payload['mimeType'] === $mimeType) {
            return $this->decodeBody($payload['body']['data'] ?? '');
        }

        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                $body = $this->extractBodyByMimeType($part, $mimeType);
                if ($body) {
                    return $body;
                }
            }
        }

        return null;
    }

    /**
     * Dekodiert Gmail Body-Daten
     */
    private function decodeBody(string $data): string
    {
        return base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    }

    /**
     * Prüft ob E-Mail Anhänge hat
     */
    private function hasAttachments(array $payload): bool
    {
        return $this->countAttachments($payload) > 0;
    }

    /**
     * Zählt Anhänge
     */
    private function countAttachments(array $payload): int
    {
        $count = 0;

        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if (isset($part['filename']) && !empty($part['filename'])) {
                    $count++;
                }
                $count += $this->countAttachments($part);
            }
        }

        return $count;
    }

    /**
     * Extrahiert Anhang-Informationen
     */
    private function extractAttachmentInfo(array $payload): array
    {
        $attachments = [];

        if (isset($payload['parts'])) {
            foreach ($payload['parts'] as $part) {
                if (isset($part['filename']) && !empty($part['filename'])) {
                    $attachments[] = [
                        'id' => $part['body']['attachmentId'] ?? null,
                        'filename' => $part['filename'],
                        'mimeType' => $part['mimeType'] ?? null,
                        'size' => $part['body']['size'] ?? null,
                    ];
                }
                $attachments = array_merge($attachments, $this->extractAttachmentInfo($part));
            }
        }

        return $attachments;
    }

    /**
     * Parst Gmail-Datum
     */
    private function parseGmailDate(?string $dateString): ?Carbon
    {
        if (!$dateString) {
            return null;
        }

        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Lädt Anhänge herunter
     */
    public function downloadAttachments(string $messageId, array $attachments): array
    {
        $downloadedFiles = [];

        foreach ($attachments as $attachment) {
            if (!isset($attachment['id'])) {
                continue;
            }

            try {
                $attachmentData = $this->makeApiRequest("/messages/{$messageId}/attachments/{$attachment['id']}");
                
                if (isset($attachmentData['data'])) {
                    $fileContent = $this->decodeBody($attachmentData['data']);
                    $filePath = $this->saveAttachment($messageId, $attachment, $fileContent);
                    
                    if ($filePath) {
                        $downloadedFiles[] = $filePath;
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to download attachment {$attachment['id']}: " . $e->getMessage());
            }
        }

        return $downloadedFiles;
    }

    /**
     * Speichert einen Anhang
     */
    private function saveAttachment(string $messageId, array $attachment, string $content): ?string
    {
        $attachmentPath = $this->settings->getGmailAttachmentPath();
        $datePath = date('Y/m/d');
        $fullPath = "{$attachmentPath}/{$datePath}/{$messageId}";
        
        $filename = $attachment['filename'] ?? "attachment_{$attachment['id']}";
        $filePath = "{$fullPath}/{$filename}";
        
        try {
            Storage::put($filePath, $content);
            return $filePath;
        } catch (\Exception $e) {
            Log::error("Failed to save attachment {$filename}: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Markiert eine E-Mail als gelesen
     */
    public function markAsRead(string $messageId): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/modify", [
                'removeLabelIds' => ['UNREAD']
            ]);
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                $email->markAsRead();
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark message {$messageId} as read: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Markiert eine E-Mail als ungelesen
     */
    public function markAsUnread(string $messageId): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/modify", [
                'addLabelIds' => ['UNREAD']
            ]);
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                $email->markAsUnread();
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to mark message {$messageId} as unread: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Fügt Labels zu einer E-Mail hinzu
     */
    public function addLabels(string $messageId, array $labelIds): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/modify", [
                'addLabelIds' => $labelIds
            ]);
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                foreach ($labelIds as $labelId) {
                    $email->addLabel($labelId);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to add labels to message {$messageId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Entfernt Labels von einer E-Mail
     */
    public function removeLabels(string $messageId, array $labelIds): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/modify", [
                'removeLabelIds' => $labelIds
            ]);
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                foreach ($labelIds as $labelId) {
                    $email->removeLabel($labelId);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to remove labels from message {$messageId}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verschiebt eine E-Mail in den Papierkorb
     */
    public function moveToTrash(string $messageId): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/trash");
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                $email->moveToTrash();
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to move message {$messageId} to trash: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Stellt eine E-Mail aus dem Papierkorb wieder her
     */
    public function restoreFromTrash(string $messageId): bool
    {
        try {
            $this->makeApiPostRequest("/messages/{$messageId}/untrash");
            
            // Lokale Datenbank aktualisieren
            $email = GmailEmail::findByGmailId($messageId);
            if ($email) {
                $email->restoreFromTrash();
            }
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to restore message {$messageId} from trash: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Testet die Gmail-Verbindung
     */
    public function testConnection(): array
    {
        try {
            $profile = $this->makeApiRequest('/profile');
            
            return [
                'success' => true,
                'email' => $profile['emailAddress'],
                'messages_total' => $profile['messagesTotal'] ?? 0,
                'threads_total' => $profile['threadsTotal'] ?? 0,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Gibt Statistiken zurück
     */
    public function getStats(): array
    {
        $localStats = GmailEmail::getStats();
        $configStatus = $this->settings->getGmailConfigStatus();
        
        return array_merge($localStats, [
            'config' => $configStatus,
            'last_sync' => $this->settings->getGmailLastSync(),
            'last_error' => $this->settings->getGmailLastError(),
        ]);
    }
}
