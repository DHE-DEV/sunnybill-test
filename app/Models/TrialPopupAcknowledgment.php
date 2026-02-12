<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrialPopupAcknowledgment extends Model
{
    protected $fillable = [
        'user_id',
        'displayed_at',
        'acknowledged_at',
    ];

    protected function casts(): array
    {
        return [
            'displayed_at' => 'datetime',
            'acknowledged_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
