<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class AppToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'token',
        'abilities',
        'expires_at',
        'is_active',
        'last_used_at',
        'created_by_ip',
        'app_type',
        'app_version',
        'device_info',
        'notes',
    ];

    protected $casts = [
        'abilities' => 'array',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    /**
     * Beziehung zum User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Verfügbare App-Typen
     */
    public static function getAppTypes(): array
    {
        return [
            'mobile_app' => 'Mobile App',
            'desktop_app' => 'Desktop App',
            'web_app' => 'Web App',
            'third_party' => 'Third Party',
            'integration' => 'Integration',
        ];
    }

    /**
     * Verfügbare Token-Berechtigungen
     */
    public static function getAvailableAbilities(): array
    {
        return [
            'tasks:read' => 'Aufgaben lesen',
            'tasks:create' => 'Aufgaben erstellen',
            'tasks:update' => 'Aufgaben bearbeiten',
            'tasks:delete' => 'Aufgaben löschen',
            'tasks:assign' => 'Aufgaben zuweisen',
            'tasks:status' => 'Status ändern',
            'tasks:notes' => 'Notizen verwalten',
            'tasks:documents' => 'Dokumente verwalten',
            'tasks:time' => 'Zeiten erfassen',
            'user:profile' => 'Profil lesen',
            'notifications:read' => 'Benachrichtigungen lesen',
            'notifications:create' => 'Benachrichtigungen erstellen',
        ];
    }

    /**
     * Generiere einen neuen Token
     */
    public static function generateToken(): string
    {
        return 'sb_' . Str::random(64);
    }

    /**
     * Erstelle einen neuen App-Token
     */
    public static function createToken(
        int $userId,
        string $name,
        array $abilities = [],
        string $appType = 'mobile_app',
        ?string $appVersion = null,
        ?string $deviceInfo = null,
        ?string $notes = null
    ): self {
        $token = self::generateToken();
        
        return self::create([
            'user_id' => $userId,
            'name' => $name,
            'token' => hash('sha256', $token),
            'abilities' => $abilities ?: ['tasks:read'],
            'expires_at' => now()->addYears(2),
            'is_active' => true,
            'created_by_ip' => request()->ip(),
            'app_type' => $appType,
            'app_version' => $appVersion,
            'device_info' => $deviceInfo,
            'notes' => $notes,
        ]);
    }

    /**
     * Prüfe ob Token gültig ist
     */
    public function isValid(): bool
    {
        return $this->is_active && 
               $this->expires_at > now() &&
               $this->user && 
               $this->user->is_active;
    }

    /**
     * Prüfe ob Token eine bestimmte Berechtigung hat
     */
    public function hasAbility(string $ability): bool
    {
        return in_array($ability, $this->abilities ?? []);
    }

    /**
     * Markiere Token als verwendet
     */
    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Erneuere Token (verlängere Gültigkeit)
     */
    public function renew(): void
    {
        $this->update(['expires_at' => now()->addYears(2)]);
    }

    /**
     * Deaktiviere Token
     */
    public function disable(): void
    {
        $this->update(['is_active' => false]);
    }

    /**
     * Aktiviere Token
     */
    public function enable(): void
    {
        $this->update(['is_active' => true]);
    }

    /**
     * Scope für aktive Tokens
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für nicht abgelaufene Tokens
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope für gültige Tokens
     */
    public function scopeValid($query)
    {
        return $query->active()->notExpired();
    }

    /**
     * Scope für bald ablaufende Tokens (innerhalb von 30 Tagen)
     */
    public function scopeExpiringSoon($query)
    {
        return $query->where('expires_at', '>', now())
                    ->where('expires_at', '<', now()->addDays(30));
    }

    /**
     * Finde Token durch hash
     */
    public static function findByToken(string $token): ?self
    {
        return self::where('token', hash('sha256', $token))->first();
    }

    /**
     * Get App Type Label
     */
    public function getAppTypeLabelAttribute(): string
    {
        return self::getAppTypes()[$this->app_type] ?? $this->app_type;
    }

    /**
     * Get Abilities Labels
     */
    public function getAbilitiesLabelsAttribute(): array
    {
        $availableAbilities = self::getAvailableAbilities();
        return array_map(function ($ability) use ($availableAbilities) {
            return $availableAbilities[$ability] ?? $ability;
        }, $this->abilities ?? []);
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        if (!$this->is_active) {
            return 'Deaktiviert';
        }
        
        if ($this->expires_at < now()) {
            return 'Abgelaufen';
        }
        
        if ($this->expires_at < now()->addDays(30)) {
            return 'Läuft bald ab';
        }
        
        return 'Aktiv';
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        if (!$this->is_active) {
            return 'gray';
        }
        
        if ($this->expires_at < now()) {
            return 'danger';
        }
        
        if ($this->expires_at < now()->addDays(30)) {
            return 'warning';
        }
        
        return 'success';
    }
}
