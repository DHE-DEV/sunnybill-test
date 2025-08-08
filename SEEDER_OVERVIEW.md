# Database Seeder Übersicht

## Verfügbare Seeder

### 1. **DatabaseSeeder.php** (Haupt-Seeder)
Führt alle anderen Seeder in der richtigen Reihenfolge aus:
- `php artisan db:seed`

### 2. **Standard Seeder** (bereits vorhanden)
- **UserSeeder** - Basis-Benutzer
- **CustomerSeeder** - Grund-Kundendaten
- **ArticleSeeder** - Artikel/Produkte
- **SolarPlantSeeder** - Basis-Solaranlagen
- **SolarPlantMilestoneSeeder** - Meilensteine
- **PlantParticipationSeeder** - Beteiligungen
- **SupplierSeeder** - Grund-Lieferanten
- **SupplierRecognitionPatternSeeder** - Erkennungsmuster
- **PdfExtractionRuleSeeder** - PDF-Extraktionsregeln
- **ContractMatchingRuleSeeder** - Vertragszuordnungsregeln
- **MaloIdFieldConfigSeeder** - MaLo-ID Konfiguration
- **EpIdFieldConfigSeeder** - EP-ID Konfiguration
- **ExternalBillingWorkflowChartSeeder** - Abrechnungsworkflows

### 3. **ComprehensiveTestDataSeeder** ⭐ (NEU)
Umfassende Testdaten für Development/Testing:

#### 🏢 **Zusätzliche Kunden** (20 Stück)
- **Privatkunden (10):** Michael Fischer, Sarah Wagner, Andreas Becker, etc.
- **Geschäftskunden (10):** TechStart GmbH, Grüne Zukunft AG, etc.
- Vollständige Kontaktdaten, Adressen, Kundennummern

#### 🏭 **Zusätzliche Lieferanten** (10 Stück)
- SolarTech Nord GmbH, Bayern Energie Solutions AG, etc.
- Realistische deutsche Solar-Unternehmen
- Vollständige Firmendaten und Kontakte

#### ☀️ **Zusätzliche Solaranlagen** (15 Stück)
- **Kleinere Anlagen (12):** 9,8 kWp - 234,6 kWp
- **Große Solarparks (3):** 8.920 - 22.850 kWp
- Realistische deutsche Standorte
- Korrekte kWp-Leistungswerte

#### 📞 **Telefonnummern**
- 1-3 Telefonnummern pro Kunde
- Mobile, Festnetz, Geschäftsnummern
- Korrekte deutsche Vorwahlen

#### 🔑 **API-Tokens**
- Full Access Token (Vollzugriff)
- Phone Numbers Token (Telefonnummern-API)
- Solar Plants Token (Solaranlagen-API)  
- Customers Token (Kunden-API)

## Seeder Ausführung

### Alle Seeder ausführen:
```bash
php artisan db:seed
```

### Nur Testdaten-Seeder:
```bash
php artisan db:seed --class=ComprehensiveTestDataSeeder
```

### Datenbank zurücksetzen und neu seeden:
```bash
php artisan migrate:fresh --seed
```

## Testdaten-Statistik

Nach vollständiger Seeder-Ausführung:

- **👥 Benutzer:** ~3-5 (Admin + API Test User)
- **🏢 Kunden:** ~25-30 (Standard + zusätzliche)
- **🏭 Lieferanten:** ~15-20 (Standard + zusätzliche)
- **☀️ Solaranlagen:** ~20-25 (mit korrekten kWp-Werten)
- **📞 Telefonnummern:** ~50-100 (1-3 pro Kunde)
- **🔑 API-Tokens:** ~4-6 (für verschiedene Bereiche)
- **📊 Artikel:** Standard-Solartechnik-Artikel
- **⚙️ Konfigurationen:** Alle PDF-Regeln, Workflow-Charts, etc.

## Qualität der Testdaten

✅ **Realistische deutsche Daten**
- Echte Städte und Postleitzahlen
- Deutsche Firmen- und Personennamen
- Korrekte Telefonnummern-Formate

✅ **Vollständige API-Abdeckung**
- Alle Hauptentitäten abgedeckt
- Verschiedene Zugriffsebenen
- Ready für Postman/API-Tests

✅ **Vielfältige Solaranlagen**
- Kleine private Anlagen (9,8 kWp)
- Gewerbeanlagen (50-200 kWp)
- Große Solarparks (8-23 MWp)

✅ **Konsistente Datenstrukturen**
- Korrekte Beziehungen zwischen Entitäten
- Gültige IDs und Referenzen
- Realistische Zeitstempel
