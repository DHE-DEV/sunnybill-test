# SunnyBill Lead-Management API
## Integrationsleitfaden für externe Anbieter

**Version:** 1.0  
**Datum:** August 2025  
**Base URL:** `https://sunnybill-test.test/api/app`

---

## 📋 Inhaltsverzeichnis

1. [Übersicht](#übersicht)
2. [Authentifizierung](#authentifizierung)
3. [API-Endpoints](#api-endpoints)
4. [Datenstrukturen](#datenstrukturen)
5. [Beispiele](#beispiele)
6. [Fehlerbehandlung](#fehlerbehandlung)
7. [Rate Limiting](#rate-limiting)
8. [Kontakt](#kontakt)

---

## 🔍 Übersicht

Die SunnyBill Lead-Management API ermöglicht es externen Systemen, Leads zu erstellen, zu verwalten und zu verfolgen. Alle Leads werden sicher in unserem System gespeichert und können über das Admin-Panel verwaltet werden.

### Hauptfunktionen:
- ✅ Lead-Erstellung
- ✅ Lead-Verwaltung (CRUD-Operationen)
- ✅ Lead-Qualifizierung (Ranking A-E)
- ✅ Lead-zu-Kunde Konvertierung
- ✅ Sichere Token-basierte Authentifizierung

---

## 🔐 Authentifizierung

### API-Token anfordern
Um die API zu nutzen, benötigen Sie einen API-Token mit entsprechenden Berechtigungen. 

**Kontaktieren Sie uns zur Token-Erstellung:**
- E-Mail: support@sunnybill.de
- Telefon: +49 XXX XXXXXXX

### Token verwenden
```http
Authorization: Bearer YOUR_API_TOKEN_HERE
Content-Type: application/json
```

### Verfügbare Berechtigungen:
- `leads:create` - Leads erstellen
- `leads:read` - Leads anzeigen/auflisten
- `leads:update` - Leads bearbeiten
- `leads:delete` - Leads löschen
- `leads:status` - Lead-Status ändern
- `leads:convert` - Leads zu Kunden konvertieren

---

## 🚀 API-Endpoints

### 1. Lead erstellen
```http
POST /leads
```

**Beschreibung:** Erstellt einen neuen Lead im System.

**Headers:**
```http
Authorization: Bearer YOUR_TOKEN
Content-Type: application/json
```

**Erforderliche Berechtigung:** `leads:create`

**Request Body (Minimum):**
```json
{
  "name": "Firmenname"
}
```

**Request Body (Vollständig):**
```json
{
  "name": "Beispiel GmbH & Co. KG",
  "contact_person": "Max Mustermann",
  "department": "Geschäftsführung",
  "email": "kontakt@beispiel.de",
  "phone": "+49 30 12345678",
  "website": "https://www.beispiel.de",
  "street": "Musterstraße 123",
  "address_line_2": "2. OG, Büro 42",
  "postal_code": "10115",
  "city": "Berlin",
  "state": "Berlin",
  "country": "Deutschland",
  "country_code": "DE",
  "ranking": "A",
  "notes": "Interessanter Lead mit großem Potenzial",
  "is_active": true
}
```

**Response (201 Created):**
```json
{
  "success": true,
  "message": "Lead erfolgreich erstellt",
  "data": {
    "id": 123,
    "name": "Beispiel GmbH & Co. KG",
    "customer_type": "lead",
    "contact_person": "Max Mustermann",
    "email": "kontakt@beispiel.de",
    "ranking": "A",
    "is_active": true,
    "created_at": "2025-08-26T13:30:00.000000Z",
    "updated_at": "2025-08-26T13:30:00.000000Z"
  }
}
```

### 2. Leads auflisten
```http
GET /leads
```

**Beschreibung:** Ruft eine paginierte Liste aller Leads ab.

**Erforderliche Berechtigung:** `leads:read`

**Query Parameter:**
- `page` (int): Seitennummer (Standard: 1)
- `per_page` (int): Einträge pro Seite (Max: 100, Standard: 15)
- `search` (string): Suchbegriff
- `ranking` (string): Filter nach Ranking (A, B, C, D, E)
- `city` (string): Filter nach Stadt
- `is_active` (boolean): Filter nach Status
- `sort_by` (string): Sortierfeld (id, name, created_at, etc.)
- `sort_direction` (string): Sortierrichtung (asc, desc)

**Response (200 OK):**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "name": "Beispiel GmbH",
      "contact_person": "Max Mustermann",
      "email": "kontakt@beispiel.de",
      "ranking": "A",
      "city": "Berlin",
      "is_active": true,
      "created_at": "2025-08-26T13:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 67
  }
}
```

### 3. Einzelnen Lead anzeigen
```http
GET /leads/{id}
```

**Beschreibung:** Ruft Details eines spezifischen Leads ab.

**Erforderliche Berechtigung:** `leads:read`

**Response (200 OK):**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "name": "Beispiel GmbH & Co. KG",
    "customer_type": "lead",
    "contact_person": "Max Mustermann",
    "department": "Geschäftsführung",
    "email": "kontakt@beispiel.de",
    "phone": "+49 30 12345678",
    "website": "https://www.beispiel.de",
    "street": "Musterstraße 123",
    "city": "Berlin",
    "ranking": "A",
    "notes": "Wichtige Notizen...",
    "is_active": true,
    "created_at": "2025-08-26T13:30:00.000000Z",
    "updated_at": "2025-08-26T13:30:00.000000Z"
  }
}
```

### 4. Lead aktualisieren
```http
PUT /leads/{id}
```

**Beschreibung:** Aktualisiert die Daten eines bestehenden Leads.

**Erforderliche Berechtigung:** `leads:update`

**Request Body:** Gleiche Felder wie beim Erstellen (alle optional außer bei Vollständiger Aktualisierung)

### 5. Lead löschen
```http
DELETE /leads/{id}
```

**Beschreibung:** Löscht einen Lead aus dem System.

**Erforderliche Berechtigung:** `leads:delete`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Lead erfolgreich gelöscht"
}
```

### 6. Lead-Status ändern
```http
PATCH /leads/{id}/status
```

**Beschreibung:** Aktiviert oder deaktiviert einen Lead.

**Erforderliche Berechtigung:** `leads:status`

**Request Body:**
```json
{
  "is_active": false
}
```

### 7. Lead zu Kunde konvertieren
```http
PATCH /leads/{id}/convert-to-customer
```

**Beschreibung:** Konvertiert einen Lead in einen Kunden.

**Erforderliche Berechtigung:** `leads:convert`

**Response (200 OK):**
```json
{
  "success": true,
  "message": "Lead erfolgreich zu Kunde konvertiert",
  "data": {
    "id": 123,
    "customer_type": "business",
    "updated_at": "2025-08-26T14:00:00.000000Z"
  }
}
```

### 8. API-Optionen abrufen
```http
GET /leads/options
```

**Beschreibung:** Ruft verfügbare Optionen für Dropdown-Felder ab.

**Erforderliche Berechtigung:** `leads:read`

---

## 📊 Datenstrukturen

### Lead-Objekt
```json
{
  "id": "integer - Eindeutige Lead-ID",
  "name": "string(255) - Firmenname (Pflichtfeld)",
  "contact_person": "string(255) - Ansprechpartner",
  "department": "string(255) - Abteilung",
  "email": "string(255) - E-Mail-Adresse (gültige E-Mail)",
  "phone": "string(255) - Telefonnummer",
  "website": "string(255) - Website-URL",
  "street": "string(255) - Straße",
  "address_line_2": "string(255) - Adresszusatz",
  "postal_code": "string(10) - Postleitzahl",
  "city": "string(255) - Stadt",
  "state": "string(255) - Bundesland",
  "country": "string(255) - Land (Standard: Deutschland)",
  "country_code": "string(3) - Ländercode (Standard: DE)",
  "ranking": "enum(A,B,C,D,E) - Lead-Qualifizierung",
  "notes": "text(5000) - Notizen",
  "is_active": "boolean - Aktiv/Inaktiv (Standard: true)",
  "customer_type": "string - Immer 'lead'",
  "created_at": "datetime - Erstellungsdatum",
  "updated_at": "datetime - Letzte Änderung"
}
```

### Lead-Rankings
| Code | Beschreibung | Bedeutung |
|------|--------------|-----------|
| A | Heißer Lead | Sehr interessiert, hohe Abschlusswahrscheinlichkeit |
| B | Warmer Lead | Interessiert, mittlere Abschlusswahrscheinlichkeit |
| C | Kalter Lead | Wenig Interesse, niedrige Priorität |
| D | Unqualifiziert | Lead muss noch qualifiziert werden |
| E | Nicht interessiert | Kein Interesse, Follow-up nicht empfohlen |

---

## 💡 Beispiele

### Beispiel 1: Minimaler Lead
```bash
curl -X POST https://sunnybill-test.test/api/app/leads \
  -H "Authorization: Bearer sb_abc123..." \
  -H "Content-Type: application/json" \
  -d '{"name": "Acme Corporation"}'
```

### Beispiel 2: Vollständiger Lead
```bash
curl -X POST https://sunnybill-test.test/api/app/leads \
  -H "Authorization: Bearer sb_abc123..." \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Tech Solutions GmbH",
    "contact_person": "Anna Schmidt",
    "department": "Einkauf",
    "email": "anna.schmidt@tech-solutions.de",
    "phone": "+49 40 987654321",
    "website": "https://www.tech-solutions.de",
    "street": "Innovationsallee 42",
    "postal_code": "20095",
    "city": "Hamburg",
    "state": "Hamburg",
    "country": "Deutschland",
    "country_code": "DE",
    "ranking": "A",
    "notes": "Großes Potenzial für Solaranlagen-Projekt. Kontakt über LinkedIn.",
    "is_active": true
  }'
```

### Beispiel 3: Leads mit Filter abrufen
```bash
curl -X GET "https://sunnybill-test.test/api/app/leads?ranking=A&city=Berlin&per_page=20" \
  -H "Authorization: Bearer sb_abc123..."
```

---

## ⚠️ Fehlerbehandlung

### Standard HTTP-Statuscodes
- `200` - Erfolgreich
- `201` - Erfolgreich erstellt
- `400` - Fehlerhafte Anfrage
- `401` - Nicht authentifiziert
- `403` - Berechtigung fehlt
- `404` - Ressource nicht gefunden
- `422` - Validierungsfehler
- `500` - Serverfehler

### Fehler-Response Format
```json
{
  "success": false,
  "message": "Beschreibung des Fehlers",
  "errors": {
    "field_name": [
      "Spezifische Fehlermeldung"
    ]
  }
}
```

### Beispiel Validierungsfehler (422)
```json
{
  "success": false,
  "message": "Validierungsfehler",
  "errors": {
    "email": [
      "The email must be a valid email address."
    ],
    "ranking": [
      "The selected ranking is invalid."
    ]
  }
}
```

### Beispiel Authentifizierungsfehler (401)
```json
{
  "success": false,
  "message": "Token fehlt oder ungültiges Format"
}
```

### Beispiel Berechtigungsfehler (403)
```json
{
  "success": false,
  "message": "Unzureichende Berechtigungen für diese Aktion",
  "required_abilities": ["leads:create"],
  "token_abilities": ["leads:read"]
}
```

---

## ⏱️ Rate Limiting

Zur Sicherstellung der Systemstabilität gelten folgende Limits:

- **Standard-Token:** 1000 Anfragen pro Stunde
- **Premium-Token:** 5000 Anfragen pro Stunde

Bei Überschreitung erhalten Sie Status `429 Too Many Requests`.

**Response Headers:**
```http
X-RateLimit-Limit: 1000
X-RateLimit-Remaining: 999
X-RateLimit-Reset: 1693056000
```

---

## 🔄 Versionierung

Die API-Version wird über die URL verwaltet. Aktuelle Version: `v1`

Änderungen werden wie folgt kommuniziert:
- **Major Changes:** Neue API-Version (Breaking Changes)
- **Minor Changes:** Rückwärtskompatible Erweiterungen
- **Patches:** Fehlerbehebungen

---

## 📞 Support & Kontakt

### Technischer Support
- **E-Mail:** api-support@sunnybill.de
- **Telefon:** +49 XXX XXXXXXX
- **Support-Zeiten:** Mo-Fr, 9:00-17:00 Uhr

### Dokumentation & Updates
- **API-Dokumentation:** https://docs.sunnybill.de/api
- **Status-Seite:** https://status.sunnybill.de
- **Changelog:** https://docs.sunnybill.de/changelog

### Token-Verwaltung
Für neue API-Token oder Berechtigungsänderungen kontaktieren Sie unser Support-Team.

---

## 📋 Checkliste für Integration

### Vor der Integration:
- [ ] API-Token angefordert und erhalten
- [ ] Benötigte Berechtigungen definiert
- [ ] Test-Umgebung eingerichtet
- [ ] Error-Handling implementiert

### Nach der Integration:
- [ ] Vollständige Tests durchgeführt
- [ ] Rate-Limiting berücksichtigt
- [ ] Monitoring eingerichtet
- [ ] Dokumentation erstellt

---

**© 2025 SunnyBill GmbH - Alle Rechte vorbehalten**

*Diese Dokumentation unterliegt der Verschwiegenheit und ist ausschließlich für autorisierte Partner bestimmt.*
