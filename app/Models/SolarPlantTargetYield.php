<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SolarPlantTargetYield extends Model
{
    use HasFactory, HasUuids;

    protected $fillable = [
        'solar_plant_id',
        'year',
        'january_kwh',
        'february_kwh',
        'march_kwh',
        'april_kwh',
        'may_kwh',
        'june_kwh',
        'july_kwh',
        'august_kwh',
        'september_kwh',
        'october_kwh',
        'november_kwh',
        'december_kwh',
        'notes',
    ];

    protected $casts = [
        'year' => 'integer',
        'january_kwh' => 'decimal:2',
        'february_kwh' => 'decimal:2',
        'march_kwh' => 'decimal:2',
        'april_kwh' => 'decimal:2',
        'may_kwh' => 'decimal:2',
        'june_kwh' => 'decimal:2',
        'july_kwh' => 'decimal:2',
        'august_kwh' => 'decimal:2',
        'september_kwh' => 'decimal:2',
        'october_kwh' => 'decimal:2',
        'november_kwh' => 'decimal:2',
        'december_kwh' => 'decimal:2',
    ];

    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Berechnet die Gesamtsumme aller Monate
     */
    public function getTotalYearlyTargetAttribute(): float
    {
        return (float) (
            $this->january_kwh +
            $this->february_kwh +
            $this->march_kwh +
            $this->april_kwh +
            $this->may_kwh +
            $this->june_kwh +
            $this->july_kwh +
            $this->august_kwh +
            $this->september_kwh +
            $this->october_kwh +
            $this->november_kwh +
            $this->december_kwh
        );
    }

    /**
     * Gibt die Monatsnamen zurück
     */
    public static function getMonthNames(): array
    {
        return [
            'january_kwh' => 'Januar',
            'february_kwh' => 'Februar',
            'march_kwh' => 'März',
            'april_kwh' => 'April',
            'may_kwh' => 'Mai',
            'june_kwh' => 'Juni',
            'july_kwh' => 'Juli',
            'august_kwh' => 'August',
            'september_kwh' => 'September',
            'october_kwh' => 'Oktober',
            'november_kwh' => 'November',
            'december_kwh' => 'Dezember',
        ];
    }

    /**
     * Gibt den Wert für einen bestimmten Monat zurück
     */
    public function getMonthValue(int $month): ?float
    {
        $monthFields = [
            1 => 'january_kwh',
            2 => 'february_kwh',
            3 => 'march_kwh',
            4 => 'april_kwh',
            5 => 'may_kwh',
            6 => 'june_kwh',
            7 => 'july_kwh',
            8 => 'august_kwh',
            9 => 'september_kwh',
            10 => 'october_kwh',
            11 => 'november_kwh',
            12 => 'december_kwh',
        ];

        return isset($monthFields[$month]) ? $this->{$monthFields[$month]} : null;
    }
}