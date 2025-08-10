# Solaranlagen-API Dokumentation

## Übersicht
Die Solaranlagen-API ermöglicht die vollständige Verwaltung von Solaranlagen (CRUD) sowie erweiterte Funktionen wie Komponenten-Management, Beteiligungsverwaltung, monatliche Ergebnisse und detaillierte Statistiken.

**Base URL:** `/api/solar-plants`

---

## 🔐 Authentifizierung
Alle Endpoints erfordern ein gültiges App-Token im Header:
```
Authorization: Bearer YOUR_APP_TOKEN
```

### Erforderliche Berechtigungen:
- `solar-plants:read` - Solaranlagen lesen
- `solar-plants:create` - Solaranlagen erstellen
- `solar-plants:update` - Solaranlagen aktualisieren  
- `solar-plants:delete` - Solaranlagen löschen

---

## 📋 1. Solaranlagen auflisten

### `GET /api/solar-plants`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Listet alle Solaranlagen mit erweiterten Filter- und Suchoptionen auf.

### Query Parameter:

| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `status` | string | Filtert nach Anlagenstatus | `active`, `planning`, `construction`, `maintenance` |
| `is_active` | boolean | Filtert nach Aktivitätsstatus | `true`, `false` |
| `location` | string | Filtert nach Standort (LIKE-Suche) | `Hamburg` |
| `min_capacity` | number | Mindestkapazität in kW | `50.0` |
| `max_capacity` | number | Maximalkapazität in kW | `500.0` |
| `commissioning_from` | date | Inbetriebnahme ab Datum | `2024-01-01` |
| `commissioning_to` | date | Inbetriebnahme bis Datum | `2024-12-31` |
| `search` | string | Sucht in Name, Standort, Anlagennummer, App-Code | `Solar Nord` |
| `sort_by` | string | Sortierfeld | `created_at`, `name`, `total_capacity_kw`, `commissioning_date` |
| `sort_direction` | string | Sortierrichtung | `asc`, `desc` |
| `per_page` | integer | Anzahl pro Seite (max. 100) | `25` |

### Beispiel Request:
```http
GET /api/solar-plants?status=active&min_capacity=100&location=Hamburg&per_page=25&sort_by=total_capacity_kw&sort_direction=desc
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Solarpark Hamburg Nord",
      "location": "Hamburg, Industriegebiet Nord",
      "plant_number": "SP-HAM-001",
      "app_code": "HAM001",
      "status": "active",
      "is_active": true,
      "total_capacity_kw": 250.5,
      "panel_count": 1000,
      "inverter_count": 5,
      "battery_capacity_kwh": 100.0,
      "expected_annual_yield_kwh": 275000.0,
      "total_investment": 450000.00,
      "commissioning_date": "2024-03-15",
      "latitude": 53.5511,
      "longitude": 9.9937,
      "created_at": "2024-01-15T10:30:00.000000Z",
      "updated_at": "2024-01-15T10:30:00.000000Z",
      "participations": [
        {
          "id": 1,
          "customer_id": 5,
          "investment_amount": 25000.00,
          "percentage": 5.5
        }
      ],
      "solar_inverters": [
        {
          "id": 1,
          "manufacturer": "SMA",
          "model": "STP 50-40",
          "power_kw": 50.0,
          "quantity": 5
        }
      ],
      "solar_modules": [...],
      "solar_batteries": [...],
      "supplier_contracts": [...],
      "monthly_results": [...]
    }
  ],
  "pagination": {
    "current_page": 1,
    "last_page": 4,
    "per_page": 25,
    "total": 89
  }
}
```

---

## 👁️ 2. Einzelne Solaranlage anzeigen

