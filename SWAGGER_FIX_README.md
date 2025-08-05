# 🔧 Swagger API Dokumentation - Live-Server Fix

## Problem
Die API-Dokumentation funktioniert lokal, aber auf dem Live-Server (https://prosoltec.voltmaster.cloud/api/documentation) gibt es einen 403 Forbidden Fehler:

```
GET https://prosoltec.voltmaster.cloud/docs/?api-docs.json 403 (Forbidden)
```

## Ursachen
1. **Falsche Server-URL**: Die generierte API-Dokumentation verwendet `http://my-default-host.com` statt der korrekten Live-Server URL
2. **Fehlende Environment-Konfiguration**: `L5_SWAGGER_CONST_HOST` ist nicht für den Live-Server konfiguriert
3. **Veraltete API-Dokumentation**: Die Dokumentation wurde möglicherweise nicht mit der korrekten Konfiguration generiert

## 🚀 Lösung

### Schritt 1: Environment-Konfiguration auf Live-Server aktualisieren

Füge diese Zeilen zur `.env` Datei auf dem Live-Server hinzu:

```bash
# Swagger Configuration für Live-Server
L5_SWAGGER_GENERATE_ALWAYS=false
L5_SWAGGER_USE_ABSOLUTE_PATH=true
L5_SWAGGER_CONST_HOST=https://prosoltec.voltmaster.cloud
L5_FORMAT_TO_USE_FOR_DOCS=json
APP_URL=https://prosoltec.voltmaster.cloud
```

### Schritt 2: Deployment-Script ausführen

1. Lade das `deploy-swagger.sh` Script auf den Live-Server hoch
2. Mache es ausführbar:
   ```bash
   chmod +x deploy-swagger.sh
   ```
3. Führe es aus:
   ```bash
   ./deploy-swagger.sh
   ```

### Schritt 3: Manuelle Alternative

Falls das Script nicht funktioniert, führe diese Befehle manuell aus:

```bash
# Cache leeren
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# Konfiguration neu laden
php artisan config:cache

# Alte API-Docs löschen
rm -rf storage/api-docs/*
rm -rf public/docs/*

# Neue API-Docs generieren
php artisan l5-swagger:generate

# Berechtigungen setzen
chmod -R 755 storage/api-docs/
chmod -R 755 public/docs/
```

### Schritt 4: Webserver-Konfiguration prüfen

Stelle sicher, dass der Webserver (nginx/Apache) Zugriff auf die `/docs` Route hat:

**Für nginx:**
```nginx
location /docs {
    try_files $uri $uri/ /index.php?$query_string;
}

location /api/documentation {
    try_files $uri $uri/ /index.php?$query_string;
}
```

**Für Apache (.htaccess ist bereits korrekt konfiguriert)**

## 🔍 Verifikation

Nach der Implementierung sollte:

1. **API-Dokumentation erreichbar sein**: https://prosoltec.voltmaster.cloud/api/documentation
2. **JSON-Endpoint funktionieren**: https://prosoltec.voltmaster.cloud/docs/api-docs.json
3. **Korrekte Server-URL in der Dokumentation stehen**: `https://prosoltec.voltmaster.cloud`

## 📝 Änderungen im Code

### config/l5-swagger.php
- Geändert: `L5_SWAGGER_CONST_HOST` verwendet jetzt `APP_URL` als Fallback

### Neue Dateien
- `.env.production`: Template für Live-Server Environment-Konfiguration
- `deploy-swagger.sh`: Automatisches Deployment-Script
- `SWAGGER_FIX_README.md`: Diese Anleitung

## 🚨 Wichtige Hinweise

1. **Backup erstellen**: Erstelle vor Änderungen ein Backup der aktuellen `.env` Datei
2. **Cache leeren**: Nach Konfigurationsänderungen immer den Cache leeren
3. **Berechtigungen**: Stelle sicher, dass der Webserver Schreibrechte auf `storage/api-docs/` hat
4. **HTTPS**: Verwende immer HTTPS URLs für den Live-Server

## 🔧 Troubleshooting

### Problem: 403 Forbidden bleibt bestehen
- Prüfe Webserver-Logs: `/var/log/nginx/error.log` oder `/var/log/apache2/error.log`
- Prüfe Dateiberechtigungen: `ls -la storage/api-docs/`
- Prüfe ob die Route registriert ist: `php artisan route:list | grep docs`

### Problem: Falsche Server-URL in Dokumentation
- Prüfe `.env` Konfiguration: `php artisan config:show app.url`
- Regeneriere Dokumentation: `php artisan l5-swagger:generate`

### Problem: Dokumentation wird nicht generiert
- Prüfe Schreibrechte: `chmod -R 755 storage/api-docs/`
- Prüfe Logs: `tail -f storage/logs/laravel.log`