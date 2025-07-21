<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectMilestone extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'planned_date',
        'actual_date',
        'status',
        'responsible_user_id',
        'dependencies',
        'completion_percentage',
        'is_critical_path',
        'sort_order',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'completion_percentage' => 'integer',
        'is_critical_path' => 'boolean',
        'dependencies' => 'array',
        'sort_order' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function responsibleUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsible_user_id');
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'gray',
            'in_progress' => 'info',
            'completed' => 'success',
            'delayed' => 'warning',
            'cancelled' => 'danger',
            default => 'gray',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'planning' => 'Planung',
            'approval' => 'Genehmigung',
            'implementation' => 'Umsetzung',
            'testing' => 'Testing',
            'delivery' => 'Lieferung',
            'payment' => 'Zahlung',
            'review' => 'Review',
            default => $this->type,
        };
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->planned_date->isPast() && $this->status !== 'completed';
    }

    public function getDaysRemainingAttribute(): int
    {
        return now()->diffInDays($this->planned_date, false);
    }

    public function getProgressPercentageAttribute(): int
    {
        return $this->completion_percentage;
    }
}
