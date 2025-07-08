<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use App\Notifications\CustomVerifyEmail;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'temporary_password',
        'tmp_p',
        'role',
        'is_active',
        'last_login_at',
        'phone',
        'department',
        'notes',
        'password_change_required',
        'password_changed_at',
        // Gmail-Benachrichtigungseinstellungen
        'gmail_notifications_enabled',
        'gmail_notification_preferences',
        'gmail_browser_notifications',
        'gmail_email_notifications',
        'gmail_sound_notifications',
        'gmail_last_notification_at',
        'gmail_notifications_received_count',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_login_at' => 'datetime',
            'is_active' => 'boolean',
            'password_change_required' => 'boolean',
            'password_changed_at' => 'datetime',
            // Gmail-Benachrichtigungseinstellungen
            'gmail_notifications_enabled' => 'boolean',
            'gmail_notification_preferences' => 'array',
            'gmail_browser_notifications' => 'boolean',
            'gmail_email_notifications' => 'boolean',
            'gmail_sound_notifications' => 'boolean',
            'gmail_last_notification_at' => 'datetime',
            'gmail_notifications_received_count' => 'integer',
        ];
    }

    /**
     * Mutator for temporary_password - prevents automatic hashing
     */
    public function setTemporaryPasswordAttribute($value): void
    {
        // Store temporary password as plain text (no hashing)
        $this->attributes['temporary_password'] = $value;
    }

    /**
     * Accessor for temporary_password - returns plain text
     */
    public function getTemporaryPasswordAttribute($value): ?string
    {
        // Return temporary password as plain text (no decryption needed)
        return $value;
    }

    /**
     * Get available user roles
     */
    public static function getRoles(): array
    {
        return [
            'admin' => 'Administrator',
            'manager' => 'Manager',
            'user' => 'Benutzer',
            'viewer' => 'Betrachter',
        ];
    }

    /**
     * Get role label
     */
    public function getRoleLabelAttribute(): string
    {
        return self::getRoles()[$this->role] ?? 'Unbekannt';
    }

    /**
     * Check if user has specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Check if user is admin
     */
    public function isAdmin(): bool
    {
        return $this->hasRole('admin');
    }

    /**
     * Check if user is manager or admin
     */
    public function isManagerOrAdmin(): bool
    {
        return in_array($this->role, ['admin', 'manager']);
    }

    /**
     * Scope for active users
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for inactive users
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->update(['last_login_at' => now()]);
    }

    /**
     * Send the email verification notification.
     */
    public function sendEmailVerificationNotification($temporaryPassword = null)
    {
        $this->notify(new CustomVerifyEmail($temporaryPassword));
    }

    /**
     * Generate a random password
     */
    public static function generateRandomPassword(int $length = 12): string
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $password = '';
        
        // Ensure at least one character from each type
        $password .= chr(rand(97, 122)); // lowercase
        $password .= chr(rand(65, 90));  // uppercase
        $password .= chr(rand(48, 57));  // number
        
        // Fill the rest randomly
        for ($i = 3; $i < $length; $i++) {
            $password .= $characters[rand(0, strlen($characters) - 1)];
        }

        // Shuffle the password
        return str_shuffle($password);
    }

    /**
     * Check if user needs to change password
     */
    public function needsPasswordChange(): bool
    {
        return (bool) $this->password_change_required;
    }

    /**
     * Mark password as changed and clear temporary password
     */
    public function markPasswordAsChanged(): void
    {
        $this->password_change_required = false;
        $this->password_changed_at = now();
        $this->temporary_password = null; // Lösche temporäres Passwort
        $this->tmp_p = null; // Lösche auch die neue Spalte
        $this->save();
    }

    /**
     * Require password change
     */
    public function requirePasswordChange(): void
    {
        $this->password_change_required = true;
        $this->save();
    }

    /**
     * Set temporary password
     */
    public function setTemporaryPassword(string $password): void
    {
        $this->tmp_p = $password; // Verwende die neue Spalte
        $this->password_change_required = true;
        $this->save();
    }

    /**
     * Clear temporary password
     */
    public function clearTemporaryPassword(): void
    {
        $this->tmp_p = null; // Verwende die neue Spalte
        $this->save();
    }

    /**
     * Check if user has temporary password
     */
    public function hasTemporaryPassword(): bool
    {
        return !empty($this->tmp_p); // Verwende die neue Spalte
    }

    /**
     * Get temporary password for email notifications
     */
    public function getTemporaryPasswordForEmail(): ?string
    {
        return $this->tmp_p; // Verwende die neue Spalte
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * Allows access for:
     * - Local development: any user
     * - Production: users with admin/manager role OR specific email domains
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Benutzer muss aktiv sein
        if (!$this->is_active) {
            return false;
        }

        // Allow access for the admin user created by migration
        if ($this->email === 'admin@example.com') {
            return true;
        }

        // For local development (sunnybill-test.test), allow all users
        if (app()->environment('local') || str_contains(config('app.url'), '.test')) {
            return true;
        }

        // Rollenbasierte Zugriffskontrolle: Admin, Manager und User haben immer Zugriff
        if (in_array($this->role, ['admin', 'manager', 'user'])) {
            return true;
        }

        // Zusätzlich: Bestimmte E-Mail-Domains haben Zugriff
        return str_ends_with($this->email, '@chargedata.eu');
    }
}