### `GET /api/solar-plants/{solarPlant}`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Zeigt detaillierte Informationen einer einzelnen Solaranlage mit allen verknüpften Daten.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Beispiel Request:
```http
GET /api/solar-plants/1
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "id": 1,
    "name": "Solarpark Hamburg Nord",
    "location": "Hamburg, Industriegebiet Nord",
    "plant_number": "SP-HAM-001",
    "app_code": "HAM001",
    "plot_number": "Flurstück 42/1",
    "mastr_number_unit": "SEE123456789",
    "mastr_registration_date_unit": "2024-02-01",
    "mastr_number_eeg_plant": "EEG987654321",
    "commissioning_date_eeg_plant": "2024-03-15",
    "malo_id": "MALO12345",
    "melo_id": "MELO67890",
    "vnb_process_number": "VNB2024-001",
    "commissioning_date_unit": "2024-03-15",
    "unit_commissioning_date": "2024-03-15",
    "pv_soll_planning_date": "2024-01-10",
    "pv_soll_project_number": "PVS-2024-001",
    "latitude": 53.5511,
    "longitude": 9.9937,
    "description": "Große Freiflächenanlage mit Ost-West-Ausrichtung",
    "installation_date": "2024-02-20",
    "planned_installation_date": "2024-02-15",
    "commissioning_date": "2024-03-15",
    "planned_commissioning_date": "2024-03-10",
    "total_capacity_kw": 250.5,
    "panel_count": 1000,
    "inverter_count": 5,
    "battery_capacity_kwh": 100.0,
    "expected_annual_yield_kwh": 275000.0,
    "total_investment": 450000.00,
    "annual_operating_costs": 12500.00,
    "feed_in_tariff_per_kwh": 0.0822,
    "electricity_price_per_kwh": 0.30,
    "degradation_rate": 0.5,
    "status": "active",
    "is_active": true,
    "notes": "Anlage läuft optimal",
    "custom_field_1": "Wert 1",
    "custom_field_2": "Wert 2",
    "custom_field_3": "Wert 3",
    "custom_field_4": "Wert 4",
    "custom_field_5": "Wert 5",
    "created_at": "2024-01-15T10:30:00.000000Z",
    "updated_at": "2024-01-15T10:30:00.000000Z",
    "statistics": {
      "total_participation": 85.5,
      "available_participation": 14.5,
      "participations_count": 12,
      "total_inverter_power": 250.0,
      "total_module_power": 250.5,
      "total_battery_capacity": 100.0,
      "current_total_power": 180.2,
      "current_battery_soc": 75.5,
      "components_count": 1006,
      "formatted_coordinates": "53.5511°N, 9.9937°E",
      "google_maps_url": "https://maps.google.com/?q=53.5511,9.9937"
    },
    "participations": [...],
    "solar_inverters": [...],
    "solar_modules": [...],
    "solar_batteries": [...],
    "supplier_contracts": [...],
    "monthly_results": [...],
    "target_yields": [...],
    "notes": [...],
    "documents": [...]
  }
}
```

---

## ➕ 3. Neue Solaranlage erstellen

### `POST /api/solar-plants`

**Berechtigung:** `solar-plants:create`

**Beschreibung:** Erstellt eine neue Solaranlage mit umfangreichen technischen und finanziellen Parametern.

### Request Body:
```json
{
  "name": "Solarpark München Süd",
  "location": "München, Gewerbegebiet Süd",
  "plot_number": "Flurstück 15/3",
  "mastr_number_unit": "SEE999888777",
  "mastr_registration_date_unit": "2024-04-01",
  "mastr_number_eeg_plant": "EEG111222333",
  "commissioning_date_eeg_plant": "2024-05-20",
  "malo_id": "MALO99999",
  "melo_id": "MELO88888",
  "vnb_process_number": "VNB2024-025",
  "commissioning_date_unit": "2024-05-20",
  "unit_commissioning_date": "2024-05-20",
  "pv_soll_planning_date": "2024-02-01",
  "pv_soll_project_number": "PVS-2024-025",
  "latitude": 48.1351,
  "longitude": 11.5820,
  "description": "Moderne Aufdachanlage mit Batteriespeicher",
  "installation_date": "2024-04-15",
  "planned_installation_date": "2024-04-10",
  "commissioning_date": "2024-05-20",
  "planned_commissioning_date": "2024-05-15",
  "total_capacity_kw": 150.0,
  "panel_count": 600,
  "inverter_count": 3,
  "battery_capacity_kwh": 75.0,
  "expected_annual_yield_kwh": 165000.0,
  "total_investment": 280000.00,
  "annual_operating_costs": 8500.00,
  "feed_in_tariff_per_kwh": 0.0822,
  "electricity_price_per_kwh": 0.32,
  "degradation_rate": 0.5,
  "status": "active",
  "is_active": true,
  "notes": "Neue Anlage in optimaler Südausrichtung"
}
```

### Validierungsregeln:

