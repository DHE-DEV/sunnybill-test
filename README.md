# SunnyBill - Laravel 12 + Filament 3 + Lexoffice Integration

Ein vollständiges Rechnungsverwaltungssystem mit Laravel 12, Filament 3 Admin Panel und Lexoffice API-Integration.

## 🚀 Features

- **Kundenverwaltung** - Vollständige CRUD-Operationen für Kunden
- **Artikelverwaltung** - Artikel mit 6 Nachkommastellen für präzise Preiskalkulationen
- **Rechnungserstellung** - Intuitive Rechnungserstellung mit automatischer Berechnung
- **Lexoffice Integration** - Bidirektionale Synchronisation mit Lexoffice
- **PDF-Export** - Professionelle Rechnungs-PDFs
- **Admin Panel** - Modernes Filament 3 Interface mit voller Browserbreite
- **Responsive Design** - Optimiert für große Bildschirme und maximale Inhaltsdarstellung
- **Logging** - Vollständige Protokollierung aller Lexoffice-Operationen

## 📋 Anforderungen

- PHP 8.3+
- Laravel 12
- MySQL 8.0+
- Composer
- Node.js & NPM

## 🛠️ Installation

### 1. Repository klonen
```bash
git clone <repository-url>
cd sunnybill
```

### 2. Dependencies installieren
```bash
composer install
npm install
```

### 3. Umgebung konfigurieren
```bash
cp .env.example .env
php artisan key:generate
```

### 4. Datenbank konfigurieren
Bearbeiten Sie die `.env` Datei:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sunnybill
DB_USERNAME=root
DB_PASSWORD=your_password
```

### 5. Lexoffice API konfigurieren
```env
LEXOFFICE_API_KEY=your-lexoffice-api-key-here
LEXOFFICE_BASE_URL=https://api.lexoffice.io/v1/
```

### 6. Datenbank migrieren und seeden
```bash
php artisan migrate
php artisan db:seed
```

### 7. Admin-User erstellen
```bash
php artisan make:filament-user
```

### 8. Assets kompilieren
```bash
npm run build
```

### 9. Server starten
```bash
php artisan serve
```

Das Admin Panel ist unter `http://localhost:8000/admin` erreichbar.

## 🖥️ Layout-Optimierungen

Das Admin Panel ist für maximale Bildschirmnutzung optimiert:

- **Volle Browserbreite**: Nutzt die komplette verfügbare Breite
- **Kollabierbare Sidebar**: Mehr Platz für Inhalte auf Desktop
- **Responsive Tabellen**: Optimiert für große Datenmengen
- **Erweiterte Modals**: Dialoge nutzen bis zu 90% der Bildschirmbreite
- **Kompakte Navigation**: Effiziente Raumnutzung

### Layout-Features:
- Maximale Container-Breite entfernt
- Tabellen nutzen volle Breite
- Formulare erweitert für bessere Übersicht
- Responsive Design für verschiedene Bildschirmgrößen

## � Datenmodelle

### Kunden (customers)
- UUID als Primary Key
- Name, E-Mail, Telefon
- Vollständige Adressdaten
- Lexoffice-ID für Synchronisation

### Artikel (articles)
- UUID als Primary Key
- Name, Beschreibung
- Preis mit 6 Nachkommastellen
- Steuersatz (0%, 7%, 19%)
- Lexoffice-ID für Synchronisation

### Rechnungen (invoices)
- UUID als Primary Key
- Kunde-Referenz
- Rechnungsnummer (automatisch generiert)
- Status (Entwurf, Versendet, Bezahlt, Storniert)
- Gesamtsumme mit 6 Nachkommastellen
- Lexoffice-ID für Synchronisation

### Rechnungsposten (invoice_items)
- Artikel-Referenz
- Menge, Einzelpreis, Steuersatz
- Gesamtpreis mit automatischer Berechnung
- Individuelle Beschreibung möglich

### Lexoffice Logs (lexoffice_logs)
- Vollständige Protokollierung aller API-Calls
- Request/Response-Daten
- Fehlerbehandlung und -protokollierung

## 🔌 Lexoffice Integration

### API-Verbindung testen
```bash
php artisan sync:lexoffice --test
```

### Kunden von Lexoffice importieren
```bash
php artisan sync:lexoffice --customers
```

### Artikel von Lexoffice importieren
```bash
php artisan sync:lexoffice --articles
```

