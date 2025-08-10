# Kunden-API Dokumentation

## Übersicht
Die Kunden-API ermöglicht die vollständige Verwaltung von Kunden (CRUD) sowie erweiterte Funktionen wie Finanzanalysen, Beteiligungsverwaltung und Projektübersichten.

**Base URL:** `/api/customers`

---

## 🔐 Authentifizierung
Alle Endpoints erfordern ein gültiges App-Token im Header:
```
Authorization: Bearer YOUR_APP_TOKEN
```

### Erforderliche Berechtigungen:
- `customers:read` - Kunden lesen
- `customers:create` - Kunden erstellen
- `customers:update` - Kunden aktualisieren
- `customers:delete` - Kunden löschen
- `customers:status` - Kundenstatus ändern

---

## 📋 1. Kunden auflisten

### `GET /api/customers`

**Berechtigung:** `customers:read`

**Beschreibung:** Listet alle Kunden mit erweiterten Filter- und Suchoptionen auf.

### Query Parameter:

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `status` | string | Filtert nach Kundenstatus | `active`, `inactive`, `prospect`, `blocked` |
| `customer_type` | string | Filtert nach Kundentyp | `private`, `business` |
| `city` | string | Filtert nach Stadt (LIKE-Suche) | `Berlin` |
| `is_active` | boolean | Filtert nach Aktivitätsstatus | `true`, `false` |
| `has_participations` | boolean | Filtert nach Beteiligungen | `true`, `false` |
| `has_solar_plants` | boolean | Filtert nach Solaranlagen | `true`, `false` |
| `created_from` | date | Erstellt ab Datum | `2024-01-01` |
| `created_to` | date | Erstellt bis Datum | `2024-12-31` |
| `search` | string | Sucht in Name, E-Mail, Kundennummer, Telefon | `Max Mustermann` |
| `sort_by` | string | Sortierfeld | `created_at`, `customer_number`, `last_name` |
| `sort_direction` | string | Sortierrichtung | `asc`, `desc` |
| `per_page` | integer | Anzahl pro Seite (max. 100) | `25` |

### Beispiel Request:
```http
GET /api/customers?status=active&customer_type=private&search=Max&per_page=25&sort_by=created_at&sort_direction=desc
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_number": "KD-000001",
      "customer_type": "private",
      "first_name": "Max",
      "last_name": "Mustermann",
      "company_name": null,
      "email": "max.mustermann@example.com",
      "phone": "+49 123 456789",
      "street": "Musterstraße",
      "house_number": "1",
      "postal_code": "12345",
      "city": "Berlin",
      "country": "Deutschland",
      "tax_number": "DE123456789",
      "status": "active",
      "is_active": true,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z",
      "solar_plants": [
        {
          "id": 1,
          "name": "Solaranlage Berlin",
          "capacity_kwp": 25.5,
          "is_active": true
        }
      ],
      "participations": [
        {
          "id": 1,
          "investment_amount": 10000.00,
          "percentage": 15.5
        }
      ]
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

## 👁️ 2. Einzelnen Kunden anzeigen

### `GET /api/customers/{customer}`

**Berechtigung:** `customers:read`

**Beschreibung:** Zeigt detaillierte Informationen eines einzelnen Kunden mit allen verknüpften Daten.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
GET /api/customers/1
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "customer_number": "KD-000001",
    "customer_type": "private",
    "first_name": "Max",
    "last_name": "Mustermann",
    "company_name": null,
    "email": "max.mustermann@example.com",
    "phone": "+49 123 456789",
    "street": "Musterstraße",
    "house_number": "1",
    "postal_code": "12345",
    "city": "Berlin",
    "country": "Deutschland",
    "tax_number": "DE123456789",
    "status": "active",
    "is_active": true,
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "computed_fields": {
      "full_name": "Max Mustermann",
      "display_name": "Max Mustermann",
      "complete_address": "Musterstraße 1, 12345 Berlin, Deutschland"
    },
    "statistics": {
      "total_investment": 10000.00,
      "total_participations": 2,
      "active_plants": 1,
      "total_projects": 3,
      "open_tasks": 5
    },
    "solar_plants": [...],
    "participations": [...],
    "projects": [...],
    "tasks": [...]
  }
}
```

