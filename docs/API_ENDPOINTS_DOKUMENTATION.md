# API-Endpoints Dokumentation

Diese Dokumentation beschreibt alle verfügbaren API-Endpoints der SunnyBill-Anwendung.

## Authentifizierung

Alle API-Endpoints verwenden App-Token-Authentifizierung:
```
Authorization: Bearer {app_token}
```

## Base URL
```
{domain}/api/app
```

---

## 🔹 Solaranlagen API (`/solar-plants`)

### Grundlegende CRUD-Operationen

- **GET** `/solar-plants` - Liste aller Solaranlagen
  - **Berechtigung:** `solar-plants:read`
  - **Parameter:** `status`, `is_active`, `location`, `min_capacity`, `max_capacity`, `commissioning_from`, `commissioning_to`, `search`, `sort_by`, `sort_direction`, `per_page`

- **POST** `/solar-plants` - Neue Solaranlage erstellen
  - **Berechtigung:** `solar-plants:create`

- **GET** `/solar-plants/{id}` - Details einer Solaranlage
  - **Berechtigung:** `solar-plants:read`

- **PUT** `/solar-plants/{id}` - Solaranlage aktualisieren
  - **Berechtigung:** `solar-plants:update`

- **DELETE** `/solar-plants/{id}` - Solaranlage löschen
  - **Berechtigung:** `solar-plants:delete`

### Zusätzliche Endpoints

- **GET** `/solar-plants/{id}/components` - Komponenten (Wechselrichter, Module, Batterien)
- **GET** `/solar-plants/{id}/participations` - Kundenbeteiligungen
- **GET** `/solar-plants/{id}/monthly-results` - Monatliche Ergebnisse
- **GET** `/solar-plants/{id}/statistics` - Detaillierte Statistiken

---

## 🔹 Projekte API (`/projects`)

### Grundlegende CRUD-Operationen

- **GET** `/projects` - Liste aller Projekte
  - **Berechtigung:** `projects:read`
  - **Parameter:** `status`, `type`, `priority`, `project_manager_id`, `customer_id`, `supplier_id`, `solar_plant_id`, `is_active`, `start_date_from`, `start_date_to`, `planned_end_date_from`, `planned_end_date_to`, `min_budget`, `max_budget`, `min_progress`, `max_progress`, `overdue_only`, `search`

- **POST** `/projects` - Neues Projekt erstellen
  - **Berechtigung:** `projects:create`

- **GET** `/projects/{id}` - Projektdetails
  - **Berechtigung:** `projects:read`

- **PUT** `/projects/{id}` - Projekt aktualisieren
  - **Berechtigung:** `projects:update`

- **DELETE** `/projects/{id}` - Projekt löschen
  - **Berechtigung:** `projects:delete`

### Spezielle Aktionen

- **PATCH** `/projects/{id}/status` - Projektstatus ändern
  - **Berechtigung:** `projects:status`

- **GET** `/projects/{id}/progress` - Projektfortschritt mit Meilensteinen
- **PATCH** `/projects/{id}/progress` - Fortschritt aktualisieren

### Projekt-Meilensteine

- **GET** `/projects/{id}/milestones` - Meilensteine eines Projekts
- **POST** `/projects/{id}/milestones` - Neuen Meilenstein erstellen

### Projekt-Termine

- **GET** `/projects/{id}/appointments` - Termine eines Projekts
- **POST** `/projects/{id}/appointments` - Neuen Termin erstellen

---

## 🔹 Projektmeilensteine API (`/project-milestones`)

### Grundlegende CRUD-Operationen

- **GET** `/project-milestones` - Alle Meilensteine (projektübergreifend)
  - **Berechtigung:** `milestones:read`
  - **Parameter:** `project_id`, `status`, `type`, `responsible_user_id`, `is_critical_path`, `planned_date_from`, `planned_date_to`, `overdue_only`, `due_today`, `due_this_week`, `search`

- **GET** `/project-milestones/{id}` - Meilenstein-Details
  - **Berechtigung:** `milestones:read`

- **PUT** `/project-milestones/{id}` - Meilenstein aktualisieren
  - **Berechtigung:** `milestones:update`

- **DELETE** `/project-milestones/{id}` - Meilenstein löschen
  - **Berechtigung:** `milestones:delete`

### Spezielle Aktionen

- **PATCH** `/project-milestones/{id}/status` - Status ändern
  - **Berechtigung:** `milestones:status`

