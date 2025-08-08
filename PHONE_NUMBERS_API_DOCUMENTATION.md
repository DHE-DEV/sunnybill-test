# Phone Numbers API Dokumentation

Diese Dokumentation beschreibt die umfassende API für das Verwalten von Telefonnummern in der SunnyBill-Anwendung.

## Übersicht

Die Phone Numbers API ermöglicht es, Telefonnummern für verschiedene Entitäten (Users, Customers, Suppliers, etc.) zu erstellen, zu bearbeiten, zu verwalten und zu löschen. Die API bietet sowohl allgemeine als auch benutzerfreundliche, user-spezifische Endpunkte.

## Authentifizierung

Alle API-Aufrufe erfordern einen App-Token mit entsprechenden Berechtigungen:

```bash
Authorization: Bearer YOUR_APP_TOKEN
```

### Erforderliche Berechtigungen

- `phone-numbers:read` - Telefonnummern anzeigen
- `phone-numbers:create` - Telefonnummern erstellen
- `phone-numbers:update` - Telefonnummern bearbeiten
- `phone-numbers:delete` - Telefonnummern löschen

## API-Varianten

### 🎯 User-spezifische API (Empfohlen für User-Telefonnummern)

**Base URL:** `/api/app/users/{userId}/phone-numbers`

Diese benutzerfreundlichere API ist speziell für User-Telefonnummern optimiert und fügt automatisch die User-ID aus der URL hinzu.

### 🛠️ Allgemeine API (Für alle Entitäten)

**Base URL:** `/api/app/phone-numbers`

Diese flexible API unterstützt alle Entitätstypen (User, Customer, Supplier, etc.).

## Datenmodell

### PhoneNumber Objekt

```json
{
  "id": "string (UUID)",
  "phoneable_id": "string (UUID)",
  "phoneable_type": "string",
  "phone_number": "string",
  "formatted_number": "string",
  "type": "string (business|private|mobile)",
  "type_label": "string",
  "label": "string|null",
  "display_label": "string",
  "is_primary": "boolean",
  "is_favorite": "boolean",
  "sort_order": "integer",
  "digits_only": "string",
  "created_at": "string (ISO 8601)",
  "updated_at": "string (ISO 8601)",
  "owner": "object|null",
  "meta": {
    "is_german_number": "boolean",
    "is_mobile": "boolean",
    "character_count": "integer",
    "digit_count": "integer"
  }
}
```

---

## 🎯 User-spezifische API Endpunkte

Diese Endpunkte sind speziell für User-Telefonnummern optimiert und benutzerfreundlicher.

### 1. User-Telefonnummern abrufen

Ruft alle Telefonnummern eines Users ab.

```
GET /api/app/users/{userId}/phone-numbers
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |

#### Query Parameter

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `type` | string | Filter nach Typ (business, private, mobile) | `type=mobile` |
| `is_primary` | boolean | Filter nach Hauptnummern | `is_primary=1` |
| `is_favorite` | boolean | Filter nach Favoriten | `is_favorite=1` |

#### Beispiel Request

```bash
curl -X GET \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers?type=mobile" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json"
```

#### Beispiel Response

```json
{
  "data": [
    {
      "id": "phone-number-uuid",
      "phoneable_id": "123e4567-e89b-12d3-a456-426614174000",
      "phoneable_type": "App\\Models\\User",
      "phone_number": "+49 175 9876543",
      "formatted_number": "+49 175 987 6543",
      "type": "mobile",
      "type_label": "Mobil",
      "label": "Handy Geschäft",
      "display_label": "Mobil (Handy Geschäft)",
      "is_primary": false,
      "is_favorite": true,
      "sort_order": 2,
      "created_at": "2025-01-08T10:30:00Z",
      "updated_at": "2025-01-08T10:30:00Z"
    }
  ]
}
```

### 2. User-Telefonnummer erstellen

Erstellt eine neue Telefonnummer für einen User. Die User-ID wird automatisch aus der URL übernommen.

```
POST /api/app/users/{userId}/phone-numbers
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |

#### Request Body

```json
{
  "phone_number": "string (required)",
  "type": "string (required, business|private|mobile)",
  "label": "string (optional)",
  "is_primary": "boolean (optional, default: false)",
  "is_favorite": "boolean (optional, default: false)",
  "sort_order": "integer (optional, default: 0)"
}
```

**⚠️ Wichtig:** Bei user-spezifischen Endpunkten sind `phoneable_id` und `phoneable_type` **nicht erforderlich**, da sie automatisch gesetzt werden.

#### Beispiel Request

```bash
curl -X POST \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+49 30 123456789",
    "type": "business",
    "label": "Büro Berlin",
    "is_primary": true
  }'
```

#### Beispiel Response (201 Created)

```json
{
  "data": {
    "id": "new-phone-number-uuid",
    "phoneable_id": "123e4567-e89b-12d3-a456-426614174000",
    "phoneable_type": "App\\Models\\User",
    "phone_number": "+49 30 123456789",
    "formatted_number": "+49 30 123 456 789",
    "type": "business",
    "type_label": "Geschäftlich",
    "label": "Büro Berlin",
    "display_label": "Geschäftlich (Büro Berlin) [Hauptnummer]",
    "is_primary": true,
    "is_favorite": false,
    "sort_order": 0,
    "created_at": "2025-01-08T12:00:00Z",
    "updated_at": "2025-01-08T12:00:00Z"
  }
}
```