| Feld | Regeln | Beschreibung |
|------|--------|--------------|
| `name` | **Pflicht** - String, max. 255 | Name der Solaranlage |
| `location` | **Pflicht** - String, max. 255 | Standort der Anlage |
| `plot_number` | Optional - String, max. 255 | Flurstücksnummer |
| `mastr_number_unit` | Optional - String, max. 255 | Marktstammdatenregister-Nummer (Einheit) |
| `mastr_registration_date_unit` | Optional - Date | MaStR Registrierungsdatum (Einheit) |
| `mastr_number_eeg_plant` | Optional - String, max. 255 | MaStR-Nummer (EEG-Anlage) |
| `commissioning_date_eeg_plant` | Optional - Date | Inbetriebnahmedatum (EEG-Anlage) |
| `malo_id` | Optional - String, max. 255 | Marktlokations-ID |
| `melo_id` | Optional - String, max. 255 | Messlokations-ID |
| `vnb_process_number` | Optional - String, max. 255 | VNB-Vorgangsnummer |
| `commissioning_date_unit` | Optional - Date | Inbetriebnahmedatum (Einheit) |
| `unit_commissioning_date` | Optional - Date | Einheit-Inbetriebnahmedatum |
| `pv_soll_planning_date` | Optional - Date | PV-Soll Planungsdatum |
| `pv_soll_project_number` | Optional - String, max. 255 | PV-Soll Projektnummer |
| `latitude` | Optional - Numeric, -90 bis 90 | Breitengrad |
| `longitude` | Optional - Numeric, -180 bis 180 | Längengrad |
| `description` | Optional - Text | Beschreibung der Anlage |
| `installation_date` | Optional - Date | Installationsdatum |
| `planned_installation_date` | Optional - Date | Geplantes Installationsdatum |
| `commissioning_date` | Optional - Date | Inbetriebnahmedatum |
| `planned_commissioning_date` | Optional - Date | Geplantes Inbetriebnahmedatum |
| `total_capacity_kw` | Optional - Numeric, min. 0 | Gesamtkapazität in kW |
| `panel_count` | Optional - Integer, min. 0 | Anzahl Module |
| `inverter_count` | Optional - Integer, min. 0 | Anzahl Wechselrichter |
| `battery_capacity_kwh` | Optional - Numeric, min. 0 | Batteriekapazität in kWh |
| `expected_annual_yield_kwh` | Optional - Numeric, min. 0 | Erwarteter Jahresertrag in kWh |
| `total_investment` | Optional - Numeric, min. 0 | Gesamtinvestition in Euro |
| `annual_operating_costs` | Optional - Numeric, min. 0 | Jährliche Betriebskosten in Euro |
| `feed_in_tariff_per_kwh` | Optional - Numeric, min. 0 | Einspeisevergütung pro kWh |
| `electricity_price_per_kwh` | Optional - Numeric, min. 0 | Strompreis pro kWh |
| `degradation_rate` | Optional - Numeric, 0-100 | Degradationsrate in % |
| `status` | Optional - String, max. 255 | Status der Anlage |
| `is_active` | Optional - Boolean | Ist die Anlage aktiv? |
| `notes` | Optional - Text | Notizen zur Anlage |
| `custom_field_1` bis `custom_field_5` | Optional - String, max. 255 | Benutzerdefinierte Felder |

### Beispiel Request:
```http
POST /api/solar-plants
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "name": "Solarpark München Süd",
  "location": "München, Gewerbegebiet Süd",
  "total_capacity_kw": 150.0,
  "panel_count": 600,
  "expected_annual_yield_kwh": 165000.0,
  "total_investment": 280000.00,
  "status": "active"
}
```

### Response (201 Created):
```json
{
  "success": true,
  "message": "Solaranlage erfolgreich erstellt",
  "data": {
    "id": 25,
    "name": "Solarpark München Süd",
    "location": "München, Gewerbegebiet Süd",
    "total_capacity_kw": 150.0,
    "...": "..."
  }
}
```

---

## ✏️ 4. Solaranlage aktualisieren

### `PUT /api/solar-plants/{solarPlant}`

**Berechtigung:** `solar-plants:update`

**Beschreibung:** Aktualisiert eine bestehende Solaranlage. Partielle Updates sind möglich.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Request Body:
```json
{
  "total_capacity_kw": 155.0,
  "panel_count": 620,
  "status": "maintenance",
  "notes": "Wartung durchgeführt, Kapazität optimiert"
}
```

