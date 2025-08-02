# 🔧 Swagger API-Dokumentation reparieren

## Problem
Error 500 beim Laden von `https://sunnybill-test.test/docs?api-docs.yaml`

## Lösungsschritte

### 1. Cache leeren und Swagger neu generieren

```bash
# Laravel Cache leeren
php artisan config:clear
php artisan cache:clear
php artisan view:clear

# Swagger Dokumentation neu generieren
php artisan l5-swagger:generate

# Falls das fehlschlägt, manuell löschen:
rm storage/api-docs/api-docs.json
rm storage/api-docs/api-docs.yaml
```

### 2. Swagger-Konfiguration prüfen

In `config/l5-swagger.php` sollte stehen:
```php
'format_to_use_for_docs' => env('L5_FORMAT_TO_USE_FOR_DOCS', 'json'),
```

**Ändern Sie von 'yaml' zu 'json'** in der Konfiguration oder `.env`:
```bash
# In .env hinzufügen:
L5_FORMAT_TO_USE_FOR_DOCS=json
```

### 3. Alternative URLs testen

```bash
# Swagger UI
http://sunnybill-test.test/api/documentation

# Direkte API-Docs
http://sunnybill-test.test/docs/api-docs.json
http://sunnybill-test.test/docs/api-docs.yaml

# Alternative Route
http://sunnybill-test.test/docs
```

### 4. Schnelltest - API direkt testen

Da die API-Routen existieren, können Sie sie direkt testen:

```bash
# User-Suche (funktioniert ohne Token)
curl -X GET "http://sunnybill-test.test/api/users/search?q=test"
curl -X GET "http://sunnybill-test.test/api/users/all"

# Mit authentifiziertem User (falls Sanctum Token vorhanden)
curl -X GET "http://sunnybill-test.test/api/user" \
  -H "Authorization: Bearer YOUR_SANCTUM_TOKEN"
```

### 5. Permissions prüfen

```bash
# Storage-Ordner Berechtigungen
chmod -R 775 storage/api-docs/
chown -R www-data:www-data storage/api-docs/

# Falls Windows:
# Rechtsklick auf storage/api-docs -> Eigenschaften -> Sicherheit -> Bearbeiten
```

### 6. Debugging aktivieren

In `.env`:
```bash
APP_DEBUG=true
LOG_LEVEL=debug
```

Dann Log prüfen:
```bash
tail -f storage/logs/laravel.log
```

### 7. Manueller Fix - API-Docs überschreiben

Falls alles fehlschlägt, können Sie die vorhandene YAML-Datei reparieren:

```bash
# Backup erstellen
cp storage/api-docs/api-docs.yaml storage/api-docs/api-docs.yaml.backup

# Swagger regenerieren mit nur Basis-Controller
php artisan l5-swagger:generate
```

## 🚀 Schnellste Lösung

```bash
# 1. Format auf JSON ändern
echo "L5_FORMAT_TO_USE_FOR_DOCS=json" >> .env

# 2. Cache leeren
php artisan config:clear

# 3. Swagger neu generieren
php artisan l5-swagger:generate

# 4. Aufrufen
# Browser: http://sunnybill-test.test/api/documentation
```

## 📋 Verfügbare API-Endpunkte (funktionieren ohne Swagger)

### Basis-Endpunkte (funktionieren sofort):
- `GET /api/users/search?q=name` - User-Suche
- `GET /api/users/all` - Alle User

### App-Token-Endpunkte (benötigen Authentication):
- `GET /api/app/profile` - User-Profil  
- `GET /api/app/tasks` - Aufgaben
- `GET /api/app/customers` - Kunden
- `GET /api/app/suppliers` - Lieferanten
- `GET /api/app/solar-plants` - Solaranlagen
- `GET /api/app/projects` - Projekte

## 📖 Vollständige API-Dokumentation

Die vollständige API-Dokumentation ist bereits in `storage/api-docs/api-docs.yaml` vorhanden und sehr detailliert. Das Problem ist nur, dass Swagger sie nicht laden kann.

**Versuchen Sie zuerst die schnellste Lösung oben!**