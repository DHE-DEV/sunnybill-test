# SunnyBill Produktions-Checkliste

## ✅ Vor dem Deployment

### 1. Umgebungskonfiguration
- [ ] `.env.production` Datei erstellt und angepasst
- [ ] `APP_ENV=production` gesetzt
- [ ] `APP_DEBUG=false` gesetzt
- [ ] `APP_URL=https://sunnybill-test.chargedata.eu` gesetzt
- [ ] `SESSION_DOMAIN=.chargedata.eu` gesetzt
- [ ] Starkes `APP_KEY` generiert

### 2. Datenbank-Konfiguration
- [ ] Produktions-Datenbank erstellt
- [ ] Datenbankbenutzer mit minimalen Rechten erstellt
- [ ] Datenbankverbindung getestet
- [ ] Backup-Strategie implementiert

### 3. Mail-Konfiguration
- [ ] SMTP-Server konfiguriert
- [ ] Mail-Credentials eingetragen
- [ ] Test-E-Mail versendet

### 4. SSL/HTTPS
- [ ] SSL-Zertifikat für `sunnybill-test.chargedata.eu` installiert
- [ ] HTTPS-Weiterleitung konfiguriert
- [ ] SSL-Konfiguration getestet

## ✅ Deployment-Schritte

### 1. Server-Vorbereitung
- [ ] PHP 8.2+ installiert
- [ ] Composer installiert
- [ ] Node.js/NPM installiert
- [ ] Webserver (Apache/Nginx) konfiguriert
- [ ] MySQL/MariaDB installiert

### 2. Code-Deployment
- [ ] Repository geklont oder Code hochgeladen
- [ ] `composer install --no-dev --optimize-autoloader` ausgeführt
- [ ] `npm install && npm run build` ausgeführt
- [ ] `.env` Datei konfiguriert

### 3. Laravel-Setup
- [ ] `php artisan key:generate` ausgeführt
- [ ] `php artisan migrate --force` ausgeführt
- [ ] `php artisan storage:link` ausgeführt
- [ ] Cache optimiert (`config:cache`, `route:cache`, `view:cache`)

### 4. Berechtigungen
- [ ] `storage/` Verzeichnis beschreibbar (775)
- [ ] `bootstrap/cache/` Verzeichnis beschreibbar (775)
- [ ] Webserver-Benutzer als Eigentümer gesetzt
- [ ] `.env` Datei geschützt (600)

## ✅ Nach dem Deployment

### 1. Funktionstest
- [ ] Hauptseite erreichbar: `https://sunnybill-test.chargedata.eu`
- [ ] Admin-Login erreichbar: `https://sunnybill-test.chargedata.eu/admin/login`
- [ ] SSL-Zertifikat gültig
- [ ] Keine JavaScript-Fehler in der Konsole

### 2. Admin-Benutzer
- [ ] Ersten Admin-Benutzer erstellt:
```bash
php artisan tinker
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@sunnybill.de';
$user->password = bcrypt('SECURE_PASSWORD');
$user->save();
```
- [ ] Login mit Admin-Benutzer getestet
- [ ] Dashboard funktionsfähig

### 3. Sicherheitscheck
- [ ] `.env` Datei nicht öffentlich zugänglich
- [ ] `storage/` und `bootstrap/cache/` nicht öffentlich zugänglich
- [ ] Nur `public/` Verzeichnis vom Webserver erreichbar
- [ ] Debug-Modus deaktiviert
- [ ] Fehler-Logs konfiguriert

### 4. Performance-Optimierung
- [ ] OPcache aktiviert
- [ ] Gzip-Komprimierung aktiviert
- [ ] Browser-Caching konfiguriert
- [ ] Laravel-Caches optimiert

## ✅ Monitoring und Wartung

### 1. Log-Monitoring
- [ ] Laravel-Logs überwacht: `storage/logs/laravel.log`
- [ ] Webserver-Logs überwacht
- [ ] PHP-FPM-Logs überwacht
- [ ] Log-Rotation konfiguriert

### 2. Backup-Strategie
- [ ] Automatische Datenbank-Backups eingerichtet
- [ ] Datei-Backups konfiguriert
- [ ] Backup-Wiederherstellung getestet
- [ ] Backup-Aufbewahrungsrichtlinie definiert

### 3. Update-Prozess
- [ ] Staging-Umgebung eingerichtet
- [ ] Update-Prozedur dokumentiert
- [ ] Rollback-Strategie definiert
- [ ] Wartungsfenster geplant

## 🚨 Kritische Konfigurationen

### Webserver-Konfiguration (Apache)
```apache
<VirtualHost *:443>
    ServerName sunnybill-test.chargedata.eu
    DocumentRoot /var/www/sunnybill-test/public
    
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    
    <Directory /var/www/sunnybill-test/public>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Wichtige .env-Einstellungen
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://sunnybill-test.chargedata.eu
SESSION_DOMAIN=.chargedata.eu
LOG_LEVEL=error
```

### Datenbankbenutzer-Rechte
```sql
CREATE USER 'sunnybill_user'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, INDEX, DROP 
ON sunnybill_production.* TO 'sunnybill_user'@'localhost';
FLUSH PRIVILEGES;
```

## 📞 Support-Kontakte

- **Entwickler**: [Kontaktinformationen]
- **Server-Administrator**: [Kontaktinformationen]
- **Domain-Verwaltung**: [Kontaktinformationen]

## 🔗 Wichtige URLs

- **Anwendung**: https://sunnybill-test.chargedata.eu
- **Admin-Panel**: https://sunnybill-test.chargedata.eu/admin/login
- **Repository**: https://github.com/DHE-DEV/sunnybill-test

---

**Hinweis**: Diese Checkliste sollte bei jedem Deployment durchgegangen werden, um sicherzustellen, dass alle kritischen Aspekte berücksichtigt wurden.