**Hinweis:** Alle Felder sind optional. Nur übermittelte Felder werden aktualisiert.

### Beispiel Request:
```http
PUT /api/solar-plants/25
Authorization: Bearer your_app_token_here
Content-Type: application/json

{
  "total_capacity_kw": 155.0,
  "panel_count": 620,
  "status": "maintenance"
}
```

### Response:
```json
{
  "success": true,
  "message": "Solaranlage erfolgreich aktualisiert",
  "data": {
    "id": 25,
    "name": "Solarpark München Süd",
    "total_capacity_kw": 155.0,
    "panel_count": 620,
    "status": "maintenance",
    "...": "..."
  }
}
```

---

## 🗑️ 5. Solaranlage löschen

### `DELETE /api/solar-plants/{solarPlant}`

**Berechtigung:** `solar-plants:delete`

**Beschreibung:** Löscht eine Solaranlage vollständig aus dem System.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Beispiel Request:
```http
DELETE /api/solar-plants/25
Authorization: Bearer your_app_token_here
```

### Response (200 OK):
```json
{
  "success": true,
  "message": "Solaranlage erfolgreich gelöscht"
}
```

---

## ⚙️ 6. Komponenten einer Solaranlage

### `GET /api/solar-plants/{solarPlant}/components`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Zeigt alle technischen Komponenten einer Solaranlage (Wechselrichter, Module, Batterien).

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Beispiel Request:
```http
GET /api/solar-plants/1/components
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "inverters": [
      {
        "id": 1,
        "solar_plant_id": 1,
        "manufacturer": "SMA",
        "model": "STP 50-40",
        "power_kw": 50.0,
        "efficiency": 98.5,
        "quantity": 5,
        "serial_numbers": ["INV001", "INV002", "INV003", "INV004", "INV005"],
        "installation_date": "2024-02-20",
        "warranty_years": 10,
        "status": "active"
      }
    ],
    "modules": [
      {
        "id": 1,
        "solar_plant_id": 1,
        "manufacturer": "Jinko Solar",
        "model": "JKM410M-54HL4-V",
        "power_wp": 410,
        "efficiency": 20.8,
        "quantity": 1000,
        "total_power_wp": 410000,
        "technology": "Mono-PERC",
        "installation_date": "2024-02-15",
        "warranty_years": 25,
        "status": "active"
      }
    ],
    "batteries": [
      {
        "id": 1,
        "solar_plant_id": 1,
        "manufacturer": "Tesla",
        "model": "Megapack 2XL",
        "capacity_kwh": 100.0,
        "power_kw": 50.0,
        "efficiency": 95.0,
        "quantity": 1,
        "cycle_life": 6000,
        "installation_date": "2024-02-25",
        "warranty_years": 20,
        "status": "active"
      }
    ],
    "statistics": {
      "total_inverter_power": 250.0,
      "total_module_power": 410.0,
      "total_battery_capacity": 100.0,
      "components_count": 1006
    }
  }
}
```

---

## 💰 7. Beteiligungen einer Solaranlage

### `GET /api/solar-plants/{solarPlant}/participations`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Zeigt alle Kundenbeteiligungen an einer Solaranlage mit Investitionsdetails.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Beispiel Request:
```http
GET /api/solar-plants/1/participations
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "participations": [
      {
        "id": 1,
        "customer_id": 5,
        "solar_plant_id": 1,
        "investment_amount": 25000.00,
        "percentage": 5.5,
        "investment_date": "2024-01-15",
        "expected_annual_return": 2750.00,
        "contract_duration_years": 20,
        "status": "active",
        "customer": {
          "id": 5,
          "customer_number": "KD-000005",
          "first_name": "Anna",
          "last_name": "Schmidt",
          "email": "anna.schmidt@example.com",
          "display_name": "Anna Schmidt"
        }
      },
      {
        "id": 2,
        "customer_id": 12,
        "solar_plant_id": 1,
        "investment_amount": 50000.00,
        "percentage": 11.1,
        "investment_date": "2024-02-01",
        "expected_annual_return": 5500.00,
        "contract_duration_years": 20,
        "status": "active",
        "customer": {
          "id": 12,
          "customer_number": "KD-000012",
          "company_name": "Energie GmbH",
          "email": "info@energie-gmbh.de",
          "display_name": "Energie GmbH"
        }
      }
    ],
    "statistics": {
      "total_participation": 85.5,
      "available_participation": 14.5,
      "participations_count": 12
    }
  }
}
```

