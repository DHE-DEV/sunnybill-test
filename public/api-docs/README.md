# SunnyBill API Dokumentation

## Übersicht

Diese Dokumentation beschreibt die REST API des SunnyBill-Systems für die Verwaltung von Aufgaben, Kunden, Lieferanten und Solaranlagen.

## Zugriff

- **Lokale Entwicklung**: http://localhost:8000/api-docs/
- **Produktion**: https://voltmaster.cloud/api-docs/

## Dateien

- `index.html` - Interaktive Swagger UI Dokumentation
- `openapi.yaml` - OpenAPI 3.0.3 Spezifikation
- `README.md` - Diese Datei

## Authentifizierung

### App-Token (Empfohlen)

1. **Token generieren**:
   - Melden Sie sich in der Admin-Oberfläche an
   - Gehen Sie zu "App-Token" → "Neues Token erstellen"
   - Wählen Sie die erforderlichen Berechtigungen
   - Kopieren Sie das generierte Token

2. **Token verwenden**:
   ```bash
   curl -X GET "https://voltmaster.cloud/api/app/tasks" \
     -H "Authorization: Bearer YOUR_APP_TOKEN" \
     -H "Content-Type: application/json"
   ```

### Laravel Sanctum

Für Web-basierte Anwendungen verwenden Sie Sanctum-Token:

```bash
curl -X GET "https://voltmaster.cloud/api/user" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

## Berechtigungen

App-Token haben granulare Berechtigungen:

| Berechtigung | Beschreibung |
|-------------|-------------|
| `tasks:read` | Aufgaben anzeigen und durchsuchen |
| `tasks:create` | Neue Aufgaben erstellen |
| `tasks:update` | Aufgaben bearbeiten |
| `tasks:delete` | Aufgaben löschen |
| `tasks:status` | Aufgaben-Status ändern |
| `tasks:assign` | Aufgaben zuweisen |
| `tasks:time` | Zeiterfassung bearbeiten |

## API-Endpunkte

### Aufgaben

- `GET /api/app/tasks` - Aufgaben auflisten (paginiert)
- `POST /api/app/tasks` - Neue Aufgabe erstellen
- `GET /api/app/tasks/{id}` - Spezifische Aufgabe anzeigen
- `PUT /api/app/tasks/{id}` - Aufgabe aktualisieren
- `DELETE /api/app/tasks/{id}` - Aufgabe löschen
- `PATCH /api/app/tasks/{id}/status` - Aufgaben-Status ändern
- `PATCH /api/app/tasks/{id}/assign` - Aufgabe zuweisen
- `PATCH /api/app/tasks/{id}/time` - Zeiterfassung aktualisieren
- `GET /api/app/tasks/{id}/subtasks` - Unteraufgaben anzeigen

### Dropdown-Daten

- `GET /api/app/users` - Benutzer-Liste
- `GET /api/app/customers` - Kunden-Liste
- `GET /api/app/suppliers` - Lieferanten-Liste
- `GET /api/app/solar-plants` - Solaranlagen-Liste
- `GET /api/app/options` - Optionen (Status, Prioritäten, etc.)

### Benutzer

- `GET /api/user` - Aktueller Benutzer (Sanctum)
- `GET /api/users/search` - Benutzer suchen
- `GET /api/users/all` - Alle Benutzer

### App-Token

- `GET /api/app/profile` - Token-Profil anzeigen

## Beispiel-Anfragen

### Aufgaben auflisten

```bash
curl -X GET "https://voltmaster.cloud/api/app/tasks?page=1&per_page=10&status=open" \
  -H "Authorization: Bearer YOUR_APP_TOKEN" \
  -H "Accept: application/json"
```

### Neue Aufgabe erstellen

```bash
curl -X POST "https://voltmaster.cloud/api/app/tasks" \
  -H "Authorization: Bearer YOUR_APP_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "title": "Beispiel-Aufgabe",
    "description": "Eine Beschreibung der Aufgabe",
    "priority": "medium",
    "status": "open",
    "due_date": "2025-01-31"
  }'
```

### Aufgaben-Status ändern

```bash
curl -X PATCH "https://voltmaster.cloud/api/app/tasks/123/status" \
  -H "Authorization: Bearer YOUR_APP_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "status": "completed"
  }'
```

## Fehlerbehandlung

Die API gibt strukturierte Fehlermeldungen zurück:

```json
{
  "success": false,
  "message": "Validierungsfehler",
  "errors": {
    "title": ["Das Titel-Feld ist erforderlich."],
    "priority": ["Die Priorität ist ungültig."]
  }
}
```

### HTTP-Statuscodes

- `200` - OK
- `201` - Erstellt
- `204` - Kein Inhalt
- `400` - Ungültige Anfrage
- `401` - Nicht authentifiziert
- `403` - Keine Berechtigung
- `404` - Nicht gefunden
- `422` - Validierungsfehler
- `500` - Serverfehler

## Paginierung

Listen-Endpunkte verwenden Laravel-Paginierung:

```json
{
  "success": true,
  "data": [...],
  "meta": {
    "current_page": 1,
    "from": 1,
    "last_page": 5,
    "per_page": 15,
    "to": 15,
    "total": 73
  },
  "links": {
    "first": "https://voltmaster.cloud/api/app/tasks?page=1",
    "last": "https://voltmaster.cloud/api/app/tasks?page=5",
    "prev": null,
    "next": "https://voltmaster.cloud/api/app/tasks?page=2"
  }
}
```

## Filterung und Sortierung

### Aufgaben filtern

```bash
# Nach Status filtern
GET /api/app/tasks?status=open

# Nach Priorität filtern
GET /api/app/tasks?priority=high

# Nach zugewiesenem Benutzer filtern
GET /api/app/tasks?assigned_to=123

# Überfällige Aufgaben
GET /api/app/tasks?overdue=true

# Suche in Titel und Beschreibung
GET /api/app/tasks?search=Solar

# Kombinierte Filter
GET /api/app/tasks?status=open&priority=high&assigned_to=123
```

### Sortierung

```bash
# Nach Erstellungsdatum sortieren (Standard)
GET /api/app/tasks?sort=created_at&order=desc

# Nach Fälligkeitsdatum sortieren
GET /api/app/tasks?sort=due_date&order=asc

# Nach Priorität sortieren
GET /api/app/tasks?sort=priority&order=desc
```

## Entwicklung

### Lokale Entwicklung

1. Starten Sie den Laravel-Server:
   ```bash
   php artisan serve
   ```

2. Öffnen Sie die API-Dokumentation:
   ```
   http://localhost:8000/api-docs/
   ```

### API testen

Verwenden Sie die interaktive Swagger UI:

1. Öffnen Sie die Dokumentation im Browser
2. Klicken Sie auf "Authorize" und geben Sie Ihr Token ein
3. Verwenden Sie "Try it out" für beliebige Endpunkte

## Support

Bei Fragen oder Problemen wenden Sie sich an:

- **E-Mail**: support@voltmaster.cloud
- **GitHub**: https://github.com/DHE-DEV/sunnybill-test

## Changelog

### v1.0.0 (2025-07-17)

- Initiale API-Dokumentation
- Vollständige OpenAPI 3.0.3 Spezifikation
- Interaktive Swagger UI
- App-Token Authentifizierung
- Aufgaben-Management API
- Dropdown-Daten API
- Benutzer-Suche API
- Umfassende Fehlerbehandlung
- Deutsche Lokalisierung