- **PATCH** `/project-milestones/{id}/progress` - Fortschritt aktualisieren
  - **Berechtigung:** `milestones:update`

---

## 🔹 Projekttermine API (`/project-appointments`)

### Grundlegende CRUD-Operationen

- **GET** `/project-appointments` - Alle Termine (projektübergreifend)
  - **Berechtigung:** `appointments:read`
  - **Parameter:** `project_id`, `status`, `type`, `location`, `start_date_from`, `start_date_to`, `upcoming_only`, `today_only`, `this_week`, `overdue_only`, `search`

- **GET** `/project-appointments/{id}` - Termin-Details
  - **Berechtigung:** `appointments:read`

- **PUT** `/project-appointments/{id}` - Termin aktualisieren
  - **Berechtigung:** `appointments:update`

- **DELETE** `/project-appointments/{id}` - Termin löschen
  - **Berechtigung:** `appointments:delete`

### Spezielle Endpoints

- **GET** `/project-appointments/upcoming` - Anstehende Termine (alle Projekte)
  - **Parameter:** `limit` (Standard: 10, Max: 50)

- **GET** `/project-appointments/calendar` - Kalenderansicht
  - **Parameter:** `start_date` (erforderlich), `end_date` (erforderlich), `project_id` (optional)

### Spezielle Aktionen

- **PATCH** `/project-appointments/{id}/status` - Terminstatus ändern
  - **Berechtigung:** `appointments:status`

---

## 🔹 Kosten API (`/costs`)

### Übersichten und Berichte

- **GET** `/costs/overview` - Kostenübersicht (alle Bereiche)
  - **Berechtigung:** `costs:read`
  - Enthält: Projektkosten, Solaranlagen-Investitionen, Abrechnungs-Performance, monatliche Trends

- **GET** `/costs/reports` - Kostenberichte mit Zeitraum-Filter
  - **Berechtigung:** `costs:reports`
  - **Parameter:** `start_date`, `end_date`, `report_type` (`monthly`, `quarterly`, `yearly`)

### Projektspezifische Kosten

- **GET** `/projects/{id}/costs` - Projektkosten-Details
  - **Berechtigung:** `costs:read`

- **POST** `/projects/{id}/costs` - Kosten zu Projekt hinzufügen
  - **Berechtigung:** `costs:create`
  - **Body:** `amount`, `description`, `category`, `date`

### Solaranlagen-Kosten

- **GET** `/solar-plants/{id}/costs` - Solaranlagen-Kosten
  - **Berechtigung:** `costs:read`

- **GET** `/solar-plants/{id}/billings` - Abrechnungen einer Solaranlage
  - **Berechtigung:** `costs:read`
  - **Parameter:** `year`, `start_month`, `end_month`

---

## 🔹 Kunden API (`/customers`)

### Grundlegende CRUD-Operationen

- **GET** `/customers` - Liste aller Kunden
  - **Berechtigung:** `customers:read`
  - **Parameter:** `status`, `customer_type`, `city`, `is_active`, `has_participations`, `has_solar_plants`, `created_from`, `created_to`, `search`, `sort_by`, `sort_direction`, `per_page`

- **POST** `/customers` - Neuen Kunden erstellen
  - **Berechtigung:** `customers:create`

- **GET** `/customers/{id}` - Details eines Kunden
  - **Berechtigung:** `customers:read`

- **PUT** `/customers/{id}` - Kunden aktualisieren
  - **Berechtigung:** `customers:update`

- **DELETE** `/customers/{id}` - Kunden löschen
  - **Berechtigung:** `customers:delete`

### Spezielle Aktionen

- **PATCH** `/customers/{id}/status` - Kundenstatus ändern
  - **Berechtigung:** `customers:status`

### Zusätzliche Endpoints

- **GET** `/customers/{id}/participations` - Beteiligungen eines Kunden
- **GET** `/customers/{id}/projects` - Projekte eines Kunden
- **GET** `/customers/{id}/tasks` - Aufgaben eines Kunden
- **GET** `/customers/{id}/financials` - Finanzielle Übersicht

---

## 🔹 Lieferanten API (`/suppliers`)

### Grundlegende CRUD-Operationen

- **GET** `/suppliers` - Liste aller Lieferanten
  - **Berechtigung:** `suppliers:read`
  - **Parameter:** `status`, `supplier_type`, `city`, `is_active`, `has_contracts`, `has_projects`, `has_active_contracts`, `created_from`, `created_to`, `search`, `sort_by`, `sort_direction`, `per_page`

