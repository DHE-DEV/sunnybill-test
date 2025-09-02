# Leads-API Dokumentation

## Übersicht
Die Leads-API ermöglicht die vollständige Verwaltung von Leads (CRUD) sowie spezielle Aktionen wie die Konvertierung zu Kunden.

**Base URL:** `/api/app/leads`

---

## 🔐 Authentifizierung
Alle Endpoints erfordern ein gültiges App-Token im Header:
```
Authorization: Bearer YOUR_APP_TOKEN
```

### Erforderliche Berechtigungen:
- `leads:read` - Leads lesen
- `leads:create` - Leads erstellen
- `leads:update` - Leads aktualisieren
- `leads:delete` - Leads löschen
- `leads:status` - Lead-Status ändern
- `leads:convert` - Leads zu Kunden konvertieren

---

## 📋 1. Leads auflisten

### `GET /api/app/leads`

**Berechtigung:** `leads:read`

**Beschreibung:** Listet alle Leads mit erweiterten Filter- und Suchoptionen auf.

### Query Parameter:

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `ranking` | string | Filtert nach Lead-Ranking | `A`, `B`, `C`, `D`, `E` |
| `city` | string | Filtert nach Stadt (LIKE-Suche) | `Berlin` |
| `is_active` | boolean | Filtert nach Aktivitätsstatus | `true`, `false` |
| `search` | string | Sucht in Name, E-Mail, Kundennummer, Telefon, Stadt | `Max Mustermann` |
| `sort_by` | string | Sortierfeld | `created_at`, `name`, `ranking`, `customer_number`, `city` |
| `sort_direction` | string | Sortierrichtung | `asc`, `desc` |
| `per_page` | integer | Anzahl pro Seite (max. 100) | `25` |
| `page` | integer | Seitenzahl | `1` |

### Beispiel Request:
```http
GET /api/app/leads?ranking=A&city=Berlin&search=Max&per_page=25&sort_by=created_at&sort_direction=desc
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Max Mustermann",
      "contact_person": "Max Mustermann",
      "department": "Geschäftsführung",
      "customer_number": "LD-000001",
      "email": "max.mustermann@example.com",
      "phone": "+49 123 456789",
      "website": "https://example.com",
      "street": "Musterstraße",
      "address_line_2": "2. Stock",
      "postal_code": "12345",
      "city": "Berlin",
      "state": "Berlin",
      "country": "Deutschland",
      "country_code": "DE",
      "notes": "Interessent für große Solaranlage",
      "contact_source": "Website Kontaktformular",
      "is_active": true,
      "customer_type": "lead",
      "ranking": "A",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 3,
    "per_page": 25,
    "total": 67
  }
}
```

---

## 👁️ 2. Einzelnen Lead anzeigen

### `GET /api/app/leads/{lead}`

**Berechtigung:** `leads:read`

**Beschreibung:** Zeigt detaillierte Informationen eines einzelnen Leads.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `lead` | integer | ID des Leads |