---

## 📊 8. Monatliche Ergebnisse einer Solaranlage

### `GET /api/solar-plants/{solarPlant}/monthly-results`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Zeigt die monatlichen Leistungs- und Ertragsdaten einer Solaranlage.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Query Parameter:
| Parameter | Typ | Beschreibung | Beispiel |
|-----------|-----|--------------|----------|
| `year` | integer | Filtert nach Jahr | `2024` |
| `month` | integer | Filtert nach Monat (1-12) | `6` |

### Beispiel Request:
```http
GET /api/solar-plants/1/monthly-results?year=2024
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "solar_plant_id": 1,
      "month": "2024-07-01",
      "energy_production_kwh": 28500.50,
      "energy_consumption_kwh": 2100.25,
      "energy_fed_into_grid_kwh": 26400.25,
      "energy_from_grid_kwh": 150.00,
      "feed_in_revenue": 2164.22,
      "grid_costs": 45.00,
      "net_revenue": 2119.22,
      "capacity_factor": 82.5,
      "performance_ratio": 85.2,
      "irradiation_kwh_m2": 165.8,
      "ambient_temperature_avg": 24.5,
      "module_temperature_avg": 42.3,
      "availability_percent": 99.8,
      "downtime_hours": 1.5,
      "maintenance_costs": 250.00,
      "created_at": "2024-08-01T00:00:00.000000Z"
    },
    {
      "id": 2,
      "solar_plant_id": 1,
      "month": "2024-06-01",
      "energy_production_kwh": 26800.75,
      "energy_consumption_kwh": 2050.50,
      "energy_fed_into_grid_kwh": 24750.25,
      "energy_from_grid_kwh": 125.00,
      "feed_in_revenue": 2034.27,
      "grid_costs": 37.50,
      "net_revenue": 1996.77,
      "capacity_factor": 78.9,
      "performance_ratio": 83.4,
      "irradiation_kwh_m2": 158.2,
      "ambient_temperature_avg": 21.8,
      "module_temperature_avg": 39.1,
      "availability_percent": 100.0,
      "downtime_hours": 0.0,
      "maintenance_costs": 0.00,
      "created_at": "2024-07-01T00:00:00.000000Z"
    }
  ]
}
```

---

## 📈 9. Statistiken einer Solaranlage

### `GET /api/solar-plants/{solarPlant}/statistics`

**Berechtigung:** `solar-plants:read`

**Beschreibung:** Liefert umfassende technische, finanzielle und betriebliche Statistiken einer Solaranlage.

### URL Parameter:
| Parameter | Typ | Beschreibung |
|-----------|-----|--------------|
| `solarPlant` | integer | ID der Solaranlage |

### Beispiel Request:
```http
GET /api/solar-plants/1/statistics
Authorization: Bearer your_app_token_here
```

### Response:
```json
{
  "success": true,
  "data": {
    "basic": {
      "plant_number": "SP-HAM-001",
      "app_code": "HAM001",
      "total_capacity_kw": 250.5,
      "panel_count": 1000,
      "inverter_count": 5,
      "battery_capacity_kwh": 100.0,
      "expected_annual_yield_kwh": 275000.0
    },
    "financial": {
      "total_investment": 450000.00,
      "annual_operating_costs": 12500.00,
      "feed_in_tariff_per_kwh": 0.0822,
      "electricity_price_per_kwh": 0.30,
      "degradation_rate": 0.5,
      "formatted_total_investment": "450.000,00 €",
      "formatted_annual_operating_costs": "12.500,00 €",
      "formatted_feed_in_tariff": "0,0822 €/kWh",
      "formatted_electricity_price": "0,30 €/kWh",
      "formatted_degradation_rate": "0,5 %"
    },
    "participations": {
      "total_participation": 85.5,
      "available_participation": 14.5,
      "participations_count": 12
    },
    "components": {
      "total_inverter_power": 250.0,
      "total_module_power": 410.0,
      "total_battery_capacity": 100.0,
      "current_total_power": 180.2,
      "current_battery_soc": 75.5,
      "components_count": 1006
    },
    "location": {
      "latitude": 53.5511,
      "longitude": 9.9937,
      "has_coordinates": true,
      "formatted_coordinates": "53.5511°N, 9.9937°E",
      "google_maps_url": "https://maps.google.com/?q=53.5511,9.9937",
      "open_street_map_url": "https://www.openstreetmap.org/?mlat=53.5511&mlon=9.9937&zoom=15"
    }
  }
}
```

