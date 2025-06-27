<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPlantMilestone extends Model
{
    use HasFactory;

    protected $fillable = [
        'solar_plant_id',
        'title',
        'description',
        'planned_date',
        'actual_date',
        'status',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'planned_date' => 'date',
        'actual_date' => 'date',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Formatierter Status
     */
    public function getFormattedStatusAttribute(): string
    {
        return match($this->status) {
            'planned' => 'Geplant',
            'in_progress' => 'In Bearbeitung',
            'completed' => 'Abgeschlossen',
            'delayed' => 'Verzögert',
            'cancelled' => 'Abgebrochen',
            default => $this->status,
        };
    }

    /**
     * Prüft ob der Termin überfällig ist
     */
    public function getIsOverdueAttribute(): bool
    {
        if ($this->status === 'completed' || $this->status === 'cancelled') {
            return false;
        }
        
        return $this->planned_date < now()->toDateString();
    }

    /**
     * Prüft ob der Termin heute ist
     */
    public function getIsTodayAttribute(): bool
    {
        return $this->planned_date->isToday();
    }

    /**
     * Prüft ob der Termin in der nächsten Woche ist
     */
    public function getIsUpcomingAttribute(): bool
    {
        return $this->planned_date >= now()->toDateString() && 
               $this->planned_date <= now()->addWeek()->toDateString();
    }

    /**
     * Formatiertes geplantes Datum
     */
    public function getFormattedPlannedDateAttribute(): string
    {
        return $this->planned_date->format('d.m.Y');
    }

    /**
     * Formatiertes tatsächliches Datum
     */
    public function getFormattedActualDateAttribute(): ?string
    {
        return $this->actual_date?->format('d.m.Y');
    }

    /**
     * Abweichung in Tagen zwischen geplant und tatsächlich
     */
    public function getDateVarianceAttribute(): ?int
    {
        if (!$this->actual_date) {
            return null;
        }
        
        return $this->actual_date->diffInDays($this->planned_date, false);
    }
}