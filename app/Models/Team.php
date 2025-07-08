<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    /**
     * Many-to-Many Beziehung zu Users
     */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'team_user')
                    ->withTimestamps()
                    ->withPivot('role', 'joined_at')
                    ->orderBy('name');
    }

    /**
     * Aktive Teams
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Inaktive Teams
     */
    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    /**
     * Anzahl der Mitglieder
     */
    public function getMembersCountAttribute(): int
    {
        return $this->users()->count();
    }

    /**
     * Team-Rollen
     */
    public static function getTeamRoles(): array
    {
        return [
            'member' => 'Mitglied',
            'lead' => 'Team Lead',
            'admin' => 'Team Admin',
        ];
    }

    /**
     * Verfügbare Farben für Teams
     */
    public static function getColors(): array
    {
        return [
            'blue' => 'Blau',
            'green' => 'Grün',
            'yellow' => 'Gelb',
            'red' => 'Rot',
            'purple' => 'Lila',
            'pink' => 'Rosa',
            'indigo' => 'Indigo',
            'gray' => 'Grau',
        ];
    }

    /**
     * Fügt einen User zum Team hinzu
     */
    public function addUser(User $user, string $role = 'member'): void
    {
        $this->users()->attach($user->id, [
            'role' => $role,
            'joined_at' => now(),
        ]);
    }

    /**
     * Entfernt einen User aus dem Team
     */
    public function removeUser(User $user): void
    {
        $this->users()->detach($user->id);
    }

    /**
     * Prüft ob ein User Mitglied des Teams ist
     */
    public function hasUser(User $user): bool
    {
        return $this->users()->where('user_id', $user->id)->exists();
    }

    /**
     * Holt die Rolle eines Users im Team
     */
    public function getUserRole(User $user): ?string
    {
        $pivot = $this->users()->where('user_id', $user->id)->first()?->pivot;
        return $pivot?->role;
    }
}