---

## 🚫 Fehlerbehandlung

### Standard HTTP Status Codes:
- `200` - OK (Erfolgreiche GET, PUT, DELETE)
- `201` - Created (Erfolgreiche POST)
- `400` - Bad Request (Allgemeine Fehler)
- `401` - Unauthorized (Fehlendes/ungültiges Token)
- `403` - Forbidden (Fehlende Berechtigung oder Zugriffsbeschränkung)
- `404` - Not Found (Solaranlage nicht gefunden)
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

### Beispiel Validierungsfehler:
```json
{
  "success": false,
  "message": "Validierungsfehler",
  "errors": {
    "name": ["Das Name-Feld ist erforderlich."],
    "location": ["Das Standort-Feld ist erforderlich."],
    "total_capacity_kw": ["Die Kapazität muss eine positive Zahl sein."]
  }
}
```

### Beispiel Zugriffsfehler:
```json
{
  "success": false,
  "message": "Zugriff auf diese Solaranlage nicht erlaubt"
}
```

---

## 🔒 Ressourcen-Beschränkungen

**App-Tokens können auf bestimmte Solaranlagen beschränkt werden:**

### Token-Konfiguration:
```json
{
  "restrict_solar_plants": true,
  "allowed_solar_plants": [1, 5, 10, 23, 42]
}
```

### Auswirkung der Beschränkungen:
- `GET /api/solar-plants` zeigt nur erlaubte Solaranlagen
- Andere Endpoints nur für erlaubte Solaranlagen-IDs zugänglich
- Automatische Filterung in allen Responses
- 403 Forbidden bei Zugriff auf nicht erlaubte Anlagen

---

## 📚 Datenmodell und Beziehungen

### Verknüpfte Daten:
- **Kunden-Beteiligungen** - Prozentuale Anteile von Kunden an der Anlage
- **Technische Komponenten** - Wechselrichter, Module, Batterien
- **Lieferantenverträge** - Wartungs- und Serviceverträge
- **Monatsergebnisse** - Leistungs- und Ertragsdaten
- **Ziel-Erträge** - Geplante vs. tatsächliche Erträge
- **Notizen** - Betriebsnotizen mit Benutzerzuordnung
- **Dokumente** - Technische Unterlagen, Verträge, Zertifikate

### Berechnete Felder:
- `total_participation` - Gesamte vergebene Beteiligungen in %
- `available_participation` - Verfügbare Beteiligungen in %
- `participations_count` - Anzahl der Beteiligungen
- `total_inverter_power` - Gesamtleistung aller Wechselrichter
- `total_module_power` - Gesamtleistung aller Module
- `current_total_power` - Aktuelle Gesamtleistung
- `current_battery_soc` - Aktueller Batterie-Ladezustand
- `components_count` - Gesamtzahl der Komponenten
- `formatted_coordinates` - Formatierte GPS-Koordinaten
- `google_maps_url` - Google Maps URL für Standort

### MaStR-Integration (Marktstammdatenregister):
Die API unterstützt alle relevanten MaStR-Felder:
- `mastr_number_unit` - MaStR-Nummer der Einheit
- `mastr_registration_date_unit` - Registrierungsdatum
- `mastr_number_eeg_plant` - MaStR-Nummer der EEG-Anlage
- `commissioning_date_eeg_plant` - EEG-Inbetriebnahmedatum
- `malo_id` - Marktlokations-ID  
- `melo_id` - Messlokations-ID
- `vnb_process_number` - VNB-Vorgangsnummer