---

## ➕ 3. Neuen Kunden erstellen

### `POST /api/customers`

**Berechtigung:** `customers:create`

**Beschreibung:** Erstellt einen neuen Kunden mit automatischer Kundennummer-Generierung.

### Request Body:
```json
{
  "customer_type": "private",
  "first_name": "Anna",
  "last_name": "Schmidt",
  "email": "anna.schmidt@example.com",
  "phone": "+49 987 654321",
  "street": "Teststraße",
  "house_number": "42",
  "postal_code": "54321",
  "city": "Hamburg",
  "country": "Deutschland",
  "status": "active",
  "is_active": true
}
```

### Validierungsregeln:

#### Für alle Kunden:
| Feld | Regeln |
|------|--------|
| `customer_type` | **Pflicht** - `private` oder `business` |
| `email` | **Pflicht** - Gültige E-Mail, eindeutig |
| `phone` | Optional - Max. 50 Zeichen |
| `street` | Optional - Max. 255 Zeichen |
| `house_number` | Optional - Max. 20 Zeichen |
| `postal_code` | Optional - Max. 10 Zeichen |
| `city` | Optional - Max. 255 Zeichen |
| `country` | Optional - Max. 255 Zeichen |
| `tax_number` | Optional - Max. 50 Zeichen |
| `customer_number` | Optional - Max. 50 Zeichen, eindeutig (wird automatisch generiert) |
| `status` | **Pflicht** - `active`, `inactive`, `prospect`, `blocked` |
| `is_active` | Optional - Boolean (Standard: `true`) |

#### Zusätzlich für Privatkunden (`customer_type: private`):
| Feld | Regeln |
|------|--------|
| `first_name` | **Pflicht** - Max. 255 Zeichen |
| `last_name` | **Pflicht** - Max. 255 Zeichen |

#### Zusätzlich für Geschäftskunden (`customer_type: business`):
| Feld | Regeln |
|------|--------|
| `company_name` | **Pflicht** - Max. 255 Zeichen |

### Beispiel Request:
```http
POST /api/customers
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "customer_type": "private",
  "first_name": "Anna",
  "last_name": "Schmidt",
  "email": "anna.schmidt@example.com",
  "phone": "+49 987 654321",
  "street": "Teststraße",
  "house_number": "42",
  "postal_code": "54321",
  "city": "Hamburg",
  "country": "Deutschland",
  "status": "active"
}
```

### Response (201 Created):
```json
{
  "success": true,
  "message": "Kunde erfolgreich erstellt",
  "data": {
    "id": 123,
    "customer_number": "KD-000123",
    "customer_type": "private",
    "first_name": "Anna",
    "last_name": "Schmidt",
    "email": "anna.schmidt@example.com",
    "...": "..."
  }
}
```

### Fehler Response (422 Unprocessable Entity):
```json
{
  "success": false,
  "message": "Validierungsfehler",
  "errors": {
    "email": ["Die E-Mail-Adresse ist bereits vergeben."],
    "first_name": ["Das Vorname-Feld ist erforderlich."]
  }
}
```

---

## ✏️ 4. Kunden aktualisieren

### `PUT /api/customers/{customer}`

**Berechtigung:** `customers:update`

**Beschreibung:** Aktualisiert einen bestehenden Kunden. Partielle Updates sind möglich.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Request Body:
```json
{
  "phone": "+49 987 654321 (neu)",
  "city": "München",
  "status": "inactive"
}
```

**Hinweis:** Alle Felder sind optional. Nur übermittelte Felder werden aktualisiert.

### Beispiel Request:
```http
PUT /api/customers/123
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "phone": "+49 987 654321 (neu)",
  "city": "München",
  "status": "inactive"
}
```

