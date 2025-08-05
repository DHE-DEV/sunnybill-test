# 🚀 VoltMaster API Dokumentation

## Übersicht
Die VoltMaster API ist eine RESTful API für das Solar Management System, die umfassende Funktionen für Aufgaben-, Kunden-, Solaranlagen- und Projektmanagement bietet.

**Base URL**: `https://prosoltec.voltmaster.cloud`  
**API Version**: 1.0.0  
**Authentifizierung**: App Token (Bearer Token)

## 🔐 Authentifizierung

Die API verwendet App Token für die Authentifizierung. Jeder Request muss einen gültigen Bearer Token im Authorization Header enthalten.

```http
Authorization: Bearer YOUR_APP_TOKEN
```

### App Token erhalten
App Tokens werden über das VoltMaster Admin-Panel verwaltet und haben spezifische Berechtigungen für verschiedene Bereiche.

## 📋 Verfügbare Endpoints

### 🔑 Authentifizierung

#### Benutzer-Profil abrufen
```http
GET /api/app/profile
```
**Berechtigung**: Basis-Token  
**Beschreibung**: Gibt die Profil-Informationen des authentifizierten Benutzers zurück

**Response**:
```json
{
  "data": {
    "id": 1,
    "name": "John Doe",
    "email": "john@example.com"
  }
}
```

#### Logout
```http
POST /api/app/logout
```
**Berechtigung**: Basis-Token  
**Beschreibung**: Invalidiert den aktuellen App Token

---

### 📝 Aufgaben-Management

#### Aufgaben abrufen
```http
GET /api/app/tasks
```
**Berechtigung**: `tasks:read`  
**Parameter**:
- `page` (optional): Seitennummer (Standard: 1)
- `per_page` (optional): Einträge pro Seite (Standard: 15)

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "title": "Solar Panel Installation",
      "description": "Install solar panels on customer roof",
      "status": "open",
      "priority": "medium",
      "due_date": "2024-12-31",
      "assigned_to": 1,
      "customer_id": 1,
      "created_at": "2024-01-01T00:00:00Z",
      "updated_at": "2024-01-01T00:00:00Z"
    }
  ],
  "meta": {
    "current_page": 1,
    "total": 50,
    "per_page": 15
  }
}
```

#### Neue Aufgabe erstellen
```http
POST /api/app/tasks
```
**Berechtigung**: `tasks:create`  
**Content-Type**: `application/json`

**Request Body**:
```json
{
  "title": "Solar Panel Installation",
  "description": "Install solar panels on customer roof",
  "status": "open",
  "priority": "medium",
  "due_date": "2024-12-31",
  "assigned_to": 1,
  "customer_id": 1,
  "supplier_id": 1,
  "project_id": 1
}
```

**Status-Werte**: `open`, `in_progress`, `completed`, `cancelled`  
**Priority-Werte**: `low`, `medium`, `high`, `urgent`

#### Einzelne Aufgabe abrufen
```http
GET /api/app/tasks/{task_id}
```
**Berechtigung**: `tasks:read`

#### Aufgabe aktualisieren
```http
PUT /api/app/tasks/{task_id}
```
**Berechtigung**: `tasks:update`

#### Aufgabe löschen
```http
DELETE /api/app/tasks/{task_id}
```
**Berechtigung**: `tasks:delete`

#### Aufgaben-Status ändern
```http
PATCH /api/app/tasks/{task_id}/status
```
**Berechtigung**: `tasks:status`

**Request Body**:
```json
{
  "status": "in_progress"
}
```

#### Aufgabe zuweisen
```http
PATCH /api/app/tasks/{task_id}/assign
```
**Berechtigung**: `tasks:assign`

**Request Body**:
```json
{
  "assigned_to": 2
}
```

#### Zeiterfassung aktualisieren
```http
PATCH /api/app/tasks/{task_id}/time
```
**Berechtigung**: `tasks:time`

#### Unteraufgaben abrufen
```http
GET /api/app/tasks/{task_id}/subtasks
```
**Berechtigung**: `tasks:read`

---

### 👥 Kunden-Management

#### Kunden abrufen
```http
GET /api/app/customers
```
**Berechtigung**: `customers:read`

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "name": "Max Mustermann",
      "email": "max@example.com",
      "customer_type": "private",
      "company_name": null,
      "status": "active"
    }
  ]
}
```

#### Neuen Kunden erstellen
```http
POST /api/app/customers
```
**Berechtigung**: `customers:create`

