# SunnyBill Produktions-Setup Checkliste

## 🚨 Kritische Schritte für Live-Freischaltung

### 1. Server-Abhängigkeiten installieren
```bash
# Auf dem Produktionsserver ausführen:
cd /path/to/sunnybill-production
composer install --no-dev --optimize-autoloader
```

### 2. Umgebungskonfiguration
```bash
# .env-Datei auf dem Produktionsserver erstellen/anpassen:
cp .env.production .env

# Wichtige Werte anpassen:
APP_KEY=                    # Generieren mit: php artisan key:generate
DB_HOST=                    # Produktions-Datenbankserver
DB_DATABASE=                # Produktions-Datenbankname
DB_USERNAME=                # Produktions-DB-Benutzer
DB_PASSWORD=                # Produktions-DB-Passwort
MAIL_HOST=                  # SMTP-Server für E-Mails
MAIL_USERNAME=              # SMTP-Benutzername
MAIL_PASSWORD=              # SMTP-Passwort
```

### 3. Laravel-Konfiguration
```bash
# Application Key generieren:
php artisan key:generate

# Konfiguration cachen:
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Storage-Link erstellen:
php artisan storage:link
```

### 4. Datenbank-Setup
```bash
# Migrationen ausführen:
php artisan migrate --force

# Admin-Benutzer wird automatisch erstellt durch Migration:
# Email: admin@example.com
# Passwort: admin123
```

### 5. Benutzer-Berechtigung konfiguriert
Die `canAccessPanel()` Methode im User-Model erlaubt Zugang für:
- **Lokal (http://sunnybill-test.test/)**: Alle Benutzer
- **Live (https://sunnybill-test.chargedata.eu/)**:
  - admin@example.com (automatisch erstellt)
  - Benutzer mit @yourdomain.com
  - Benutzer mit @chargedata.eu

### 6. Dateiberechtigungen setzen
```bash
# Webserver-Benutzer (meist www-data oder apache):
sudo chown -R www-data:www-data /path/to/sunnybill-production
sudo chmod -R 755 /path/to/sunnybill-production
sudo chmod -R 775 /path/to/sunnybill-production/storage
sudo chmod -R 775 /path/to/sunnybill-production/bootstrap/cache
```

### 7. Webserver-Konfiguration (Apache)
```apache
<VirtualHost *:443>
    ServerName sunnybill-test.chargedata.eu
    DocumentRoot /path/to/sunnybill-production/public
    
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    
    <Directory /path/to/sunnybill-production/public>
        AllowOverride All
        Require all granted
        DirectoryIndex index.php
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sunnybill_error.log
    CustomLog ${APACHE_LOG_DIR}/sunnybill_access.log combined
</VirtualHost>
```

### 8. Webserver-Konfiguration (Nginx)
```nginx
server {
    listen 443 ssl;
    server_name sunnybill-test.chargedata.eu;
    root /path/to/sunnybill-production/public;
    index index.php;
    
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

## 🔍 Debugging-Schritte bei 403 Fehler

### 1. Laravel-Logs prüfen
```bash
tail -f /path/to/sunnybill-production/storage/logs/laravel.log
```

### 2. Webserver-Logs prüfen
```bash
# Apache:
tail -f /var/log/apache2/sunnybill_error.log

# Nginx:
tail -f /var/log/nginx/error.log
```

### 3. Dateiberechtigungen prüfen
```bash
ls -la /path/to/sunnybill-production/
ls -la /path/to/sunnybill-production/storage/
ls -la /path/to/sunnybill-production/bootstrap/cache/
```

### 4. PHP-Konfiguration prüfen
```bash
php -v
php -m | grep -E "(pdo|mysql|mbstring|openssl|tokenizer|xml|ctype|json|bcmath|fileinfo)"
```

### 5. Artisan-Befehle testen
```bash
cd /path/to/sunnybill-production
php artisan --version
php artisan route:list
php artisan config:show app
```

## ✅ Verifikation nach Setup

1. **Startseite testen**: https://sunnybill-test.chargedata.eu/
2. **Admin-Login testen**: https://sunnybill-test.chargedata.eu/admin
3. **Login-Daten**: admin@example.com / admin123
4. **SSL-Zertifikat prüfen**: Browser sollte grünes Schloss zeigen
5. **Performance testen**: Ladezeiten unter 2 Sekunden

## 🚨 Häufige Probleme und Lösungen

### Problem: 403 Forbidden
- **Ursache**: Falsche Dateiberechtigungen oder Webserver-Konfiguration
- **Lösung**: Berechtigungen setzen und Virtual Host konfigurieren

### Problem: 500 Internal Server Error
- **Ursache**: Laravel-Konfigurationsfehler oder fehlende .env
- **Lösung**: Logs prüfen, .env konfigurieren, Caches leeren

### Problem: "Class not found" Fehler
- **Ursache**: Composer-Abhängigkeiten nicht installiert
- **Lösung**: `composer install --no-dev --optimize-autoloader`

### Problem: Datenbankverbindung fehlgeschlagen
- **Ursache**: Falsche DB-Konfiguration in .env
- **Lösung**: DB-Verbindungsdaten in .env prüfen und korrigieren

## 📞 Support-Kontakt
Bei weiteren Problemen Laravel-Logs und Webserver-Logs bereitstellen.