- **POST** `/suppliers` - Neuen Lieferanten erstellen
  - **Berechtigung:** `suppliers:create`

- **GET** `/suppliers/{id}` - Details eines Lieferanten
  - **Berechtigung:** `suppliers:read`

- **PUT** `/suppliers/{id}` - Lieferanten aktualisieren
  - **Berechtigung:** `suppliers:update`

- **DELETE** `/suppliers/{id}` - Lieferanten löschen
  - **Berechtigung:** `suppliers:delete`

### Spezielle Aktionen

- **PATCH** `/suppliers/{id}/status` - Lieferantenstatus ändern
  - **Berechtigung:** `suppliers:status`

### Zusätzliche Endpoints

- **GET** `/suppliers/{id}/contracts` - Verträge eines Lieferanten
- **GET** `/suppliers/{id}/projects` - Projekte eines Lieferanten
- **GET** `/suppliers/{id}/tasks` - Aufgaben eines Lieferanten
- **GET** `/suppliers/{id}/financials` - Finanzielle Übersicht
- **GET** `/suppliers/{id}/performance` - Performance-Analyse

---

## 🔹 Aufgaben API (`/tasks`)

### Grundlegende CRUD-Operationen

- **GET** `/tasks` - Liste aller Aufgaben
  - **Berechtigung:** `tasks:read`

- **POST** `/tasks` - Neue Aufgabe erstellen
  - **Berechtigung:** `tasks:create`

- **GET** `/tasks/{id}` - Aufgabendetails
  - **Berechtigung:** `tasks:read`

- **PUT** `/tasks/{id}` - Aufgabe aktualisieren
  - **Berechtigung:** `tasks:update`

- **DELETE** `/tasks/{id}` - Aufgabe löschen
  - **Berechtigung:** `tasks:delete`

### Spezielle Aktionen

- **PATCH** `/tasks/{id}/status` - Status ändern
  - **Berechtigung:** `tasks:status`

- **PATCH** `/tasks/{id}/assign` - Aufgabe zuweisen
  - **Berechtigung:** `tasks:assign`

- **PATCH** `/tasks/{id}/time` - Zeiterfassung aktualisieren
  - **Berechtigung:** `tasks:time`

- **GET** `/tasks/{id}/subtasks` - Unteraufgaben
  - **Berechtigung:** `tasks:read`

---

## 🔹 Dropdown-Daten und Optionen

### Allgemeine Daten

- **GET** `/users` - Liste aller Benutzer
  - **Berechtigung:** `tasks:read`

- **GET** `/customers` - Liste aller Kunden
  - **Berechtigung:** `tasks:read`

- **GET** `/suppliers` - Liste aller Lieferanten
  - **Berechtigung:** `tasks:read`

- **GET** `/solar-plants-dropdown` - Solaranlagen für Dropdowns
  - **Berechtigung:** `tasks:read`

### API-Optionen

- **GET** `/options/tasks` - Verfügbare Optionen für Aufgaben
  - **Berechtigung:** `tasks:read`
  - Enthält: Status-Optionen, Prioritäten, Kategorien

- **GET** `/options/projects` - Verfügbare Optionen für Projekte
  - **Berechtigung:** `projects:read`
  - Enthält: Status-Optionen, Prioritäten, Projekt-Typen

- **GET** `/options/milestones` - Verfügbare Optionen für Meilensteine
  - **Berechtigung:** `milestones:read`
  - Enthält: Status-Optionen, Meilenstein-Typen

- **GET** `/options/appointments` - Verfügbare Optionen für Termine
  - **Berechtigung:** `appointments:read`
  - Enthält: Status-Optionen, Termin-Typen, Erinnerungs-Optionen

- **GET** `/options/costs` - Verfügbare Optionen für Kosten
  - **Berechtigung:** `costs:read`
  - Enthält: Kosten-Kategorien, Report-Typen, Währungen

---

## 🔹 Profil und Authentifizierung

- **GET** `/profile` - Benutzer-Profil-Informationen
- **POST** `/logout` - Benutzer abmelden

---

## 📊 Response-Format

Alle API-Endpoints verwenden ein einheitliches Response-Format:

### Erfolgreiche Antwort
```json
{
  "success": true,
  "data": {
    // Antwortdaten
  },
  "message": "Optional: Erfolgsmeldung"
}
```

### Fehlerhafte Antwort
```json
{
  "success": false,
  "message": "Fehlermeldung",
  "errors": {
    // Validierungsfehler (optional)
  }
}
```

