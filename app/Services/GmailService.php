<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\GmailEmail;
use App\Models\GmailLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class GmailService
{
    private CompanySetting $settings;
    private string $baseUrl = 'https://www.googleapis.com/gmail/v1';
    private string $oauthUrl = 'https://oauth2.googleapis.com/token';

    public function __construct(?CompanySetting $company = null)
    {
        $this->settings = $company ?? CompanySetting::current();
    }

    /**
     * Prüft ob Gmail konfiguriert ist
     */
    public function isConfigured(): bool
    {
        return $this->settings->isGmailEnabled() && 
               $this->settings->getGmailClientId() && 
               $this->settings->getGmailClientSecret();
    }
    
    /**
     * Erstellt die OAuth2-Autorisierungs-URL
     */
    public function getAuthorizationUrl(string $redirectUri): string
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Gmail ist nicht konfiguriert. Bitte konfigurieren Sie Client ID und Client Secret.');
        }
        
        $params = [
            'client_id' => $this->settings->getGmailClientId(),
            'redirect_uri' => $redirectUri,
            'scope' => 'https://www.googleapis.com/auth/gmail.readonly https://www.googleapis.com/auth/gmail.modify https://www.googleapis.com/auth/userinfo.email',
            'response_type' => 'code',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => csrf_token(),
        ];
        
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }
    
    /**
     * Tauscht den Autorisierungscode gegen Access- und Refresh-Tokens
     */
    public function exchangeCodeForTokens(string $code, string $redirectUri): array
    {
        if (!$this->isConfigured()) {
            throw new \Exception('Gmail ist nicht konfiguriert.');
        }
        
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->settings->getGmailClientId(),
            'client_secret' => $this->settings->getGmailClientSecret(),
            'code' => $code,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $redirectUri,
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Fehler beim Token-Austausch: ' . $response->body());
        }
        
        $tokens = $response->json();
        
        // Speichere Tokens in den Einstellungen
        $this->settings->setGmailTokens(
            $tokens['access_token'],
            $tokens['refresh_token'] ?? null,
            now()->addSeconds($tokens['expires_in'])
        );
        
        // Hole E-Mail-Adresse des verbundenen Kontos
        try {
            $userInfo = $this->getUserInfo($tokens['access_token']);
            $this->settings->gmail_email_address = $userInfo['email'];
            $this->settings->save();
        } catch (\Exception $e) {
            \Log::warning('Konnte E-Mail-Adresse nicht abrufen: ' . $e->getMessage());
        }
        
        return $tokens;
    }
    
    /**
     * Erneuert den Access Token mit dem Refresh Token
     */
    public function refreshAccessToken(): string
    {
        $refreshToken = $this->settings->getGmailRefreshToken();
        
        if (!$refreshToken) {
            throw new \Exception('Kein Refresh Token verfügbar. Bitte autorisieren Sie Gmail erneut.');
        }
        
        $response = Http::post('https://oauth2.googleapis.com/token', [
            'client_id' => $this->settings->getGmailClientId(),
            'client_secret' => $this->settings->getGmailClientSecret(),
            'refresh_token' => $refreshToken,
            'grant_type' => 'refresh_token',
        ]);
        
        if (!$response->successful()) {
            throw new \Exception('Fehler beim Token-Refresh: ' . $response->body());
        }
        
        $tokens = $response->json();
        
        // Speichere neuen Access Token
        $this->settings->setGmailTokens(
            $tokens['access_token'],
            $tokens['refresh_token'] ?? $refreshToken, // Behalte alten Refresh Token falls keiner zurückgegeben wird
            now()->addSeconds($tokens['expires_in'])
        );
        
        return $tokens['access_token'];
    }
    
    /**
     * Holt einen gültigen Access Token (erneuert automatisch falls nötig)
     */
    public function getValidAccessToken(): string
    {
        $accessToken = $this->settings->getGmailAccessToken();
        $expiresAt = $this->settings->getGmailTokenExpiresAt();
        
        // Prüfe ob Token noch gültig ist (mit 5 Minuten Puffer)
        if ($accessToken && $expiresAt && $expiresAt->gt(now()->addMinutes(5))) {
            return $accessToken;
        }
        
        // Token ist abgelaufen oder nicht vorhanden, erneuere ihn
        return $this->refreshAccessToken();
    }
    
    /**
     * Holt Benutzerinformationen von Google
     */
    public function getUserInfo(string $accessToken = null): array
    {
        if (!$accessToken) {
            $accessToken = $this->getValidAccessToken();
        }
        
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->get('https://www.googleapis.com/oauth2/v2/userinfo');
        
        if (!$response->successful()) {
            throw new \Exception('Fehler beim Abrufen der Benutzerinformationen: ' . $response->body());
        }
        
        return $response->json();
    }
    
    /**
     * Testet die Gmail-Verbindung
     */
    public function testConnection(): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Gmail ist nicht konfiguriert.'
                ];
            }
            
            if (!$this->settings->getGmailRefreshToken()) {
                return [
                    'success' => false,
                    'error' => 'Keine Autorisierung vorhanden. Bitte autorisieren Sie Gmail zuerst.'
                ];
            }
            
            $userInfo = $this->getUserInfo();
            
            return [
                'success' => true,
                'email' => $userInfo['email'],
                'name' => $userInfo['name'] ?? null,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Sendet eine E-Mail über Gmail
     */
    public function sendEmail(string $to, string $subject, string $body, array $options = []): array
    {
        try {
            if (!$this->isConfigured()) {
                return [
                    'success' => false,
                    'error' => 'Gmail ist nicht konfiguriert.'
                ];
            }
            
            if (!$this->settings->getGmailRefreshToken()) {
                return [
                    'success' => false,
                    'error' => 'Keine Autorisierung vorhanden. Bitte autorisieren Sie Gmail zuerst.'
                ];
            }

            // Hole die E-Mail-Adresse des verbundenen Kontos
            $fromEmail = $this->settings->gmail_email_address;
            if (!$fromEmail) {
                $userInfo = $this->getUserInfo();
                $fromEmail = $userInfo['email'];
            }

            // Standardmäßig HTML aktivieren für Task-Notizen
            $options['html'] = $options['html'] ?? true;

            // Erstelle die E-Mail im RFC 2822 Format
            $emailContent = $this->createEmailMessage($fromEmail, $to, $subject, $body, $options);
            
            // Base64url encode der E-Mail
            $encodedEmail = rtrim(strtr(base64_encode($emailContent), '+/', '-_'), '=');
            
            // Sende die E-Mail über Gmail API
            $response = $this->makeApiPostRequest('/messages/send', [
                'raw' => $encodedEmail
            ]);
            
            Log::info('Gmail: E-Mail gesendet', [
                'to' => $to,
                'subject' => $subject,
                'html' => $options['html'] ?? false,
                'message_id' => $response['id'] ?? null,
                'thread_id' => $response['threadId'] ?? null
            ]);
            
            return [
                'success' => true,
                'message_id' => $response['id'] ?? null,
                'thread_id' => $response['threadId'] ?? null
            ];
            
        } catch (\Exception $e) {
            Log::error('Gmail: E-Mail senden fehlgeschlagen', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Erstellt eine E-Mail-Nachricht im RFC 2822 Format
     */
    private function createEmailMessage(string $from, string $to, string $subject, string $body, array $options = []): string
    {
        $cc = $options['cc'] ?? null;
        $bcc = $options['bcc'] ?? null;
        $isHtml = $options['html'] ?? false;
        
        // UTF-8 Kodierung für den Betreff (RFC 2047)
        $encodedSubject = $this->encodeSubject($subject);
        
        $headers = [];
        $headers[] = "From: {$from}";
        $headers[] = "To: {$to}";
        
        if ($cc) {
            $headers[] = "Cc: {$cc}";
        }
        
        if ($bcc) {
            $headers[] = "Bcc: {$bcc}";
        }
        
        $headers[] = "Subject: {$encodedSubject}";
        $headers[] = "Date: " . date('r');
        $headers[] = "Message-ID: <" . uniqid() . "@" . parse_url($from, PHP_URL_HOST) . ">";
        $headers[] = "MIME-Version: 1.0";
        
        if ($isHtml) {
            $headers[] = "Content-Type: text/html; charset=UTF-8";
        } else {
            $headers[] = "Content-Type: text/plain; charset=UTF-8";
        }
        
        $headers[] = "Content-Transfer-Encoding: 8bit";
        
        // Kombiniere Headers und Body (ohne zusätzliche Kodierung)
        $email = implode("\r\n", $headers) . "\r\n\r\n" . $body;
        
        return $email;
    }

    /**
     * Kodiert den E-Mail-Betreff für UTF-8 Zeichen (RFC 2047)
     */
    private function encodeSubject(string $subject): string
    {
        // Prüfe ob der Betreff Non-ASCII Zeichen enthält
        if (mb_check_encoding($subject, 'ASCII')) {
            return $subject;
        }
        
        // Kodiere den Betreff mit RFC 2047 (quoted-printable)
        return '=?UTF-8?B?' . base64_encode($subject) . '?=';
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
            
            // Prüfe auf Rate Limit (429) und protokolliere es
            if ($response->status() === 429) {
                $this->handleRateLimit($response, $endpoint);
                // Für Rate Limits werfen wir eine spezielle Exception
                throw new \Exception('RATE_LIMIT_EXCEEDED: ' . $error);
            }
            
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
            // Filter anwenden wenn aktiviert
            if ($this->settings->gmail_filter_inbox && !isset($options['q'])) {
                $options['q'] = '-in:inbox';
            }
            
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
                    
                    // Detailliertes Logging für E-Mail Labels
                    $this->logEmailLabels($messageId, $emailData);
                    
                    // Datenbank-Logging wenn aktiviert
                    if ($this->settings->gmail_logging_enabled) {
                        $this->createGmailLog($emailData, $existingEmail ? 'updated' : 'created');
                    }
                    
                    // E-Mail erstellen oder aktualisieren
                    if ($existingEmail) {
                        $existingEmail->update($emailData);
                        $stats['updated']++;
                        Log::info("Gmail: Updated email", [
                            'gmail_id' => $messageId,
                            'subject' => $emailData['subject'],
                            'labels' => $emailData['labels']
                        ]);
                    } else {
                        GmailEmail::create($emailData);
                        $stats['new']++;
                        Log::info("Gmail: Created new email", [
                            'gmail_id' => $messageId,
                            'subject' => $emailData['subject'],
                            'labels' => $emailData['labels']
                        ]);
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
     * Lädt einen einzelnen Anhang herunter
     */
    public function downloadAttachment(string $messageId, string $attachmentId): ?string
    {
        \Log::info('Gmail API: Starte Anhang-Download', [
            'message_id' => $messageId,
            'attachment_id' => $attachmentId,
            'api_endpoint' => "/messages/{$messageId}/attachments/{$attachmentId}"
        ]);
        
        try {
            $attachmentData = $this->makeApiRequest("/messages/{$messageId}/attachments/{$attachmentId}");
            
            Log::info('Gmail API: API-Request erfolgreich', [
                'message_id' => $messageId,
                'attachment_id' => $attachmentId,
                'response_keys' => array_keys($attachmentData),
                'has_data_field' => isset($attachmentData['data']),
                'data_size' => isset($attachmentData['data']) ? strlen($attachmentData['data']) : 0
            ]);
            
            if (isset($attachmentData['data'])) {
                $decodedData = $this->decodeBody($attachmentData['data']);
                
                Log::info('Gmail API: Anhang-Download erfolgreich', [
                    'message_id' => $messageId,
                    'attachment_id' => $attachmentId,
                    'encoded_size' => strlen($attachmentData['data']),
                    'decoded_size' => $decodedData ? strlen($decodedData) : 0,
                    'decode_success' => $decodedData !== null
                ]);
                
                return $decodedData;
            } else {
                Log::warning('Gmail API: Keine Daten im Response', [
                    'message_id' => $messageId,
                    'attachment_id' => $attachmentId,
                    'response_keys' => array_keys($attachmentData),
                    'full_response' => $attachmentData
                ]);
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('Gmail API: Anhang-Download fehlgeschlagen', [
                'message_id' => $messageId,
                'attachment_id' => $attachmentId,
                'error_message' => $e->getMessage(),
                'error_class' => get_class($e),
                'error_code' => method_exists($e, 'getCode') ? $e->getCode() : null,
                'error_line' => $e->getLine(),
                'error_file' => $e->getFile()
            ]);
            return null;
        }
    }

    /**
     * Lädt nur PDF-Anhänge herunter und erstellt eine ZIP-Datei
     */
    public function downloadPdfAttachments(string $messageId, array $attachments): ?string
    {
        // Filtere nur PDF-Anhänge
        $pdfAttachments = array_filter($attachments, function ($attachment) {
            $mimeType = $attachment['mimeType'] ?? '';
            $filename = $attachment['filename'] ?? '';
            
            return $mimeType === 'application/pdf' ||
                   str_ends_with(strtolower($filename), '.pdf');
        });

        if (empty($pdfAttachments)) {
            return null;
        }

        $tempFiles = [];
        $zipFileName = "pdf_attachments_{$messageId}_" . date('Y-m-d_H-i-s') . '.zip';
        $zipPath = storage_path("app/temp/{$zipFileName}");

        // Stelle sicher, dass das temp-Verzeichnis existiert
        if (!file_exists(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0755, true);
        }

        try {
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE) !== TRUE) {
                throw new \Exception('Konnte ZIP-Datei nicht erstellen');
            }

            foreach ($pdfAttachments as $attachment) {
                if (!isset($attachment['id'])) {
                    continue;
                }

                try {
                    $attachmentData = $this->makeApiRequest("/messages/{$messageId}/attachments/{$attachment['id']}");
                    
                    if (isset($attachmentData['data'])) {
                        $fileContent = $this->decodeBody($attachmentData['data']);
                        $filename = $attachment['filename'] ?? "attachment_{$attachment['id']}.pdf";
                        
                        // Füge Datei zur ZIP hinzu
                        $zip->addFromString($filename, $fileContent);
                        
                        Log::info("PDF attachment added to ZIP", [
                            'gmail_id' => $messageId,
                            'filename' => $filename,
                            'size' => strlen($fileContent)
                        ]);
                    }
                } catch (\Exception $e) {
                    Log::error("Failed to download PDF attachment {$attachment['id']}: " . $e->getMessage());
                }
            }

            $zip->close();

            // Prüfe ob ZIP-Datei erstellt wurde und Inhalt hat
            if (file_exists($zipPath) && filesize($zipPath) > 0) {
                return $zipPath;
            } else {
                throw new \Exception('ZIP-Datei konnte nicht erstellt werden oder ist leer');
            }

        } catch (\Exception $e) {
            Log::error("Failed to create PDF attachments ZIP: " . $e->getMessage());
            
            // Aufräumen bei Fehler
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
            
            return null;
        }
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
     * Loggt detaillierte E-Mail Label Informationen
     */
    private function logEmailLabels(string $messageId, array $emailData): void
    {
        $labels = $emailData['labels'] ?? [];
        $subject = $emailData['subject'] ?? 'No Subject';
        $from = $emailData['from'][0]['email'] ?? 'Unknown';
        
        // Kategorisiere Labels
        $systemLabels = [];
        $userLabels = [];
        $categoryLabels = [];
        
        foreach ($labels as $label) {
            if (str_starts_with($label, 'CATEGORY_')) {
                $categoryLabels[] = $label;
            } elseif (in_array($label, ['INBOX', 'SENT', 'DRAFT', 'TRASH', 'SPAM', 'UNREAD', 'STARRED', 'IMPORTANT'])) {
                $systemLabels[] = $label;
            } else {
                $userLabels[] = $label;
            }
        }
        
        // Detailliertes Logging
        Log::info("Gmail Email Labels", [
            'gmail_id' => $messageId,
            'subject' => $subject,
            'from' => $from,
            'total_labels' => count($labels),
            'all_labels' => $labels,
            'system_labels' => $systemLabels,
            'category_labels' => $categoryLabels,
            'user_labels' => $userLabels,
            'has_inbox' => in_array('INBOX', $labels),
            'is_unread' => in_array('UNREAD', $labels),
            'is_important' => in_array('IMPORTANT', $labels),
            'is_starred' => in_array('STARRED', $labels),
            'filter_active' => $this->settings->gmail_filter_inbox ?? false,
        ]);
        
        // Zusätzliches Warning wenn INBOX Label trotz Filter gefunden wird
        if ($this->settings->gmail_filter_inbox && in_array('INBOX', $labels)) {
            Log::warning("Gmail: Email with INBOX label found despite filter being active", [
                'gmail_id' => $messageId,
                'subject' => $subject,
                'labels' => $labels
            ]);
        }
    }

    /**
     * Erstellt einen Gmail-Log-Eintrag
     */
    private function createGmailLog(array $emailData, string $action = 'sync', ?string $notes = null): void
    {
        try {
            $labels = $emailData['labels'] ?? [];
            
            // Kategorisiere Labels
            $systemLabels = [];
            $userLabels = [];
            $categoryLabels = [];
            
            foreach ($labels as $label) {
                if (str_starts_with($label, 'CATEGORY_')) {
                    $categoryLabels[] = $label;
                } elseif (in_array($label, ['INBOX', 'SENT', 'DRAFT', 'TRASH', 'SPAM', 'UNREAD', 'STARRED', 'IMPORTANT'])) {
                    $systemLabels[] = $label;
                } else {
                    $userLabels[] = $label;
                }
            }
            
            $logData = [
                'gmail_id' => $emailData['gmail_id'],
                'subject' => $emailData['subject'],
                'from_email' => $emailData['from'][0]['email'] ?? null,
                'total_labels' => count($labels),
                'all_labels' => $labels,
                'system_labels' => $systemLabels,
                'category_labels' => $categoryLabels,
                'user_labels' => $userLabels,
                'has_inbox' => in_array('INBOX', $labels),
                'is_unread' => in_array('UNREAD', $labels),
                'is_important' => in_array('IMPORTANT', $labels),
                'is_starred' => in_array('STARRED', $labels),
                'filter_active' => $this->settings->gmail_filter_inbox ?? false,
                'action' => $action,
                'notes' => $notes,
            ];
            
            GmailLog::create($logData);
            
        } catch (\Exception $e) {
            Log::error('Failed to create Gmail log entry: ' . $e->getMessage());
        }
    }

    /**
     * Behandelt Gmail API Rate Limits
     */
    private function handleRateLimit($response, string $endpoint): void
    {
        $responseBody = $response->json();
        $retryAfter = null;
        
        // Extrahiere Retry-After Zeit aus der Fehlermeldung
        if (isset($responseBody['error']['message'])) {
            $message = $responseBody['error']['message'];
            if (preg_match('/Retry after (\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z)/', $message, $matches)) {
                $retryAfter = $matches[1];
            }
        }
        
        \Log::warning('Gmail API Rate Limit erreicht', [
            'endpoint' => $endpoint,
            'retry_after' => $retryAfter,
            'response_status' => $response->status(),
            'error_message' => $responseBody['error']['message'] ?? 'Unknown error',
            'current_time' => now()->toISOString(),
            'suggested_action' => 'Warten Sie bis zum angegebenen Zeitpunkt oder reduzieren Sie die API-Anfragen'
        ]);
        
        // Speichere Rate Limit Info in den Einstellungen
        $this->settings->setGmailLastError(
            "Rate Limit erreicht. Retry nach: " . ($retryAfter ?? 'unbekannt')
        );
    }

    /**
     * Lädt einen einzelnen Anhang herunter mit Rate Limit Behandlung
     */
    public function downloadAttachmentWithRetry(string $messageId, string $attachmentId, int $maxRetries = 3): ?string
    {
        $attempt = 0;
        
        while ($attempt < $maxRetries) {
            try {
                return $this->downloadAttachment($messageId, $attachmentId);
            } catch (\Exception $e) {
                $attempt++;
                
                // Prüfe ob es ein Rate Limit Fehler ist
                if (strpos($e->getMessage(), 'RATE_LIMIT_EXCEEDED') !== false ||
                    strpos($e->getMessage(), 'User-rate limit exceeded') !== false) {
                    
                    if ($attempt < $maxRetries) {
                        $waitTime = pow(2, $attempt) * 60; // Exponential backoff: 2, 4, 8 Minuten
                        
                        \Log::info('Rate Limit Retry', [
                            'message_id' => $messageId,
                            'attachment_id' => $attachmentId,
                            'attempt' => $attempt,
                            'max_retries' => $maxRetries,
                            'wait_time_seconds' => $waitTime,
                            'next_retry_at' => now()->addSeconds($waitTime)->toISOString(),
                            'error_message' => $e->getMessage()
                        ]);
                        
                        sleep($waitTime);
                        continue;
                    }
                }
                
                // Für andere Fehler oder wenn max retries erreicht
                \Log::error('Download Attachment Retry Failed', [
                    'message_id' => $messageId,
                    'attachment_id' => $attachmentId,
                    'attempt' => $attempt,
                    'max_retries' => $maxRetries,
                    'error_message' => $e->getMessage(),
                    'final_attempt' => true
                ]);
                
                throw $e;
            }
        }
        
        return null;
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
