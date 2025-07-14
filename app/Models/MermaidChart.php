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
            'external_billing_workflow' => 'Workflow Externe Abrechnung',
            'solar_plant_connections' => 'Solaranlagen-Verknüpfungen',
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
     * Standard-Template für den Workflow externer Abrechnungen
     */
    public static function getDefaultExternalBillingWorkflowTemplate(): string
    {
        return 'flowchart TD
    %% Styling
    classDef process fill:#E3F2FD,stroke:#1976D2,stroke-width:2px
    classDef decision fill:#FFF3E0,stroke:#F57C00,stroke-width:2px
    classDef storage fill:#E8F5E8,stroke:#388E3C,stroke-width:2px
    classDef action fill:#FCE4EC,stroke:#C2185B,stroke-width:2px
    classDef endStyle fill:#F3E5F5,stroke:#7B1FA2,stroke-width:2px

    %% Start
    Start([Externe Abrechnung empfangen]):::process
    
    %% Eingangswege
    Start --> Eingang{Eingangsweg?}:::decision
    Eingang --> Post[Per Post erhalten]:::process
    Eingang --> Email[Per E-Mail erhalten]:::process
    Eingang --> Download[Per Download erhalten]:::process
    
    %% Post-Pfad
    Post --> PostScan[Abrechnung einscannen]:::action
    PostScan --> PostPDF[PDF-Datei zwischenspeichern]:::storage
    
    %% E-Mail-Pfad  
    Email --> EmailPDF[PDF-Datei zwischenspeichern]:::storage
    
    %% Download-Pfad
    Download --> DownloadPDF[PDF-Datei zwischenspeichern]:::storage
    DownloadPDF --> DownloadFile[Abrechnung muss per Download heruntergeladen werden]:::action
    
    %% Konvergenz zum gemeinsamen Workflow
    PostPDF --> Menu[Menupunkt Lieferanten - Abrechnungen offnen]:::process
    EmailPDF --> Menu
    DownloadFile --> Menu
    
    %% Gemeinsamer Workflow
    Menu --> NewBilling[Schaltflaeche Neue Abrechnung anklicken]:::action
    NewBilling --> SelectContract[Lieferantenvertrag auswaehlen]:::action
    SelectContract --> FillFields[Felder ausfuellen und auf Erstellen klicken]:::action
    FillFields --> Redirect[Es erfolgt eine automatische Weiterleitung zur Detailseite der Abrechnung]:::process
    Redirect --> DocumentTab[Unten in der Liste im Tab Dokumente die Schaltflaeche Dokument hinzufuegen anklicken]:::action
    DocumentTab --> UploadChoice{Datei-Upload}:::decision
    UploadChoice --> UploadDirect[Ziehen Sie die Abrechnungsdatei in das Feld Datei]:::action
    UploadChoice --> UploadSelect[oder waehlen Sie die Datei manuell nach klicken auf auswaehlen aus]:::action
    UploadDirect --> SetName[Vergeben Sie einen Dokumentnamen der in den Listen angezeigt wird und waehlen Sie eine Kategorie]:::action
    UploadSelect --> SetName
    SetName --> Submit[Klicken Sie auf die Schaltflaeche Absenden]:::action
    Submit --> Complete[Abrechnung vollstaendig erfasst]:::endStyle';
    }

    /**
     * Standard-Template für Solaranlagen-Verknüpfungen und Abrechnungsstrukturen
     */
    public static function getDefaultSolarPlantConnectionsTemplate(): string
    {
        return 'flowchart TD
    %% Styling
    classDef solarPlant fill:#FFD700,stroke:#FF8C00,stroke-width:3px,color:#000,font-weight:bold
    classDef supplier fill:#98FB98,stroke:#32CD32,stroke-width:2px,color:#000
    classDef contract fill:#DDA0DD,stroke:#9370DB,stroke-width:2px,color:#000
    classDef customer fill:#87CEEB,stroke:#4682B4,stroke-width:2px,color:#000
    classDef billing fill:#FFB6C1,stroke:#DC143C,stroke-width:2px,color:#000
    classDef process fill:#E3F2FD,stroke:#1976D2,stroke-width:2px,color:#000

    %% Zentrale Solaranlage
    SA["Solaranlage<br/>Name: {{plant_name}}<br/>Standort: {{plant_location}}<br/>Kapazitaet: {{plant_capacity}} kWp"]:::solarPlant

    %% Lieferanten/Dienstleister
    EON["E.ON<br/>Energieversorger"]:::supplier
    NEXT["NEXT Kraftwerke<br/>Direktvermarkter"]:::supplier
    WESTNETZ["Westnetz<br/>Netzbetreiber"]:::supplier

    %% Verträge
    CONTRACT1["Vertrag 1<br/>E.ON Liefervertrag<br/>Gueltig: 2024-2026"]:::contract
    CONTRACT2["Vertrag 2<br/>NEXT Direktvermarktung<br/>Gueltig: 2023-2025"]:::contract
    CONTRACT3["Vertrag 3<br/>Westnetz Netzanschluss<br/>Gueltig: unbefristet"]:::contract

    %% Investoren/Kunden
    CUSTOMER1["Investor 1<br/>Max Mustermann<br/>45% Beteiligung"]:::customer
    CUSTOMER2["Investor 2<br/>Anna Schmidt<br/>35% Beteiligung"]:::customer
    CUSTOMER3["Investor 3<br/>Peter Weber<br/>20% Beteiligung"]:::customer

    %% Abrechnungen von Lieferanten
    BILL_EON["E.ON Abrechnung<br/>Rechnung: 1.500 EUR<br/>Zeitraum: Q1 2024"]:::billing
    BILL_NEXT["NEXT Gutschrift<br/>Gutschrift: 2.800 EUR<br/>Zeitraum: Q1 2024"]:::billing
    BILL_WESTNETZ["Westnetz Rechnung<br/>Rechnung: 450 EUR<br/>Zeitraum: Q1 2024"]:::billing

    %% Prozentuale Aufteilung
    SPLIT["Prozentuale Aufteilung<br/>nach Beteiligungsquoten"]:::process

    %% Kundenrechnungen
    CUSTOMER_BILL1["Kundenabrechnung 1<br/>Max Mustermann<br/>45% von Gesamtbetrag"]:::billing
    CUSTOMER_BILL2["Kundenabrechnung 2<br/>Anna Schmidt<br/>35% von Gesamtbetrag"]:::billing
    CUSTOMER_BILL3["Kundenabrechnung 3<br/>Peter Weber<br/>20% von Gesamtbetrag"]:::billing

    %% Verbindungen: Solaranlage zu Vertraegen
    SA -.-> CONTRACT1
    SA -.-> CONTRACT2
    SA -.-> CONTRACT3

    %% Verbindungen: Vertraege zu Lieferanten
    CONTRACT1 --> EON
    CONTRACT2 --> NEXT
    CONTRACT3 --> WESTNETZ

    %% Verbindungen: Solaranlage zu Investoren
    SA --> CUSTOMER1
    SA --> CUSTOMER2
    SA --> CUSTOMER3

    %% Verbindungen: Lieferanten zu Abrechnungen
    EON --> BILL_EON
    NEXT --> BILL_NEXT
    WESTNETZ --> BILL_WESTNETZ

    %% Verbindungen: Abrechnungen zur Aufteilung
    BILL_EON --> SPLIT
    BILL_NEXT --> SPLIT
    BILL_WESTNETZ --> SPLIT

    %% Verbindungen: Aufteilung zu Kundenrechnungen
    SPLIT --> CUSTOMER_BILL1
    SPLIT --> CUSTOMER_BILL2
    SPLIT --> CUSTOMER_BILL3

    %% Zusaetzliche Informationen
    INFO["Abrechnungslogik<br/>Alle Lieferantenrechnungen werden gesammelt<br/>Gesamtbetrag wird nach Beteiligungsquoten aufgeteilt<br/>Jeder Investor erhaelt eine anteilsmaessige Abrechnung<br/>Sowohl Rechnungen als auch Gutschriften werden beruecksichtigt"]:::process

    SA --- INFO';
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