#### Einzelnen Kunden abrufen
```http
GET /api/app/customers/{customer_id}
```
**Berechtigung**: `customers:read`

#### Kunden aktualisieren
```http
PUT /api/app/customers/{customer_id}
```
**Berechtigung**: `customers:update`

#### Kunden löschen
```http
DELETE /api/app/customers/{customer_id}
```
**Berechtigung**: `customers:delete`

#### Kunden-Status ändern
```http
PATCH /api/app/customers/{customer_id}/status
```
**Berechtigung**: `customers:status`

#### Kunden-Beteiligungen abrufen
```http
GET /api/app/customers/{customer_id}/participations
```
**Berechtigung**: `customers:read`

#### Kunden-Projekte abrufen
```http
GET /api/app/customers/{customer_id}/projects
```
**Berechtigung**: `customers:read`

#### Kunden-Aufgaben abrufen
```http
GET /api/app/customers/{customer_id}/tasks
```
**Berechtigung**: `customers:read`

#### Kunden-Finanzen abrufen
```http
GET /api/app/customers/{customer_id}/financials
```
**Berechtigung**: `customers:read`

---

### ☀️ Solaranlagen-Management

#### Solaranlagen abrufen
```http
GET /api/app/solar-plants
```
**Berechtigung**: `solar-plants:read`

**Response**:
```json
{
  "data": [
    {
      "id": 1,
      "plant_number": "SP-001",
      "name": "Solaranlage Mustermann",
      "location": "Musterstraße 123, 12345 Musterstadt",
      "total_capacity_kw": 10.5,
      "status": "active"
    }
  ]
}
```

#### Neue Solaranlage erstellen
```http
POST /api/app/solar-plants
```
**Berechtigung**: `solar-plants:create`

#### Einzelne Solaranlage abrufen
```http
GET /api/app/solar-plants/{plant_id}
```
**Berechtigung**: `solar-plants:read`

#### Solaranlage aktualisieren
```http
PUT /api/app/solar-plants/{plant_id}
```
**Berechtigung**: `solar-plants:update`

#### Solaranlage löschen
```http
DELETE /api/app/solar-plants/{plant_id}
```
**Berechtigung**: `solar-plants:delete`

#### Solaranlagen-Komponenten abrufen
```http
GET /api/app/solar-plants/{plant_id}/components
```
**Berechtigung**: `solar-plants:read`

#### Solaranlagen-Beteiligungen abrufen
```http
GET /api/app/solar-plants/{plant_id}/participations
```
**Berechtigung**: `solar-plants:read`

#### Monatliche Ergebnisse abrufen
```http
GET /api/app/solar-plants/{plant_id}/monthly-results
```
**Berechtigung**: `solar-plants:read`

#### Solaranlagen-Statistiken abrufen
```http
GET /api/app/solar-plants/{plant_id}/statistics
```
**Berechtigung**: `solar-plants:read`

---

### 🏢 Lieferanten-Management

#### Lieferanten abrufen
```http
GET /api/app/suppliers
```
**Berechtigung**: `suppliers:read`

#### Neuen Lieferanten erstellen
```http
POST /api/app/suppliers
```
**Berechtigung**: `suppliers:create`

#### Einzelnen Lieferanten abrufen
```http
GET /api/app/suppliers/{supplier_id}
```
**Berechtigung**: `suppliers:read`

#### Lieferanten aktualisieren
```http
PUT /api/app/suppliers/{supplier_id}
```
**Berechtigung**: `suppliers:update`

#### Lieferanten löschen
```http
DELETE /api/app/suppliers/{supplier_id}
```
**Berechtigung**: `suppliers:delete`

#### Lieferanten-Status ändern
```http
PATCH /api/app/suppliers/{supplier_id}/status
```
**Berechtigung**: `suppliers:status`

#### Lieferanten-Verträge abrufen
```http
GET /api/app/suppliers/{supplier_id}/contracts
```
**Berechtigung**: `suppliers:read`

#### Lieferanten-Projekte abrufen
```http
GET /api/app/suppliers/{supplier_id}/projects
```
**Berechtigung**: `suppliers:read`

#### Lieferanten-Performance abrufen
```http
GET /api/app/suppliers/{supplier_id}/performance
```
**Berechtigung**: `suppliers:read`

---

### 📊 Projekt-Management

#### Projekte abrufen
```http
GET /api/app/projects
```
**Berechtigung**: `projects:read`

#### Neues Projekt erstellen
```http
POST /api/app/projects
```
**Berechtigung**: `projects:create`