### Paginierte Antwort
```json
{
  "success": true,
  "data": [
    // Array von Objekten
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 73
  }
}
```

---

## 🔒 Berechtigungssystem

Das API-System verwendet granulare Berechtigungen:

### Solaranlagen-Berechtigungen
- `solar-plants:read` - Solaranlagen lesen
- `solar-plants:create` - Solaranlagen erstellen
- `solar-plants:update` - Solaranlagen bearbeiten
- `solar-plants:delete` - Solaranlagen löschen

### Projekt-Berechtigungen
- `projects:read` - Projekte lesen
- `projects:create` - Projekte erstellen
- `projects:update` - Projekte bearbeiten
- `projects:delete` - Projekte löschen
- `projects:status` - Projektstatus ändern

### Meilenstein-Berechtigungen
- `milestones:read` - Meilensteine lesen
- `milestones:create` - Meilensteine erstellen
- `milestones:update` - Meilensteine bearbeiten
- `milestones:delete` - Meilensteine löschen
- `milestones:status` - Meilenstein-Status ändern

### Termin-Berechtigungen
- `appointments:read` - Termine lesen
- `appointments:create` - Termine erstellen
- `appointments:update` - Termine bearbeiten
- `appointments:delete` - Termine löschen
- `appointments:status` - Termin-Status ändern

### Kosten-Berechtigungen
- `costs:read` - Kosten lesen
- `costs:create` - Kosten erstellen
- `costs:update` - Kosten bearbeiten
- `costs:delete` - Kosten löschen
- `costs:reports` - Kostenberichte abrufen

### Kunden-Berechtigungen
- `customers:read` - Kunden lesen
- `customers:create` - Kunden erstellen
- `customers:update` - Kunden bearbeiten
- `customers:delete` - Kunden löschen
- `customers:status` - Kundenstatus ändern

### Lieferanten-Berechtigungen
- `suppliers:read` - Lieferanten lesen
- `suppliers:create` - Lieferanten erstellen
- `suppliers:update` - Lieferanten bearbeiten
- `suppliers:delete` - Lieferanten löschen
- `suppliers:status` - Lieferantenstatus ändern

### Aufgaben-Berechtigungen
- `tasks:read` - Aufgaben lesen
- `tasks:create` - Aufgaben erstellen
- `tasks:update` - Aufgaben bearbeiten
- `tasks:delete` - Aufgaben löschen
- `tasks:status` - Aufgaben-Status ändern
- `tasks:assign` - Aufgaben zuweisen
- `tasks:time` - Zeiterfassung verwalten

---

## 🚀 Beispiel-Requests

### Solaranlage erstellen
```bash
curl -X POST "https://domain.com/api/app/solar-plants" \
  -H "Authorization: Bearer {app_token}" \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Solarpark Musterstadt",
    "location": "Musterstadt, Deutschland",
    "total_capacity_kw": 100.5,
    "commissioning_date": "2024-06-15",
    "is_active": true
  }'
```

### Projekt-Fortschritt abrufen
```bash
curl -X GET "https://domain.com/api/app/projects/123/progress" \
  -H "Authorization: Bearer {app_token}"
```

### Kalenderansicht für Termine
```bash
curl -X GET "https://domain.com/api/app/project-appointments/calendar?start_date=2024-01-01&end_date=2024-01-31" \
  -H "Authorization: Bearer {app_token}"
```

### Kostenübersicht abrufen
```bash
curl -X GET "https://domain.com/api/app/costs/overview" \
  -H "Authorization: Bearer {app_token}"
```

---

## 📈 Status Codes

- `200` - Erfolgreich
- `201` - Erstellt
- `400` - Ungültige Anfrage
- `401` - Nicht authentifiziert
- `403` - Keine Berechtigung
- `404` - Nicht gefunden
- `422` - Validierungsfehler
- `500` - Serverfehler

---

## 🔧 Parameter-Arten

### Query Parameter
- Verwendet für Filter, Sortierung, Paginierung
- Beispiel: `?status=active&sort_by=name&per_page=20`

### Path Parameter
- Verwendet für Ressourcen-IDs
- Beispiel: `/projects/{project_id}/milestones`

### Body Parameter
- Verwendet für POST/PUT Requests
- Content-Type: `application/json`

---

Diese API-Dokumentation wird regelmäßig aktualisiert. Bei Fragen wenden Sie sich an das Entwicklungsteam.
