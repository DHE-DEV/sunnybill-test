# Mermaid-Chart Template für Solaranlagen

Dieses Template wird automatisch mit aktuellen Daten aus der Datenbank gefüllt. Alle Platzhalter werden bei der Code-Generierung durch die entsprechenden Werte ersetzt.

## Vollständiges Beispiel-Template

```mermaid
flowchart TD
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

    %% Dynamische Sektionen (werden automatisch generiert)
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
    SA --- UpdateInfo
```

## Verfügbare Platzhalter

### Grunddaten der Solaranlage
- `{{plant_name}}` - Name der Solaranlage
- `{{plant_location}}` - Standort der Anlage
- `{{plant_capacity}}` - Gesamtkapazität in kWp
- `{{plant_status}}` - Aktueller Status der Anlage
- `{{plant_commissioning_date}}` - Inbetriebnahmedatum
- `{{plant_annual_yield}}` - Jährlicher Ertrag in kWh

### Technische Daten
- `{{plant_mastr_nr}}` - Marktstammdatenregister-Nummer
- `{{plant_malo_id}}` - Marktlokations-ID
- `{{plant_melo_id}}` - Messlokations-ID
- `{{plant_vnb_process_number}}` - VNB-Vorgangsnummer
- `{{plant_pv_soll_project_number}}` - PV-Soll Projektnummer

### Dynamische Sektionen (automatisch generiert)
- `{{customers}}` - Alle Kunden-Knoten mit Beteiligungen
- `{{suppliers}}` - Alle Lieferanten-Knoten mit Rollen
- `{{contracts}}` - Alle Vertrags-Knoten
- `{{customer_connections}}` - Verbindungen zwischen Kunden und Anlage
- `{{supplier_connections}}` - Verbindungen zwischen Lieferanten und Verträgen
- `{{billing_connections}}` - Abrechnungsverbindungen (NEXT, EWE, etc.)

### Statistiken
- `{{customer_count}}` - Anzahl der Kunden
- `{{supplier_count}}` - Anzahl der Lieferanten
- `{{contract_count}}` - Anzahl der Verträge
- `{{total_participation}}` - Gesamtbeteiligung in Prozent

### Zeitstempel
- `{{last_updated}}` - Zeitpunkt der letzten Aktualisierung (dd.mm.yyyy hh:mm)
- `{{generation_date}}` - Generierungsdatum (dd.mm.yyyy)
- `{{generation_time}}` - Generierungszeit (hh:mm:ss)

## Einfaches Template-Beispiel

```mermaid
flowchart TD
    %% Styling
    classDef plant fill:#FFD700,stroke:#FF8C00,stroke-width:2px
    classDef customer fill:#87CEEB,stroke:#4682B4,stroke-width:2px
    classDef supplier fill:#98FB98,stroke:#32CD32,stroke-width:2px

    %% Solaranlage
    SA["{{plant_name}}<br/>{{plant_capacity}}<br/>{{plant_location}}"]:::plant

    %% Automatisch generierte Bereiche
    {{customers}}
    {{suppliers}}
    {{contracts}}

    %% Verbindungen
    {{customer_connections}}
    {{supplier_connections}}

    %% Info
    Info["Aktualisiert: {{last_updated}}<br/>Kunden: {{customer_count}} | Lieferanten: {{supplier_count}}"]
```

## Verwendung

1. Kopieren Sie eines der Templates oben
2. Fügen Sie es in das "Mermaid Template" Feld ein
3. Wählen Sie eine Solaranlage aus
4. Klicken Sie auf "Vorschau generieren"
5. Alle Platzhalter werden automatisch mit den aktuellen Daten der ausgewählten Solaranlage gefüllt

## Hinweise

- Die dynamischen Sektionen (`{{customers}}`, `{{suppliers}}`, `{{contracts}}`) werden automatisch basierend auf den hinterlegten Daten generiert
- Verbindungen (`{{customer_connections}}`, `{{supplier_connections}}`, `{{billing_connections}}`) werden intelligent erstellt
- Alle Daten werden bei jeder Code-Generierung frisch aus der Datenbank geladen
- Das System erkennt automatisch bekannte Lieferanten (NEXT, EWE) und erstellt entsprechende Abrechnungsknoten