### Response:
```json
{
  "success": true,
  "message": "Kunde erfolgreich aktualisiert",
  "data": {
    "id": 123,
    "customer_number": "KD-000123",
    "phone": "+49 987 654321 (neu)",
    "city": "München",
    "status": "inactive",
    "...": "..."
  }
}
```

---

## 🗑️ 5. Kunden löschen

### `DELETE /api/customers/{customer}`

**Berechtigung:** `customers:delete`

**Beschreibung:** Löscht einen Kunden, sofern keine verknüpften Daten vorhanden sind.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
DELETE /api/customers/123
Authorization: Bearer your_app_token_here
```

### Response (200 OK):
```json
{
  "success": true,
  "message": "Kunde erfolgreich gelöscht"
}
```

### Fehler Response (400 Bad Request):
```json
{
  "success": false,
  "message": "Kunde kann nicht gelöscht werden, da noch Solaranlagen verknüpft sind"
}
```

**Schutz vor Löschen bei:**
- Verknüpften Solaranlagen
- Vorhandenen Beteiligungen
- Verknüpften Projekten

---

## 📊 6. Kundenstatus ändern

### `PATCH /api/customers/{customer}/status`

**Berechtigung:** `customers:status`

**Beschreibung:** Ändert schnell nur den Status eines Kunden.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Request Body:
```json
{
  "status": "blocked"
}
```

### Gültige Stati:
- `active` - Aktiv
- `inactive` - Inaktiv
- `prospect` - Interessent
- `blocked` - Gesperrt

### Beispiel Request:
```http
PATCH /api/customers/123/status
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "status": "blocked"
}
```

### Response:
```json
{
  "success": true,
  "message": "Kundenstatus erfolgreich geändert",
  "data": {
    "id": 123,
    "customer_number": "KD-000123",
    "status": "blocked",
    "...": "..."
  }
}
```

---

## 💰 7. Beteiligungen eines Kunden

### `GET /api/customers/{customer}/participations`

**Berechtigung:** `customers:read`

**Beschreibung:** Zeigt alle Beteiligungen eines Kunden mit Solaranlagen-Daten.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
GET /api/customers/123/participations
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 123,
      "solar_plant_id": 5,
      "investment_amount": 15000.00,
      "percentage": 12.5,
      "investment_date": "2024-01-15",
      "created_at": "2024-01-15T10:30:00.000000Z",
      "solar_plant": {
        "id": 5,
        "name": "Solarpark Hamburg Nord",
        "capacity_kwp": 500.0,
        "location": "Hamburg",
        "is_active": true,
        "billings": [
          {
            "id": 45,
            "billing_month": "2024-01",
            "total_income": 12500.00,
            "total_costs": 3200.00,
            "net_result": 9300.00
          }
        ]
      }
    }
  ],
  "summary": {
    "total_participations": 3,
    "total_investment": 42500.00,
    "total_percentage": 31.8
  }
}
```

---

## 🏗️ 8. Projekte eines Kunden

### `GET /api/customers/{customer}/projects`

**Berechtigung:** `customers:read`

**Beschreibung:** Zeigt alle Projekte eines Kunden mit Meilensteinen und Aufgaben.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
GET /api/customers/123/projects
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 123,
      "name": "Solaranlage Dach Hamburg",
      "description": "Installation einer 25 kWp Solaranlage",
      "status": "active",
      "budget": 35000.00,
      "start_date": "2024-02-01",
      "end_date": "2024-03-15",
      "milestones": [
        {
          "id": 1,
          "name": "Planung abgeschlossen",
          "status": "completed",
          "due_date": "2024-02-05"
        }
      ],
      "appointments": [...],
      "tasks": [...]
    }
  ],
  "summary": {
    "total_projects": 2,
    "active_projects": 1,
    "completed_projects": 1,
    "total_budget": 85000.00
  }
}
```

---

## ✅ 9. Aufgaben eines Kunden

### `GET /api/customers/{customer}/tasks`

**Berechtigung:** `customers:read`

**Beschreibung:** Zeigt alle Aufgaben eines Kunden mit zugewiesenen Benutzern.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
GET /api/customers/123/tasks
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "customer_id": 123,
      "project_id": 1,
      "title": "Vor-Ort Besichtigung durchführen",
      "description": "Dach begutachten und Machbarkeit prüfen",
      "status": "in_progress",
      "priority": "high",
      "due_date": "2024-02-10",
      "assigned_user": {
        "id": 5,
        "name": "Peter Techniker",
        "email": "peter@example.com"
      },
      "project": {
        "id": 1,
        "name": "Solaranlage Dach Hamburg"
      }
    }
  ],
  "summary": {
    "total_tasks": 8,
    "open_tasks": 3,
    "completed_tasks": 5,
    "high_priority_tasks": 1
  }
}
```

