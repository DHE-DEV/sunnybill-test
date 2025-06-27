<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlantParticipation extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'solar_plant_id',
        'percentage',
    ];

    protected $casts = [
        'percentage' => 'decimal:2',
    ];

    /**
     * Beziehung zum Kunden
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Formatierte Prozentangabe
     */
    public function getFormattedPercentageAttribute(): string
    {
        return number_format($this->percentage, 2, ',', '.') . '%';
    }

    /**
     * Validierung: Gesamtbeteiligung darf 100% nicht überschreiten
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($participation) {
            $solarPlant = SolarPlant::find($participation->solar_plant_id);
            
            if ($solarPlant) {
                $existingParticipation = $solarPlant->participations()
                    ->where('id', '!=', $participation->id ?? 0)
                    ->sum('percentage');
                
                $totalParticipation = $existingParticipation + $participation->percentage;
                
                if ($totalParticipation > 100) {
                    throw new \Exception(
                        "Die Gesamtbeteiligung würde {$totalParticipation}% betragen. " .
                        "Maximal sind 100% möglich. Verfügbar: " . (100 - $existingParticipation) . "%"
                    );
                }
            }
        });
    }
}