### Beispiel Request:
```http
GET /api/app/leads/1
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Max Mustermann",
    "contact_person": "Max Mustermann",
    "department": "Geschäftsführung",
    "customer_number": "LD-000001",
    "email": "max.mustermann@example.com",
    "phone": "+49 123 456789",
    "website": "https://example.com",
    "street": "Musterstraße",
    "address_line_2": "2. Stock",
    "postal_code": "12345",
    "city": "Berlin",
    "state": "Berlin",
    "country": "Deutschland",
    "country_code": "DE",
    "notes": "Interessent für große Solaranlage. Bereits Vorab-Gespräch geführt.",
    "contact_source": "Website Kontaktformular - Interesse an 50kWp Anlage",
    "is_active": true,
    "customer_type": "lead",
    "ranking": "A",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

---

## ➕ 3. Neuen Lead erstellen

### `POST /api/app/leads`

**Berechtigung:** `leads:create`

**Beschreibung:** Erstellt einen neuen Lead mit automatischer Lead-Nummer-Generierung.

### Request Body:
```json
{
  "name": "Anna Schmidt GmbH",
  "contact_person": "Anna Schmidt",
  "department": "Einkauf",
  "email": "anna.schmidt@example.com",
  "phone": "+49 987 654321",
  "website": "https://anna-schmidt.de",
  "street": "Teststraße",
  "address_line_2": "Büro 3",
  "postal_code": "54321",
  "city": "Hamburg",
  "state": "Hamburg",
  "country": "Deutschland",
  "country_code": "DE",
  "ranking": "B",
  "notes": "Kontakt über Messe hergestellt",
  "contact_source": "Messe Hamburg - Stand 42, interessiert an gewerblicher Anlage",
  "is_active": true
}
```

### Validierungsregeln:

| Feld | Regeln | Beschreibung |
|------|--------|--------------|
| `name` | **Pflicht** - Max. 255 Zeichen | Name/Firma des Leads |
| `contact_person` | Optional - Max. 255 Zeichen | Ansprechpartner |
| `department` | Optional - Max. 255 Zeichen | Abteilung des Ansprechpartners |
| `email` | Optional - Gültige E-Mail, Max. 255 Zeichen | E-Mail-Adresse |
| `phone` | Optional - Max. 255 Zeichen | Telefonnummer |
| `website` | Optional - Gültige URL, Max. 255 Zeichen | Website |
| `street` | Optional - Max. 255 Zeichen | Straße |
| `address_line_2` | Optional - Max. 255 Zeichen | Adresszusatz |
| `postal_code` | Optional - Max. 10 Zeichen | Postleitzahl |
| `city` | Optional - Max. 255 Zeichen | Stadt |
| `state` | Optional - Max. 255 Zeichen | Bundesland/Staat |
| `country` | Optional - Max. 255 Zeichen | Land |
| `country_code` | Optional - Max. 3 Zeichen | Ländercode |
| `ranking` | Optional - `A`, `B`, `C`, `D`, `E` | Lead-Bewertung |
| `notes` | Optional - Max. 5000 Zeichen | Notizen zum Lead |
| `contact_source` | Optional - Max. 1000 Zeichen | **Herkunft des Kontaktes** (z.B. Website, Empfehlung, Messe, Telefonakquise) |
| `is_active` | Optional - Boolean (Standard: `true`) | Aktiv/Inaktiv |

### Beispiel Request:
```http
POST /api/app/leads
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "name": "Anna Schmidt GmbH",
  "contact_person": "Anna Schmidt", 
  "email": "anna.schmidt@example.com",
  "phone": "+49 987 654321",
  "city": "Hamburg",
  "ranking": "B",
  "contact_source": "Messe Hamburg - Stand 42, interessiert an gewerblicher Anlage"
}
```

### Response (201 Created):
```json
{
  "success": true,
  "message": "Lead erfolgreich erstellt",
  "data": {
    "id": 123,
    "name": "Anna Schmidt GmbH",
    "contact_person": "Anna Schmidt",
    "email": "anna.schmidt@example.com",
    "phone": "+49 987 654321",
    "city": "Hamburg",
    "country": "Deutschland",
    "country_code": "DE",
    "ranking": "B",
    "contact_source": "Messe Hamburg - Stand 42, interessiert an gewerblicher Anlage",
    "customer_type": "lead",
    "is_active": true,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z"
  }
}
```

### Fehler Response (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "Validierungsfehler",
  "errors": {
    "name": ["Das Name-Feld ist erforderlich."],
    "ranking": ["Das Ranking muss A, B, C, D oder E sein."]
  }
}
```

---

## ✏️ 4. Lead aktualisieren

### `PUT /api/app/leads/{lead}`

**Berechtigung:** `leads:update`

**Beschreibung:** Aktualisiert einen bestehenden Lead. Partielle Updates sind möglich.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `lead` | integer | ID des Leads |

### Request Body:
```json
{
  "phone": "+49 987 654321 (neu)",
  "ranking": "A",
  "contact_source": "Telefonakquise - Follow-up nach Messe, sehr interessiert",
  "notes": "Lead wurde hochgestuft nach intensivem Gespräch"
}
```

**Hinweis:** Alle Felder sind optional. Nur übermittelte Felder werden aktualisiert.

### Beispiel Request:
```http
PUT /api/app/leads/123
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "ranking": "A",
  "contact_source": "Telefonakquise - Follow-up nach Messe, sehr interessiert",
  "notes": "Lead wurde hochgestuft nach intensivem Gespräch"
}
```

### Response:
```json
{
  "success": true,
  "message": "Lead erfolgreich aktualisiert",
  "data": {
    "id": 123,
    "name": "Anna Schmidt GmbH",
    "ranking": "A",
    "contact_source": "Telefonakquise - Follow-up nach Messe, sehr interessiert",
    "notes": "Lead wurde hochgestuft nach intensivem Gespräch",
    "updated_at": "2024-01-15T15:45:00.000000Z",
    "...": "..."
  }
}
```

---

## 🗑️ 5. Lead löschen

### `DELETE /api/app/leads/{lead}`

**Berechtigung:** `leads:delete`

**Beschreibung:** Löscht einen Lead permanent.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `lead` | integer | ID des Leads |

### Beispiel Request:
```http
DELETE /api/app/leads/123
Authorization: Bearer your_app_token_here
```

### Response (200 OK):
```json
{
  "success": true,
  "message": "Lead erfolgreich gelöscht"
}
```

---

## 📊 6. Lead-Status ändern

### `PATCH /api/app/leads/{lead}/status`

