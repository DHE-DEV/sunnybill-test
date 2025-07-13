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
     * Standard-Template für Solaranlagen mit erweiterten Platzhaltern
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
    classDef technical fill:#E6E6FA,stroke:#9370DB,stroke-width:1px,color:#000

    %% Solaranlage Hauptknoten
    SA["🏭 Solaranlage<br/>{{plant_name}}<br/>📍 {{plant_location}}<br/>⚡ {{plant_capacity}}<br/>📊 Status: {{plant_status}}"]:::solarPlant

    %% Technische Daten
    TechData["📋 Technische Daten<br/>MaStR-Nr: {{plant_mastr_nr}}<br/>MaLo-ID: {{plant_malo_id}}<br/>MeLo-ID: {{plant_melo_id}}<br/>VNB-Vorgang: {{plant_vnb_process_number}}<br/>PV-Soll Projekt: {{plant_pv_soll_project_number}}"]:::technical

    %% Dynamische Sektionen
    {{customers}}

    {{suppliers}}

    {{contracts}}

    %% Verbindungen
    SA --- TechData
    {{customer_connections}}

    {{supplier_connections}}

    {{billing_connections}}

    %% Statistiken
    Stats["📊 Übersicht<br/>👥 Kunden: {{customer_count}}<br/>🏢 Lieferanten: {{supplier_count}}<br/>📄 Verträge: {{contract_count}}<br/>💰 Gesamtbeteiligung: {{total_participation}}"]:::info

    %% Aktualisierungsinfo
    UpdateInfo["🔄 Letzte Aktualisierung<br/>{{last_updated}}<br/>Daten werden automatisch<br/>aus der Datenbank geladen"]:::info

    SA --- Stats
    SA --- UpdateInfo';
    }

    /**
     * Erweiterte Template-Dokumentation
     */
    public static function getTemplateDocumentation(): array
    {
        return [
            'Grunddaten' => [
                '{{plant_name}}' => 'Name der Solaranlage',
                '{{plant_location}}' => 'Standort der Anlage',
                '{{plant_capacity}}' => 'Gesamtkapazität in kWp',
                '{{plant_status}}' => 'Aktueller Status der Anlage',
                '{{plant_commissioning_date}}' => 'Inbetriebnahmedatum',
                '{{plant_annual_yield}}' => 'Jährlicher Ertrag in kWh',
            ],
            'Technische Daten' => [
                '{{plant_mastr_nr}}' => 'Marktstammdatenregister-Nummer',
                '{{plant_malo_id}}' => 'Marktlokations-ID',
                '{{plant_melo_id}}' => 'Messlokations-ID',
                '{{plant_vnb_process_number}}' => 'VNB-Vorgangsnummer',
                '{{plant_pv_soll_project_number}}' => 'PV-Soll Projektnummer',
            ],
            'Dynamische Sektionen' => [
                '{{customers}}' => 'Automatisch generierte Kunden-Knoten',
                '{{suppliers}}' => 'Automatisch generierte Lieferanten-Knoten',
                '{{contracts}}' => 'Automatisch generierte Vertrags-Knoten',
                '{{customer_connections}}' => 'Verbindungen zwischen Kunden und Anlage',
                '{{supplier_connections}}' => 'Verbindungen zwischen Lieferanten und Verträgen',
                '{{billing_connections}}' => 'Abrechnungsverbindungen',
            ],
            'Statistiken' => [
                '{{customer_count}}' => 'Anzahl der Kunden',
                '{{supplier_count}}' => 'Anzahl der Lieferanten',
                '{{contract_count}}' => 'Anzahl der Verträge',
                '{{total_participation}}' => 'Gesamtbeteiligung in Prozent',
            ],
            'Zeitstempel' => [
                '{{last_updated}}' => 'Zeitpunkt der letzten Aktualisierung',
                '{{generation_date}}' => 'Generierungsdatum',
                '{{generation_time}}' => 'Generierungszeit',
            ],
        ];
    }
}