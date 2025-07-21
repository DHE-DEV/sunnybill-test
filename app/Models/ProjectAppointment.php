<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ProjectAppointment extends Model
{
    use HasFactory, HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'project_id',
        'title',
        'description',
        'type',
        'start_datetime',
        'end_datetime',
        'location',
        'attendees',
        'reminder_minutes',
        'is_recurring',
        'recurring_pattern',
        'status',
        'created_by',
    ];

    protected $casts = [
        'start_datetime' => 'datetime',
        'end_datetime' => 'datetime',
        'attendees' => 'array',
        'recurring_pattern' => 'array',
        'is_recurring' => 'boolean',
        'reminder_minutes' => 'integer',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'meeting' => 'Meeting',
            'deadline' => 'Deadline',
            'review' => 'Review',
            'milestone_check' => 'Meilenstein-Check',
            'inspection' => 'Inspektion',
            'training' => 'Schulung',
            default => $this->type,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'scheduled' => 'gray',
            'confirmed' => 'success',
            'cancelled' => 'danger',
            'completed' => 'info',
            default => 'gray',
        };
    }

    public function getDurationAttribute(): ?string
    {
        if (!$this->end_datetime) return null;
        
        $duration = $this->start_datetime->diff($this->end_datetime);
        return $duration->format('%H:%I');
    }

    public function getIsUpcomingAttribute(): bool
    {
        return $this->start_datetime->isFuture() && 
               in_array($this->status, ['scheduled', 'confirmed']);
    }

    public function getIsTodayAttribute(): bool
    {
        return $this->start_datetime->isToday();
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->start_datetime->isPast() && 
               $this->status === 'scheduled';
    }
}
