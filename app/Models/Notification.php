<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'created_by',
        'team_id',
        'recipient_type',
        'type',
        'title',
        'message',
        'data',
        'icon',
        'color',
        'priority',
        'is_read',
        'read_at',
        'action_url',
        'action_text',
        'expires_at',
    ];

    protected $casts = [
        'data' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $dates = [
        'read_at',
        'expires_at',
        'created_at',
        'updated_at',
    ];

    // Konstanten für Notification-Typen
    const TYPE_GMAIL_EMAIL = 'gmail_email';
    const TYPE_SYSTEM = 'system';
    const TYPE_TASK = 'task';
    const TYPE_BILLING = 'billing';
    const TYPE_CUSTOMER = 'customer';
    const TYPE_SOLAR_PLANT = 'solar_plant';

    // Konstanten für Farben
    const COLOR_PRIMARY = 'primary';
    const COLOR_SUCCESS = 'success';
    const COLOR_WARNING = 'warning';
    const COLOR_DANGER = 'danger';
    const COLOR_INFO = 'info';

    // Konstanten für Prioritäten
    const PRIORITY_LOW = 'low';
    const PRIORITY_NORMAL = 'normal';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';

    /**
     * Beziehung zum User (Empfänger)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Beziehung zum Ersteller
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Beziehung zum Team (falls Team-Benachrichtigung)
     */
    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    /**
     * Gibt den Empfänger-Namen zurück
     */
    public function getRecipientNameAttribute(): string
    {
        if ($this->recipient_type === 'team' && $this->team) {
            return $this->team->name;
        }
        
        if ($this->user && $this->user->id === auth()->id()) {
            return 'Ich';
        }
        
        return $this->user ? $this->user->name : 'Unbekannt';
    }

    /**
     * Gibt den Empfänger-Namen zurück (Methoden-Version für Filament)
     */
    public function getRecipientName(): string
    {
        return $this->getRecipientNameAttribute();
    }

    /**
     * Gibt die Empfänger-Farbe zurück (für Team-Badges)
     */
    public function getRecipientColorAttribute(): string
    {
        if ($this->recipient_type === 'team' && $this->team) {
            return $this->team->color;
        }
        
        return 'gray';
    }

    /**
     * Gibt die Empfänger-Farbe zurück (Methoden-Version für Filament)
     */
    public function getRecipientColor(): string
    {
        return $this->getRecipientColorAttribute();
    }

    /**
     * Prüft ob es eine Team-Benachrichtigung ist
     */
    public function isTeamNotification(): bool
    {
        return $this->recipient_type === 'team';
    }

    /**
     * Prüft ob der aktuelle User der Empfänger ist
     */
    public function isRecipientCurrentUser(): bool
    {
        return $this->user_id === auth()->id();
    }

    /**
     * Scope für ungelesene Benachrichtigungen
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->where('is_read', false);
    }

    /**
     * Scope für gelesene Benachrichtigungen
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->where('is_read', true);
    }

    /**
     * Scope für nicht abgelaufene Benachrichtigungen
     */
    public function scopeNotExpired(Builder $query): Builder
    {
        return $query->where(function ($query) {
            $query->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope für bestimmten Benutzer
     */
    public function scopeForUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope für bestimmten Typ
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope für bestimmte Priorität
     */
    public function scopeWithPriority(Builder $query, string $priority): Builder
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope für sortierte Benachrichtigungen (neueste zuerst, dann nach Priorität)
     */
    public function scopeSorted(Builder $query): Builder
    {
        return $query->orderByRaw("
            CASE priority 
                WHEN 'urgent' THEN 1 
                WHEN 'high' THEN 2 
                WHEN 'normal' THEN 3 
                WHEN 'low' THEN 4 
                ELSE 5 
            END
        ")->orderBy('created_at', 'desc');
    }

    /**
     * Markiert die Benachrichtigung als gelesen
     */
    public function markAsRead(): bool
    {
        if ($this->is_read) {
            return true;
        }

        return $this->update([
            'is_read' => true,
            'read_at' => now(),
        ]);
    }

    /**
     * Markiert die Benachrichtigung als ungelesen
     */
    public function markAsUnread(): bool
    {
        return $this->update([
            'is_read' => false,
            'read_at' => null,
        ]);
    }

    /**
     * Prüft ob die Benachrichtigung abgelaufen ist
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Gibt das Icon für die Benachrichtigung zurück
     */
    public function getIconAttribute($value): string
    {
        if ($value) {
            return $value;
        }

        // Standard-Icons basierend auf Typ
        return match ($this->type) {
            self::TYPE_GMAIL_EMAIL => 'heroicon-o-envelope',
            self::TYPE_SYSTEM => 'heroicon-o-cog-6-tooth',
            self::TYPE_TASK => 'heroicon-o-clipboard-document-list',
            self::TYPE_BILLING => 'heroicon-o-currency-euro',
            self::TYPE_CUSTOMER => 'heroicon-o-user-group',
            self::TYPE_SOLAR_PLANT => 'heroicon-o-sun',
            default => 'heroicon-o-bell',
        };
    }

    /**
     * Gibt die CSS-Klasse für die Farbe zurück
     */
    public function getColorClass(): string
    {
        return match ($this->color) {
            self::COLOR_SUCCESS => 'text-success-600 bg-success-50',
            self::COLOR_WARNING => 'text-warning-600 bg-warning-50',
            self::COLOR_DANGER => 'text-danger-600 bg-danger-50',
            self::COLOR_INFO => 'text-info-600 bg-info-50',
            default => 'text-primary-600 bg-primary-50',
        };
    }

    /**
     * Gibt die Prioritäts-Badge-Klasse zurück
     */
    public function getPriorityBadgeClass(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'bg-red-100 text-red-800',
            self::PRIORITY_HIGH => 'bg-orange-100 text-orange-800',
            self::PRIORITY_NORMAL => 'bg-blue-100 text-blue-800',
            self::PRIORITY_LOW => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Gibt den Prioritäts-Text zurück
     */
    public function getPriorityText(): string
    {
        return match ($this->priority) {
            self::PRIORITY_URGENT => 'Dringend',
            self::PRIORITY_HIGH => 'Hoch',
            self::PRIORITY_NORMAL => 'Normal',
            self::PRIORITY_LOW => 'Niedrig',
            default => 'Normal',
        };
    }

    /**
     * Erstellt eine neue Gmail-Benachrichtigung
     */
    public static function createGmailNotification(
        int $userId,
        string $title,
        string $message,
        array $data = [],
        string $priority = self::PRIORITY_NORMAL
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => self::TYPE_GMAIL_EMAIL,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'icon' => 'heroicon-o-envelope',
            'color' => self::COLOR_PRIMARY,
            'priority' => $priority,
            'action_url' => $data['url'] ?? null,
            'action_text' => 'E-Mail anzeigen',
        ]);
    }

    /**
     * Erstellt eine System-Benachrichtigung
     */
    public static function createSystemNotification(
        int $userId,
        string $title,
        string $message,
        string $color = self::COLOR_INFO,
        array $data = []
    ): self {
        return self::create([
            'user_id' => $userId,
            'type' => self::TYPE_SYSTEM,
            'title' => $title,
            'message' => $message,
            'data' => $data,
            'icon' => 'heroicon-o-cog-6-tooth',
            'color' => $color,
            'priority' => self::PRIORITY_NORMAL,
        ]);
    }

    /**
     * Löscht abgelaufene Benachrichtigungen
     */
    public static function deleteExpired(): int
    {
        return self::where('expires_at', '<', now())->delete();
    }

    /**
     * Markiert alle Benachrichtigungen eines Benutzers als gelesen
     */
    public static function markAllAsReadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
                   ->where('is_read', false)
                   ->update([
                       'is_read' => true,
                       'read_at' => now(),
                   ]);
    }

    /**
     * Zählt ungelesene Benachrichtigungen für einen Benutzer
     */
    public static function countUnreadForUser(int $userId): int
    {
        return self::where('user_id', $userId)
                   ->unread()
                   ->notExpired()
                   ->count();
    }

    /**
     * Holt die neuesten Benachrichtigungen für einen Benutzer
     */
    public static function getRecentForUser(int $userId, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return self::where('user_id', $userId)
                   ->notExpired()
                   ->sorted()
                   ->limit($limit)
                   ->get();
    }
}