### Kunden zu Lexoffice exportieren
```bash
php artisan sync:lexoffice --export-customers
```

### Artikel zu Lexoffice exportieren
```bash
php artisan sync:lexoffice --export-articles
```

### Vollständige Synchronisation (nur Import)
```bash
php artisan sync:lexoffice
```

## 💰 Preisbehandlung

- **Intern**: Alle Preise werden mit 6 Nachkommastellen gespeichert
- **Lexoffice**: Preise werden vor dem Export auf 2 Nachkommastellen gerundet
- **PDF**: Preise werden mit 2 Nachkommastellen angezeigt
- **Admin Panel**: Vollständige 6 Nachkommastellen sichtbar

## 📄 Rechnungs-Features

### Erstellung
- Kunde auswählen oder neu anlegen
- Artikel aus Katalog wählen
- Automatische Preisübernahme
- Manuelle Anpassungen möglich
- Automatische Gesamtberechnung

### Export
- **PDF-Download**: Professionelle Rechnungsvorlage
- **Lexoffice-Export**: Direkte Übertragung zur Archivierung
- **Status-Tracking**: Vollständige Nachverfolgung

### Verwaltung
- Übersichtliche Tabellen mit Filtern
- Status-Management
- Kundenhistorie
- Lexoffice-Synchronisationsstatus

## 🎨 Admin Panel Features

### Dashboard
- Übersicht über alle Entitäten
- Schnellzugriff auf wichtige Funktionen
- Status-Übersichten

### Kunden
- CRUD-Operationen
- Rechnungshistorie pro Kunde
- Lexoffice-Import-Button
- Adressverwaltung

### Artikel
- Präzise Preiseingabe (6 Nachkommastellen)
- Steuersatz-Verwaltung
- Lexoffice-Import-Button
- Beschreibungen

### Rechnungen
- Intuitive Rechnungserstellung
- Artikel-Auswahl mit Preisübernahme
- PDF-Download
- Lexoffice-Export-Button
- Status-Management

### Logs
- Vollständige Lexoffice-API-Protokolle
- Fehleranalyse
- Request/Response-Details
- Filterbare Übersicht

## 🔧 Konfiguration

### Lexoffice API
1. Lexoffice-Account erstellen
2. API-Key generieren
3. In `.env` eintragen
4. Verbindung testen

### PDF-Anpassungen
Die PDF-Vorlage kann unter `resources/views/invoices/pdf.blade.php` angepasst werden.

### Rechnungsnummern
Automatische Generierung im Format: `YYYY-NNNN` (z.B. 2025-0001)

## 🚨 Wichtige Hinweise

### Lexoffice-Limits
- Lexoffice unterstützt nur 2 Nachkommastellen
- Preise werden automatisch gerundet
- Rate-Limiting beachten

### Datenintegrität
- UUIDs für alle Hauptentitäten
- Foreign Key Constraints
- Automatische Berechnungen

### Sicherheit
- API-Keys sicher aufbewahren
- Regelmäßige Backups
- Zugriffsrechte prüfen

## 📝 Commands

```bash
# Lexoffice-Synchronisation
php artisan sync:lexoffice [--customers] [--articles] [--export-customers] [--export-articles] [--test]

# Filament-User erstellen
php artisan make:filament-user

# Cache leeren
php artisan optimize:clear

# Wartungsmodus
php artisan down
php artisan up
```

## 🐛 Troubleshooting

### Lexoffice-Verbindung
1. API-Key prüfen
2. Netzwerkverbindung testen
3. Logs in `lexoffice_logs` Tabelle prüfen

### PDF-Generierung
1. DomPDF-Konfiguration prüfen
2. Speicherplatz verfügbar?
3. Schreibrechte für Storage-Ordner

### Performance
1. Database-Indizes prüfen
2. Query-Optimierung
3. Cache-Konfiguration

## 📞 Support

Bei Fragen oder Problemen:
1. Logs prüfen (`storage/logs/laravel.log`)
2. Lexoffice-Logs im Admin Panel prüfen
3. API-Dokumentation konsultieren

## 🔄 Updates

```bash
# Dependencies aktualisieren
composer update
npm update

# Migrationen ausführen
php artisan migrate

# Cache leeren
php artisan optimize:clear
```

---

**Entwickelt mit ❤️ für effiziente Rechnungsverwaltung**