**Berechtigung:** `leads:status`

**Beschreibung:** Ändert schnell nur den Aktivitätsstatus eines Leads.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `lead` | integer | ID des Leads |

### Request Body:
```json
{
  "is_active": false
}
```

### Beispiel Request:
```http
PATCH /api/app/leads/123/status
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "is_active": false
}
```

### Response:
```json
{
  "success": true,
  "message": "Lead erfolgreich deaktiviert",
  "data": {
    "id": 123,
    "name": "Anna Schmidt GmbH",
    "is_active": false,
    "updated_at": "2024-01-15T16:00:00.000000Z",
    "...": "..."
  }
}
```

---

## 🔄 7. Lead zu Kunde konvertieren

### `PATCH /api/app/leads/{lead}/convert-to-customer`

**Berechtigung:** `leads:convert`

**Beschreibung:** Konvertiert einen Lead zu einem regulären Kunden (ändert customer_type von 'lead' zu 'business').

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `lead` | integer | ID des Leads |

### Beispiel Request:
```http
PATCH /api/app/leads/123/convert-to-customer
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "message": "Lead erfolgreich zu Kunde konvertiert",
  "data": {
    "id": 123,
    "name": "Anna Schmidt GmbH",
    "customer_type": "business",
    "contact_source": "Messe Hamburg - Stand 42, interessiert an gewerblicher Anlage",
    "updated_at": "2024-01-15T16:15:00.000000Z",
    "...": "..."
  }
}
```

**Hinweis:** Nach der Konvertierung kann der Lead über die Kunden-API (`/api/app/customers/{customer}`) verwaltet werden.

---

## ⚙️ 8. Lead-Optionen

### `GET /api/app/leads/options`

**Berechtigung:** `leads:read`

**Beschreibung:** Liefert verfügbare Optionen für Lead-Formulare.

### Beispiel Request:
```http
GET /api/app/leads/options
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "rankings": {
      "A": "Heißer Lead (A)",
      "B": "Warmer Lead (B)", 
      "C": "Kalter Lead (C)",
      "D": "Unqualifiziert (D)",
      "E": "Nicht interessiert (E)"
    },
    "countries": {
      "Deutschland": "Deutschland",
      "Österreich": "Österreich",
      "Schweiz": "Schweiz"
    },
    "country_codes": {
      "DE": "Deutschland",
      "AT": "Österreich", 
      "CH": "Schweiz"
    },
    "boolean_options": {
      "is_active": {
        "true": "Aktiv",
        "false": "Inaktiv"
      }
    }
  }
}
```

---

## 🚫 Fehlerbehandlung

### Standard HTTP Status Codes:
- `200` - OK (Erfolgreiche GET, PUT, PATCH, DELETE)
- `201` - Created (Erfolgreiche POST)
- `400` - Bad Request (Allgemeine Fehler)
- `401` - Unauthorized (Fehlendes/ungültiges Token)
- `403` - Forbidden (Fehlende Berechtigung)
- `404` - Not Found (Lead nicht gefunden)
- `422` - Unprocessable Entity (Validierungsfehler)

### Fehler Response Format:
```json
{
  "success": false,
  "message": "Beschreibung des Fehlers",
  "errors": {
    "field_name": ["Spezifische Fehlermeldung"]
  }
}
```

---

## 📚 Zusätzliche Informationen

### Lead-Rankings:
- **A** - Heißer Lead (hohe Kaufbereitschaft)
- **B** - Warmer Lead (mittleres Interesse)
- **C** - Kalter Lead (geringes Interesse)
- **D** - Unqualifiziert (noch nicht bewertet)
- **E** - Nicht interessiert (Absage erhalten)

### Kontakt-Herkunft (contact_source):
Das Feld `contact_source` ist besonders wichtig für die Lead-Verfolgung und Marketing-Analyse. Typische Werte sind:
- "Website Kontaktformular"
- "Messe [Name] - Stand [Nummer]"
- "Telefonakquise"
- "Empfehlung von [Kundename]"
- "Google Ads Kampagne [Name]"
- "Social Media - [Plattform]"
- "Newsletter Anmeldung"
- "Direktansprache"

### Automatische Lead-Nummer:
- Format: `LD-XXXXXX` (6-stellig, mit Nullen aufgefüllt)
- Beispiel: `LD-000001`, `LD-000123`

### Standard-Werte:
- `country`: "Deutschland" (wenn nicht angegeben)
- `country_code`: "DE" (wenn nicht angegeben)
- `is_active`: `true` (wenn nicht angegeben)
- `customer_type`: Wird automatisch auf "lead" gesetzt

Diese API-Dokumentation deckt alle verfügbaren Lead-Operationen ab und bietet eine vollständige Referenz für die Integration der Lead-Verwaltung.