### 3. User-Telefonnummer anzeigen

Ruft eine spezifische User-Telefonnummer ab.

```
GET /api/app/users/{userId}/phone-numbers/{id}
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |
| `id` | string (UUID) | Die ID der Telefonnummer |

#### Beispiel Request

```bash
curl -X GET \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers/phone-uuid" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json"
```

### 4. User-Telefonnummer aktualisieren

Aktualisiert eine User-Telefonnummer.

```
PUT /api/app/users/{userId}/phone-numbers/{id}
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |
| `id` | string (UUID) | Die ID der Telefonnummer |

#### Request Body

```json
{
  "phone_number": "string (optional)",
  "type": "string (optional, business|private|mobile)",
  "label": "string (optional, nullable)",
  "is_primary": "boolean (optional)",
  "is_favorite": "boolean (optional)",
  "sort_order": "integer (optional)"
}
```

#### Beispiel Request

```bash
curl -X PUT \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers/phone-uuid" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json" \
  -H "Content-Type: application/json" \
  -d '{
    "phone_number": "+49 30 987654321",
    "label": "Büro München",
    "is_favorite": true
  }'
```

### 5. User-Telefonnummer löschen

Löscht eine User-Telefonnummer permanent.

```
DELETE /api/app/users/{userId}/phone-numbers/{id}
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |
| `id` | string (UUID) | Die ID der Telefonnummer |

#### Beispiel Request

```bash
curl -X DELETE \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers/phone-uuid" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json"
```

### 6. User-Telefonnummer als Hauptnummer setzen

Setzt eine User-Telefonnummer als Hauptnummer.

```
PATCH /api/app/users/{userId}/phone-numbers/{id}/make-primary
```

#### Path Parameter

| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `userId` | string (UUID) | Die ID des Users |
| `id` | string (UUID) | Die ID der Telefonnummer |

#### Beispiel Request

```bash
curl -X PATCH \
  "http://localhost/api/app/users/123e4567-e89b-12d3-a456-426614174000/phone-numbers/phone-uuid/make-primary" \
  -H "Authorization: Bearer sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ" \
  -H "Accept: application/json"
```

---

## 🛠️ Allgemeine API Endpunkte

Diese Endpunkte unterstützen alle Entitätstypen (User, Customer, Supplier, etc.).

### 1. Alle Telefonnummern abrufen

Ruft eine paginierte Liste aller Telefonnummern ab.

```
GET /api/app/phone-numbers
```

#### Query Parameter

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `type` | string | Filter nach Typ (business, private, mobile) | `type=mobile` |
| `phoneable_type` | string | Filter nach Besitzer-Typ | `phoneable_type=App\Models\User` |
| `phoneable_id` | string (UUID) | Filter nach Besitzer-ID | `phoneable_id=123e4567-...` |
| `is_primary` | boolean | Filter nach Hauptnummern | `is_primary=1` |
| `is_favorite` | boolean | Filter nach Favoriten | `is_favorite=1` |
| `search` | string | Suche in Telefonnummer oder Label | `search=030` |
| `per_page` | integer (1-100) | Anzahl Ergebnisse pro Seite | `per_page=25` |
| `page` | integer | Seitennummer | `page=2` |
| `sort` | string | Sortierfeld | `sort=phone_number` |
| `direction` | string | Sortierrichtung (asc, desc) | `direction=asc` |

#### Verfügbare Sortierfelder
- `created_at` (Standard)
- `phone_number`
- `type`
- `is_primary`
- `sort_order`

### 2. Telefonnummer erstellen (Allgemein)

```
POST /api/app/phone-numbers
```

#### Request Body

```json
{
  "phoneable_id": "string (required, UUID)",
  "phoneable_type": "string (required)",
  "phone_number": "string (required)",
  "type": "string (required, business|private|mobile)",
  "label": "string (optional)",
  "is_primary": "boolean (optional, default: false)",
  "is_favorite": "boolean (optional, default: false)",
  "sort_order": "integer (optional, default: 0)"
}
```

### 3. Telefonnummern nach Besitzer

Ruft alle Telefonnummern eines bestimmten Besitzers ab.

```
GET /api/app/owners/{phoneableType}/{phoneableId}/phone-numbers
```

#### Path Parameter

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `phoneableType` | string | Besitzer-Typ (URL-encoded) | `App%5CModels%5CUser` |
| `phoneableId` | string (UUID) | Besitzer-ID | `123e4567-...` |

---

## Telefonnummer-Typen

Die API unterstützt folgende Telefonnummer-Typen:

| Typ | Beschreibung | Label |
|-----|--------------|-------|
| `business` | Geschäftliche Nummer | Geschäftlich |
| `private` | Private Nummer | Privat |
| `mobile` | Mobilfunknummer | Mobil |

## Validierung

### Telefonnummer Format

Telefonnummern müssen folgendes Format erfüllen:
- Mindestens 7 und maximal 15 Ziffern (ohne Sonderzeichen)
- Erlaubte Zeichen: Ziffern (0-9), Leerzeichen, Bindestriche (-), Klammern (()), Schrägstrich (/), und ein optionales Pluszeichen (+) am Anfang
- Regex: `^[\+]?[0-9\s\-\(\)\/]{7,20}$`

### Beispiele für gültige Telefonnummern

```
+49 30 123456789
0711 1234567
+49 (0) 175 9876543
040/55556666
+49-30-12345678
```

## Fehlerbehandlung

### HTTP Status Codes

| Status | Bedeutung |
|--------|-----------|
| `200` | OK - Erfolgreich |
| `201` | Created - Erfolgreich erstellt |
| `400` | Bad Request - Ungültige Anfrage |
| `401` | Unauthorized - Authentifizierung fehlgeschlagen |
| `403` | Forbidden - Keine Berechtigung |
| `404` | Not Found - Ressource nicht gefunden |
| `422` | Unprocessable Entity - Validierungsfehler |
| `500` | Internal Server Error - Serverfehler |

### Validierungsfehler (422)

```json
{
  "message": "Die übermittelten Daten sind ungültig.",
  "errors": {
    "phone_number": [
      "Die Telefonnummer muss mindestens 7 Ziffern enthalten."
    ],
    "type": [
      "Der Telefonnummer-Typ muss einer der folgenden Werte sein: business, private, mobile."
    ]
  }
}
```

## 🎯 Empfohlene Workflows für User-Telefonnummern

### 1. User-Telefonnummern verwalten (Postman-freundlich)

```bash
# 1. Alle Telefonnummern eines Users abrufen
GET /api/app/users/{userId}/phone-numbers

