<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MermaidChart extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'template',
        'generated_code',
        'solar_plant_id',
        'chart_type',
        'is_active',
        'metadata',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Beziehung zur Solaranlage
     */
    public function solarPlant(): BelongsTo
    {
        return $this->belongsTo(SolarPlant::class);
    }

    /**
     * Scope für aktive Charts
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope für Chart-Typ
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('chart_type', $type);
    }

    /**
     * Generiert den Chart-Code basierend auf dem Template
     */
    public function generateCode(): string
    {
        if (!$this->solar_plant_id || !$this->solarPlant) {
            return $this->template;
        }

        $mermaidService = new \App\Services\MermaidChartService();
        $generatedCode = $mermaidService->generateSolarPlantChart($this->solarPlant, $this->template);
        
        // Speichere den generierten Code
        $this->update(['generated_code' => $generatedCode]);
        
        return $generatedCode;
    }

    /**
     * Gibt den aktuellen Chart-Code zurück (generiert oder Template)
     */
    public function getChartCode(): string
    {
        return $this->generated_code ?: $this->template;
    }

    /**
     * Prüft ob der Chart eine Solaranlage zugewiesen hat
     */
    public function hasSolarPlant(): bool
    {
        return !is_null($this->solar_plant_id);
    }

    /**
     * Verfügbare Chart-Typen
     */
    public static function getChartTypes(): array
    {
        return [
            'solar_plant' => 'Solaranlage',
            'customer_overview' => 'Kundenübersicht',
            'supplier_overview' => 'Lieferantenübersicht',
            'contract_overview' => 'Vertragsübersicht',
            'custom' => 'Benutzerdefiniert',
        ];
    }

    /**
     * Standard-Template für Solaranlagen
     */
    public static function getDefaultSolarPlantTemplate(): string
    {
        return 'flowchart TD
    %% Styling
    classDef solarPlant fill:#FFD700,stroke:#FF8C00,stroke-width:3px,color:#000,font-weight:bold
    classDef customer fill:#87CEEB,stroke:#4682B4,stroke-width:2px,color:#000
    classDef supplier fill:#98FB98,stroke:#32CD32,stroke-width:2px,color:#000
    classDef contract fill:#DDA0DD,stroke:#9370DB,stroke-width:1.5px,color:#000
    classDef money fill:#F0E68C,stroke:#DAA520,stroke-width:1.5px,color:#000
    classDef info fill:#FFF,stroke:#999,stroke-width:1px,color:#333

    %% Solaranlage
    SA["Solaranlage<br/>{{plant_name}}<br/>{{plant_capacity}}"]:::solarPlant

    {{customers}}

    {{suppliers}}

    {{contracts}}

    {{customer_connections}}

    {{supplier_connections}}

    {{billing_connections}}

    %% Hinweis
    Info["**Hinweise:**<br/>
    - Alle Kosten/Erlöse werden anteilig verteilt.<br/>
    - Alle Lieferanten und Dienstleister sind berücksichtigt."]:::info';
    }
}