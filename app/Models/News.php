<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class News extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'image_path',
        'is_active',
        'priority',
        'published_at',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'published_at' => 'datetime',
    ];

    /**
     * Get the user who created the news
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all user views for this news
     */
    public function userViews(): HasMany
    {
        return $this->hasMany(NewsUserView::class);
    }

    /**
     * Check if a user has viewed this news
     */
    public function hasBeenViewedBy(User $user): bool
    {
        return $this->userViews()
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Check if a user has marked this as "don't show again"
     */
    public function isDontShowAgainBy(User $user): bool
    {
        return $this->userViews()
            ->where('user_id', $user->id)
            ->where('dont_show_again', true)
            ->exists();
    }

    /**
     * Get unviewed news for a user
     */
    public static function getUnviewedForUser(User $user)
    {
        return static::where('is_active', true)
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->whereDoesntHave('userViews', function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->where('dont_show_again', true);
            })
            ->orderBy('priority', 'desc')
            ->orderBy('published_at', 'desc')
            ->get();
    }
}