---

## 💹 10. Finanzielle Übersicht

### `GET /api/customers/{customer}/financials`

**Berechtigung:** `customers:read`

**Beschreibung:** Detaillierte Finanzanalyse mit ROI-Berechnung und Performance-Daten.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `customer` | integer | ID des Kunden |

### Beispiel Request:
```http
GET /api/customers/123/financials
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "customer": {
      "id": 123,
      "customer_number": "KD-000123",
      "display_name": "Max Mustermann"
    },
    "investment_summary": {
      "total_investment": 42500.00,
      "total_participations": 3,
      "active_plants": 2
    },
    "performance_12m": {
      "period": {
        "start": "2023-08-01",
        "end": "2024-08-01"
      },
      "total_income": 18750.00,
      "total_costs": 4200.00,
      "net_result": 14550.00
    },
    "roi_analysis": {
      "annual_return": 14550.00,
      "total_investment": 42500.00,
      "roi_percentage": 34.24
    }
  }
}
```

---

## ⚙️ 11. Kunden-Optionen

### `GET /api/options/customers`

**Berechtigung:** `customers:read`

**Beschreibung:** Liefert verfügbare Optionen für Kunden-Formulare.

### Beispiel Request:
```http
GET /api/options/customers
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "customer_types": {
      "private": "Privatkunde",
      "business": "Geschäftskunde"
    },
    "statuses": {
      "active": "Aktiv",
      "inactive": "Inaktiv",
      "prospect": "Interessent",
      "blocked": "Gesperrt"
    },
    "countries": {
      "Deutschland": "Deutschland",
      "Österreich": "Österreich",
      "Schweiz": "Schweiz"
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
- `404` - Not Found (Kunde nicht gefunden)
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

## 🔒 Ressourcen-Beschränkungen

**App-Tokens können auf bestimmte Kunden beschränkt werden:**

1. **Token-Konfiguration:**
   ```json
   {
     "restrict_customers": true,
     "allowed_customers": [1, 5, 10, 23]
   }
   ```

2. **Auswirkung:**
   - `GET /api/customers` zeigt nur erlaubte Kunden
   - Andere Endpoints nur für erlaubte Kunden-IDs zugänglich
   - Automatische Filterung in allen Responses

---

## 📚 Zusätzliche Informationen

### Berechnete Felder:
- `full_name` - Kombiniert Vor- und Nachname
- `display_name` - Anzeigename (Name oder Firma)
- `complete_address` - Vollständige Adresse

### Automatische Kundennummer:
- Format: `KD-XXXXXX` (6-stellig, mit Nullen aufgefüllt)
- Basiert auf der nächsten verfügbaren ID
- Beispiel: `KD-000001`, `KD-000123`

### Verknüpfte Daten:
- **Solaranlagen** - Kunden können Solaranlagen besitzen
- **Beteiligungen** - Prozentuale Anteile an Solaranlagen
- **Projekte** - Installationsprojekte mit Meilensteinen
- **Aufgaben** - Zugewiesene To-Dos und Termine
- **Telefonnummern** - Separate Telefonnummer-Verwaltung

Diese API-Dokumentation deckt alle verfügbaren Kunden-Operationen ab und bietet eine vollständige Referenz für die Integration.
