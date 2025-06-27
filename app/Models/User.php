<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
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
        ];
    }

    /**
     * Determine if the user can access the Filament admin panel.
     *
     * Allows access for:
     * - Local development: any user
     * - Production: admin@example.com or users with @yourdomain.com
     */
    public function canAccessPanel(Panel $panel): bool
    {
        // Allow access for the admin user created by migration
        if ($this->email === 'admin@example.com') {
            return true;
        }

        // For local development (sunnybill-test.test), allow all users
        if (app()->environment('local') || str_contains(config('app.url'), '.test')) {
            return true;
        }

        // For production, require specific domain or admin email
        return str_ends_with($this->email, '@yourdomain.com') ||
               str_ends_with($this->email, '@chargedata.eu');
    }
}