#### Einzelnes Projekt abrufen
```http
GET /api/app/projects/{project_id}
```
**Berechtigung**: `projects:read`

#### Projekt aktualisieren
```http
PUT /api/app/projects/{project_id}
```
**Berechtigung**: `projects:update`

#### Projekt löschen
```http
DELETE /api/app/projects/{project_id}
```
**Berechtigung**: `projects:delete`

#### Projekt-Status ändern
```http
PATCH /api/app/projects/{project_id}/status
```
**Berechtigung**: `projects:status`

#### Projekt-Fortschritt abrufen
```http
GET /api/app/projects/{project_id}/progress
```
**Berechtigung**: `projects:read`

#### Projekt-Fortschritt aktualisieren
```http
PATCH /api/app/projects/{project_id}/progress
```
**Berechtigung**: `projects:update`

---

### 🎯 Projekt-Meilensteine

#### Projekt-Meilensteine abrufen
```http
GET /api/app/projects/{project_id}/milestones
```
**Berechtigung**: `milestones:read`

#### Neuen Meilenstein erstellen
```http
POST /api/app/projects/{project_id}/milestones
```
**Berechtigung**: `milestones:create`

#### Alle Meilensteine abrufen
```http
GET /api/app/project-milestones
```
**Berechtigung**: `milestones:read`

#### Einzelnen Meilenstein abrufen
```http
GET /api/app/project-milestones/{milestone_id}
```
**Berechtigung**: `milestones:read`

#### Meilenstein aktualisieren
```http
PUT /api/app/project-milestones/{milestone_id}
```
**Berechtigung**: `milestones:update`

#### Meilenstein löschen
```http
DELETE /api/app/project-milestones/{milestone_id}
```
**Berechtigung**: `milestones:delete`

#### Meilenstein-Status ändern
```http
PATCH /api/app/project-milestones/{milestone_id}/status
```
**Berechtigung**: `milestones:status`

---

### 📅 Projekt-Termine

#### Projekt-Termine abrufen
```http
GET /api/app/projects/{project_id}/appointments
```
**Berechtigung**: `appointments:read`

#### Neuen Termin erstellen
```http
POST /api/app/projects/{project_id}/appointments
```
**Berechtigung**: `appointments:create`

#### Alle Termine abrufen
```http
GET /api/app/project-appointments
```
**Berechtigung**: `appointments:read`

#### Anstehende Termine abrufen
```http
GET /api/app/project-appointments/upcoming
```
**Berechtigung**: `appointments:read`

#### Kalender-Ansicht abrufen
```http
GET /api/app/project-appointments/calendar
```
**Berechtigung**: `appointments:read`

---

### 💰 Kosten-Management

#### Kosten-Übersicht abrufen
```http
GET /api/app/costs/overview
```
**Berechtigung**: `costs:read`

#### Kosten-Reports abrufen
```http
GET /api/app/costs/reports
```
**Berechtigung**: `costs:reports`

#### Projekt-Kosten abrufen
```http
GET /api/app/projects/{project_id}/costs
```
**Berechtigung**: `costs:read`

#### Projekt-Kosten hinzufügen
```http
POST /api/app/projects/{project_id}/costs
```
**Berechtigung**: `costs:create`

#### Solaranlagen-Kosten abrufen
```http
GET /api/app/solar-plants/{plant_id}/costs
```
**Berechtigung**: `costs:read`

#### Solaranlagen-Abrechnungen abrufen
```http
GET /api/app/solar-plants/{plant_id}/billings
```
**Berechtigung**: `costs:read`

---

### 📋 Dropdown-Daten und Optionen

#### Benutzer für Dropdowns abrufen
```http
GET /api/app/users
```
**Berechtigung**: `tasks:read`

#### Kunden für Dropdowns abrufen
```http
GET /api/app/customers
```
**Berechtigung**: `tasks:read`

#### Lieferanten für Dropdowns abrufen
```http
GET /api/app/suppliers
```
**Berechtigung**: `tasks:read`

#### Solaranlagen für Dropdowns abrufen
```http
GET /api/app/solar-plants-dropdown
```
**Berechtigung**: `tasks:read`

#### Optionen für verschiedene Bereiche
```http
GET /api/app/options/tasks
GET /api/app/options/projects
GET /api/app/options/milestones
GET /api/app/options/appointments
GET /api/app/options/costs
GET /api/app/options/customers
GET /api/app/options/suppliers
```

---

## 🔒 Berechtigungen

Die API verwendet ein granulares Berechtigungssystem. Jeder App Token hat spezifische Berechtigungen:

### Aufgaben-Berechtigungen
- `tasks:read` - Aufgaben lesen
- `tasks:create` - Aufgaben erstellen
- `tasks:update` - Aufgaben aktualisieren
- `tasks:delete` - Aufgaben löschen
- `tasks:status` - Aufgaben-Status ändern
- `tasks:assign` - Aufgaben zuweisen
- `tasks:time` - Zeiterfassung verwalten

### Kunden-Berechtigungen
- `customers:read` - Kunden lesen
- `customers:create` - Kunden erstellen
- `customers:update` - Kunden aktualisieren
- `customers:delete` - Kunden löschen
- `customers:status` - Kunden-Status ändern

### Solaranlagen-Berechtigungen
- `solar-plants:read` - Solaranlagen lesen
- `solar-plants:create` - Solaranlagen erstellen
- `solar-plants:update` - Solaranlagen aktualisieren
- `solar-plants:delete` - Solaranlagen löschen

### Weitere Berechtigungen
- `suppliers:*` - Lieferanten-Management
- `projects:*` - Projekt-Management
- `milestones:*` - Meilenstein-Management
- `appointments:*` - Termin-Management
- `costs:*` - Kosten-Management

---

## 📊 HTTP Status Codes

- `200 OK` - Erfolgreiche Anfrage
- `201 Created` - Ressource erfolgreich erstellt
- `401 Unauthorized` - Nicht authentifiziert (fehlender/ungültiger Token)
- `403 Forbidden` - Keine Berechtigung für diese Aktion
- `404 Not Found` - Ressource nicht gefunden
- `422 Unprocessable Entity` - Validierungsfehler
- `500 Internal Server Error` - Serverfehler

---

## 🔧 Fehlerbehandlung

Fehler werden im JSON-Format zurückgegeben:

```json
{
  "message": "Validation failed",
  "errors": {
    "title": ["The title field is required."],
    "status": ["The selected status is invalid."]
  }
}
```

---

## 📝 Beispiel-Integration

### JavaScript/Node.js
```javascript
const API_BASE = 'https://prosoltec.voltmaster.cloud';
const API_TOKEN = 'your-app-token-here';

async function getTasks() {
  const response = await fetch(`${API_BASE}/api/app/tasks`, {
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json'
    }
  });
  
  if (!response.ok) {
    throw new Error(`HTTP error! status: ${response.status}`);
  }
  
  return await response.json();
}

async function createTask(taskData) {
  const response = await fetch(`${API_BASE}/api/app/tasks`, {
    method: 'POST',
    headers: {
      'Authorization': `Bearer ${API_TOKEN}`,
      'Content-Type': 'application/json'
    },
    body: JSON.stringify(taskData)
  });
  
  return await response.json();
}
```

### PHP
```php
<?php
$apiBase = 'https://prosoltec.voltmaster.cloud';
$apiToken = 'your-app-token-here';

function makeApiRequest($endpoint, $method = 'GET', $data = null) {
    global $apiBase, $apiToken;
    
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiBase . $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $apiToken,
            'Content-Type: application/json'
        ]
    ]);
    
    if ($data) {
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($curl);
    curl_close($curl);
    
    return json_decode($response, true);
}

// Beispiel: Aufgaben abrufen
$tasks = makeApiRequest('/api/app/tasks');
?>
```

### Python
```python
import requests

API_BASE = 'https://prosoltec.voltmaster.cloud'
API_TOKEN = 'your-app-token-here'

class VoltMasterAPI:
    def __init__(self, token):
        self.token = token
        self.headers = {
            'Authorization': f'Bearer {token}',
            'Content-Type': 'application/json'
        }
    
    def get_tasks(self):
        response = requests.get(f'{API_BASE}/api/app/tasks', headers=self.headers)
        response.raise_for_status()
        return response.json()
    
    def create_task(self, task_data):
        response = requests.post(f'{API_BASE}/api/app/tasks', 
                               json=task_data, headers=self.headers)
        response.raise_for_status()
        return response.json()

# Verwendung
api = VoltMasterAPI(API_TOKEN)
tasks = api.get_tasks()
```

---

## 📞 Support

Bei Fragen zur API-Integration wenden Sie sich an:
- **Email**: api@voltmaster.com
- **Dokumentation**: https://prosoltec.voltmaster.cloud/api/documentation

---

## 📄 Lizenz

Diese API-Dokumentation ist urheberrechtlich geschützt. Die Nutzung der API unterliegt den Nutzungsbedingungen von VoltMaster.