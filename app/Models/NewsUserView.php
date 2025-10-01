<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NewsUserView extends Model
{
    protected $fillable = [
        'news_id',
        'user_id',
        'dont_show_again',
        'viewed_at',
    ];

    protected $casts = [
        'dont_show_again' => 'boolean',
        'viewed_at' => 'datetime',
    ];

    /**
     * Get the news item
     */
    public function news(): BelongsTo
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Get the user who viewed the news
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