# 2. Geschäftsnummer als Hauptnummer hinzufügen
POST /api/app/users/{userId}/phone-numbers
{
  "phone_number": "+49 30 123456789",
  "type": "business",
  "label": "Büro Berlin",
  "is_primary": true
}

# 3. Mobilnummer hinzufügen
POST /api/app/users/{userId}/phone-numbers
{
  "phone_number": "+49 175 9876543",
  "type": "mobile",
  "label": "Handy",
  "is_favorite": true
}

# 4. Telefonnummer bearbeiten
PUT /api/app/users/{userId}/phone-numbers/{phoneId}
{
  "label": "Neues Label",
  "is_favorite": false
}

# 5. Als Hauptnummer setzen
PATCH /api/app/users/{userId}/phone-numbers/{phoneId}/make-primary

# 6. Telefonnummer löschen
DELETE /api/app/users/{userId}/phone-numbers/{phoneId}
```

## 🚀 Postman Collection

Mit dem bereitgestellten Token kannst du sofort loslegen:

**Bearer Token:**
```
sb_R8nkPTqogXYULmCE48dHfrOiO2HQJG8BWzAer9xEzURn6yJqOx1lwFkkgYgDa1EQ
```

**Base URL:**
```
http://localhost/api/app
```

### Schnelltest für User-Telefonnummern:

1. **User-ID ermitteln** (z.B. `1` für Administrator)

2. **Alle Telefonnummern abrufen:**
   ```
   GET /users/1/phone-numbers
   ```

3. **Neue Telefonnummer hinzufügen:**
   ```
   POST /users/1/phone-numbers
   Body: {
     "phone_number": "+49 30 123456789",
     "type": "business",
     "label": "Test Büro",
     "is_primary": true
   }
   ```

## Testing

Ein umfassendes Test-Script ist verfügbar: `test_phone_numbers_api.php`

Das Script testet:
- ✅ CRUD-Operationen für alle API-Varianten
- ✅ User-spezifische API-Endpunkte
- ✅ Filterung und Suche
- ✅ Hauptnummer-Verwaltung
- ✅ Validierung
- ✅ Fehlerbehandlung

## Changelog

### Version 1.1 (Januar 2025) - Benutzerfreundliche User-API
- ✅ **Neue user-spezifische API-Endpunkte** (`/api/app/users/{userId}/phone-numbers`)
- ✅ **Automatisches Setzen von phoneable_id und phoneable_type** bei user-spezifischen Routen
- ✅ **Vereinfachte Request-Bodies** für User-Telefonnummern
- ✅ **Postman-optimierte API-Struktur**
- ✅ **Erweiterte Dokumentation** mit klaren Beispielen

### Version 1.0 (Januar 2025)
- ✅ Vollständige CRUD-API für Telefonnummern
- ✅ Erweiterte Filter- und Suchfunktionen
- ✅ Automatische deutsche Telefonnummer-Formatierung
- ✅ Hauptnummer-Verwaltung
- ✅ Favoriten-System
- ✅ Umfassende Validierung

## Support

Bei Fragen oder Problemen mit der Phone Numbers API wenden Sie sich an das Entwicklungsteam.

---

**🎯 Die user-spezifische API ist die empfohlene Lösung für die Verwaltung von User-Telefonnummern, da sie benutzerfreundlicher ist und die User-ID automatisch aus der URL übernimmt.**
