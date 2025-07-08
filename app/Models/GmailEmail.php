<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class GmailEmail extends Model
{
    use HasFactory;

    protected $fillable = [
        'gmail_id',
        'thread_id',
        'subject',
        'snippet',
        'from',
        'to',
        'cc',
        'bcc',
        'body_text',
        'body_html',
        'labels',
        'is_read',
        'is_starred',
        'is_important',
        'is_draft',
        'is_sent',
        'is_trash',
        'is_spam',
        'has_attachments',
        'attachment_count',
        'attachments',
        'gmail_date',
        'received_at',
        'processed_at',
        'raw_headers',
        'message_id_header',
        'in_reply_to',
        'references',
        'size_estimate',
        'payload',
    ];

    protected $casts = [
        'from' => 'array',
        'to' => 'array',
        'cc' => 'array',
        'bcc' => 'array',
        'labels' => 'array',
        'attachments' => 'array',
        'raw_headers' => 'array',
        'payload' => 'array',
        'is_read' => 'boolean',
        'is_starred' => 'boolean',
        'is_important' => 'boolean',
        'is_draft' => 'boolean',
        'is_sent' => 'boolean',
        'is_trash' => 'boolean',
        'is_spam' => 'boolean',
        'has_attachments' => 'boolean',
        'gmail_date' => 'datetime',
        'received_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    /**
     * Scope für ungelesene E-Mails
     */
    public function scopeUnread($query)
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope für gelesene E-Mails
     */
    public function scopeRead($query)
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope für E-Mails mit Anhängen
     */
    public function scopeWithAttachments($query)
    {
        return $query->where('has_attachments', true);
    }

    /**
     * Scope für E-Mails mit bestimmtem Label
     */
    public function scopeWithLabel($query, $label)
    {
        return $query->whereJsonContains('labels', $label);
    }

    /**
     * Scope für E-Mails in einem bestimmten Zeitraum
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('gmail_date', [$startDate, $endDate]);
    }

    /**
     * Gibt den Absender als String zurück
     */
    public function getFromStringAttribute(): string
    {
        if (!$this->from || !is_array($this->from)) {
            return 'Unbekannt';
        }

        $from = $this->from[0] ?? [];
        $name = $from['name'] ?? '';
        $email = $from['email'] ?? '';

        if ($name && $email) {
            return "{$name} <{$email}>";
        }

        return $email ?: $name ?: 'Unbekannt';
    }

    /**
     * Gibt die Empfänger als String zurück
     */
    public function getToStringAttribute(): string
    {
        if (!$this->to || !is_array($this->to)) {
            return '';
        }

        $recipients = [];
        foreach ($this->to as $recipient) {
            $name = $recipient['name'] ?? '';
            $email = $recipient['email'] ?? '';
            
            if ($name && $email) {
                $recipients[] = "{$name} <{$email}>";
            } else {
                $recipients[] = $email ?: $name;
            }
        }

        return implode(', ', $recipients);
    }

    /**
     * Gibt eine kurze Vorschau des Inhalts zurück
     */
    public function getPreviewAttribute(): string
    {
        if ($this->snippet) {
            return $this->snippet;
        }

        if ($this->body_text) {
            return \Str::limit(strip_tags($this->body_text), 150);
        }

        if ($this->body_html) {
            return \Str::limit(strip_tags($this->body_html), 150);
        }

        return 'Keine Vorschau verfügbar';
    }

    /**
     * Prüft ob die E-Mail ein bestimmtes Label hat
     */
    public function hasLabel(string $label): bool
    {
        return in_array($label, $this->labels ?? []);
    }

    /**
     * Fügt ein Label hinzu
     */
    public function addLabel(string $label): void
    {
        $labels = $this->labels ?? [];
        if (!in_array($label, $labels)) {
            $labels[] = $label;
            $this->update(['labels' => $labels]);
        }
    }

    /**
     * Entfernt ein Label
     */
    public function removeLabel(string $label): void
    {
        $labels = $this->labels ?? [];
        $labels = array_filter($labels, fn($l) => $l !== $label);
        $this->update(['labels' => array_values($labels)]);
    }

    /**
     * Markiert die E-Mail als gelesen
     */
    public function markAsRead(): void
    {
        $this->update(['is_read' => true]);
    }

    /**
     * Markiert die E-Mail als ungelesen
     */
    public function markAsUnread(): void
    {
        $this->update(['is_read' => false]);
    }

    /**
     * Markiert die E-Mail als Favorit
     */
    public function star(): void
    {
        $this->update(['is_starred' => true]);
    }

    /**
     * Entfernt den Favorit-Status
     */
    public function unstar(): void
    {
        $this->update(['is_starred' => false]);
    }

    /**
     * Verschiebt die E-Mail in den Papierkorb
     */
    public function moveToTrash(): void
    {
        $this->update(['is_trash' => true]);
        $this->addLabel('TRASH');
    }

    /**
     * Stellt die E-Mail aus dem Papierkorb wieder her
     */
    public function restoreFromTrash(): void
    {
        $this->update(['is_trash' => false]);
        $this->removeLabel('TRASH');
    }

    /**
     * Markiert die E-Mail als Spam
     */
    public function markAsSpam(): void
    {
        $this->update(['is_spam' => true]);
        $this->addLabel('SPAM');
    }

    /**
     * Entfernt den Spam-Status
     */
    public function unmarkAsSpam(): void
    {
        $this->update(['is_spam' => false]);
        $this->removeLabel('SPAM');
    }

    /**
     * Gibt die Anhänge als Collection zurück
     */
    public function getAttachmentsCollection()
    {
        return collect($this->attachments ?? []);
    }

    /**
     * Lädt einen Anhang herunter
     */
    public function downloadAttachment(string $attachmentId): ?string
    {
        $attachment = collect($this->attachments ?? [])->firstWhere('id', $attachmentId);
        
        if (!$attachment) {
            return null;
        }

        $settings = CompanySetting::current();
        $attachmentPath = $settings->gmail_attachment_path ?? 'gmail-attachments';
        
        // Erstelle Verzeichnisstruktur: gmail-attachments/YYYY/MM/DD/gmail_id/
        $datePath = $this->gmail_date ? $this->gmail_date->format('Y/m/d') : date('Y/m/d');
        $fullPath = "{$attachmentPath}/{$datePath}/{$this->gmail_id}";
        
        $filename = $attachment['filename'] ?? "attachment_{$attachmentId}";
        $filePath = "{$fullPath}/{$filename}";
        
        // Prüfe ob Datei bereits existiert
        if (Storage::exists($filePath)) {
            return $filePath;
        }

        // Hier würde der tatsächliche Download über Gmail API erfolgen
        // Das wird im GmailService implementiert
        
        return null;
    }

    /**
     * Gibt die Größe in lesbarem Format zurück
     */
    public function getReadableSizeAttribute(): string
    {
        if (!$this->size_estimate) {
            return 'Unbekannt';
        }

        $bytes = $this->size_estimate;
        $units = ['B', 'KB', 'MB', 'GB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }

    /**
     * Prüft ob die E-Mail kürzlich empfangen wurde (letzte 24h)
     */
    public function getIsRecentAttribute(): bool
    {
        return $this->gmail_date && $this->gmail_date->isAfter(now()->subDay());
    }

    /**
     * Gibt das Alter der E-Mail in lesbarem Format zurück
     */
    public function getAgeAttribute(): string
    {
        if (!$this->gmail_date) {
            return 'Unbekannt';
        }

        return $this->gmail_date->diffForHumans();
    }

    /**
     * Statische Methode zum Finden einer E-Mail anhand der Gmail ID
     */
    public static function findByGmailId(string $gmailId): ?self
    {
        return static::where('gmail_id', $gmailId)->first();
    }

    /**
     * Statische Methode zum Erstellen oder Aktualisieren einer E-Mail
     */
    public static function createOrUpdateFromGmail(array $gmailData): self
    {
        $email = static::findByGmailId($gmailData['gmail_id']) ?? new static();
        
        $email->fill($gmailData);
        $email->received_at = $email->received_at ?? now();
        $email->save();
        
        return $email;
    }

    /**
     * Gibt Statistiken für das Dashboard zurück
     */
    public static function getStats(): array
    {
        return [
            'total' => static::count(),
            'unread' => static::unread()->count(),
            'with_attachments' => static::withAttachments()->count(),
            'recent' => static::where('gmail_date', '>=', now()->subDay())->count(),
            'this_week' => static::where('gmail_date', '>=', now()->startOfWeek())->count(),
            'this_month' => static::where('gmail_date', '>=', now()->startOfMonth())->count(),
        ];
    }
}