### Technische Spezifikationen:
| Feld | Typ | Einheit | Beschreibung |
|------|-----|---------|--------------|
| `total_capacity_kw` | Decimal | kW | Gesamtkapazität |
| `panel_count` | Integer | Stück | Anzahl Module |
| `inverter_count` | Integer | Stück | Anzahl Wechselrichter |
| `battery_capacity_kwh` | Decimal | kWh | Batteriekapazität |
| `expected_annual_yield_kwh` | Decimal | kWh/Jahr | Erwarteter Jahresertrag |
| `degradation_rate` | Decimal | %/Jahr | Jährliche Degradation |
| `feed_in_tariff_per_kwh` | Decimal | €/kWh | Einspeisevergütung |
| `electricity_price_per_kwh` | Decimal | €/kWh | Strompreis |

---

## 🌍 GPS und Kartendienste

### Koordinaten-Unterstützung:
```json
{
  "latitude": 53.5511,
  "longitude": 9.9937,
  "formatted_coordinates": "53.5511°N, 9.9937°E",
  "google_maps_url": "https://maps.google.com/?q=53.5511,9.9937",
  "open_street_map_url": "https://www.openstreetmap.org/?mlat=53.5511&mlon=9.9937&zoom=15"
}
```

### Validierung:
- Breitengrad: -90 bis +90
- Längengrad: -180 bis +180
- Automatische URL-Generierung für Kartendienste

---

## 📊 Performance und Monitoring

### Verfügbare Metriken:
- **Energieproduktion** (kWh/Monat)
- **Kapazitätsfaktor** (%)
- **Performance Ratio** (%)
- **Verfügbarkeit** (%)
- **Ausfallzeiten** (Stunden)
- **Umgebungstemperatur** (°C)
- **Modultemperatur** (°C)
- **Einstrahlung** (kWh/m²)

### Finanzielle Kennzahlen:
- **Einspeiseerlöse** (€/Monat)
- **Netzkosten** (€/Monat) 
- **Nettoerlöse** (€/Monat)
- **Wartungskosten** (€/Monat)
- **ROI-Berechnung**
- **Amortisationszeit**

---

## 🔧 Benutzerdefinierte Felder

Die API unterstützt 5 benutzerdefinierte Felder für individuelle Anforderungen:
- `custom_field_1` bis `custom_field_5`
- Typ: String (max. 255 Zeichen)
- Verwendung: Interne Referenzen, Zusatzinformationen, Integration mit Drittsystemen

### Beispiel-Verwendung:
```json
{
  "custom_field_1": "Versicherungspolice: POL-2024-001",
  "custom_field_2": "Wartungsintervall: 6 Monate",
  "custom_field_3": "Ansprechpartner: Hans Müller",
  "custom_field_4": "Projektleiter: Anna Schmidt",
  "custom_field_5": "SAP-Nummer: SAP-SP-001"
}
```

---

## 📅 Datum-Management

### Unterstützte Datumsfelder:
- **Planungsdaten:** `pv_soll_planning_date`, `planned_installation_date`, `planned_commissioning_date`
- **Realisierungsdaten:** `installation_date`, `commissioning_date`, `unit_commissioning_date`
- **Registrierungsdaten:** `mastr_registration_date_unit`, `commissioning_date_eeg_plant`

### Datumsformat:
- Format: `YYYY-MM-DD` (ISO 8601)
- Beispiel: `"2024-03-15"`
- Zeitzone: UTC (automatische Konvertierung)

---

## 🚀 Best Practices

### 1. Effiziente Datenabfrage:
```http
# Nur benötigte Felder laden
GET /api/solar-plants?per_page=50&sort_by=total_capacity_kw&sort_direction=desc

# Spezifische Anlage mit allen Details
GET /api/solar-plants/1
```

### 2. Filteroptimierung:
```http
# Mehrere Filter kombinieren
GET /api/solar-plants?status=active&min_capacity=100&location=Hamburg&commissioning_from=2024-01-01
```

### 3. Ressourcen-spezifische Abfragen:
```http
# Nur Komponenten ohne vollständige Anlagendaten
GET /api/solar-plants/1/components

# Nur Statistiken für Dashboard
GET /api/solar-plants/1/statistics
```

### 4. Performance-Monitoring:
```http
# Monatsergebnisse für spezifisches Jahr
GET /api/solar-plants/1/monthly-results?year=2024

# Aktuelle Monatsdaten
GET /api/solar-plants/1/monthly-results?year=2024&month=8
```

Diese umfassende API-Dokumentation deckt alle verfügbaren Solaranlagen-Operationen ab und bietet eine vollständige Referenz für die Integration in Frontend-Anwendungen, Mobile Apps oder Drittsysteme.
