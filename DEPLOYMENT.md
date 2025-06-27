# SunnyBill Deployment-Anleitung

## Produktionsumgebung: https://sunnybill-test.chargedata.eu

### 1. Umgebungskonfiguration

#### .env-Datei für Produktion
Die `.env.production`-Datei enthält die Produktionskonfiguration. Folgende Werte müssen vor dem Deployment angepasst werden:

**Kritische Änderungen:**
- `APP_ENV=production` (statt `local`)
- `APP_DEBUG=false` (statt `true`)
- `APP_URL=https://sunnybill-test.chargedata.eu` (statt `http://sunnybill.test`)
- `LOG_LEVEL=error` (statt `debug`)
- `SESSION_DOMAIN=.chargedata.eu` (für Cookie-Sharing)

**Datenbank-Konfiguration (anzupassen):**
```env
DB_HOST=localhost
DB_DATABASE=sunnybill_production
DB_USERNAME=sunnybill_user
DB_PASSWORD=SECURE_PASSWORD_HERE
```

**Mail-Konfiguration (anzupassen):**
```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=noreply@sunnybill.de
MAIL_PASSWORD=MAIL_PASSWORD_HERE
```

### 2. Deployment-Schritte

#### Schritt 1: Code-Upload
```bash
# Repository klonen oder Code hochladen
git clone https://github.com/DHE-DEV/sunnybill-test.git
cd sunnybill-test
```

#### Schritt 2: Abhängigkeiten installieren
```bash
# Composer-Abhängigkeiten installieren
composer install --no-dev --optimize-autoloader

# NPM-Abhängigkeiten installieren und Assets kompilieren
npm install
npm run build
```

#### Schritt 3: Umgebungskonfiguration
```bash
# .env-Datei kopieren und anpassen
cp .env.production .env

# Anwendungsschlüssel generieren (falls noch nicht vorhanden)
php artisan key:generate

# Konfiguration cachen
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

#### Schritt 4: Datenbank-Setup
```bash
# Migrationen ausführen
php artisan migrate --force

# Optional: Seeder ausführen (falls vorhanden)
php artisan db:seed --force
```

#### Schritt 5: Berechtigungen setzen
```bash
# Storage- und Cache-Verzeichnisse beschreibbar machen
chmod -R 775 storage
chmod -R 775 bootstrap/cache

# Webserver-Benutzer als Eigentümer setzen (je nach Server)
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

#### Schritt 6: Symbolic Links
```bash
# Storage-Link erstellen
php artisan storage:link
```

### 3. Webserver-Konfiguration

#### Apache Virtual Host
```apache
<VirtualHost *:443>
    ServerName sunnybill-test.chargedata.eu
    DocumentRoot /path/to/sunnybill-test/public
    
    SSLEngine on
    SSLCertificateFile /path/to/ssl/certificate.crt
    SSLCertificateKeyFile /path/to/ssl/private.key
    
    <Directory /path/to/sunnybill-test/public>
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/sunnybill_error.log
    CustomLog ${APACHE_LOG_DIR}/sunnybill_access.log combined
</VirtualHost>
```

#### Nginx-Konfiguration
```nginx
server {
    listen 443 ssl;
    server_name sunnybill-test.chargedata.eu;
    root /path/to/sunnybill-test/public;
    
    ssl_certificate /path/to/ssl/certificate.crt;
    ssl_certificate_key /path/to/ssl/private.key;
    
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    
    index index.php;
    
    charset utf-8;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }
    
    error_page 404 /index.php;
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. Sicherheitsüberprüfungen

#### Dateiberechtigungen
- `storage/` und `bootstrap/cache/` müssen beschreibbar sein
- `.env`-Datei sollte nicht öffentlich zugänglich sein
- Nur `public/`-Verzeichnis sollte vom Webserver erreichbar sein

#### SSL/HTTPS
- SSL-Zertifikat für `sunnybill-test.chargedata.eu` erforderlich
- HTTP-zu-HTTPS-Weiterleitung einrichten

#### Datenbank-Sicherheit
- Separaten Datenbankbenutzer mit minimalen Rechten erstellen
- Starkes Passwort verwenden
- Datenbankzugriff nur von localhost erlauben

### 5. Nach dem Deployment

#### Admin-Benutzer erstellen
```bash
# Ersten Admin-Benutzer über Tinker erstellen
php artisan tinker

# In Tinker:
$user = new App\Models\User();
$user->name = 'Admin';
$user->email = 'admin@sunnybill.de';
$user->password = bcrypt('secure_password_here');
$user->save();
```

#### Funktionstest
1. `https://sunnybill-test.chargedata.eu` aufrufen
2. `https://sunnybill-test.chargedata.eu/admin/login` aufrufen
3. Mit erstelltem Admin-Benutzer einloggen
4. Dashboard und Funktionen testen

### 6. Wartung und Updates

#### Cache leeren
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### Updates deployen
```bash
# Code aktualisieren
git pull origin main

# Abhängigkeiten aktualisieren
composer install --no-dev --optimize-autoloader

# Assets neu kompilieren
npm run build

# Migrationen ausführen
php artisan migrate --force

# Cache neu erstellen
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Monitoring und Logs

#### Log-Dateien überwachen
- `storage/logs/laravel.log`
- Webserver-Logs (Apache/Nginx)
- PHP-FPM-Logs

#### Performance-Optimierung
- OPcache aktivieren
- Redis für Session/Cache verwenden (optional)
- CDN für statische Assets (optional)

## Wichtige Hinweise

1. **Backup**: Vor jedem Update Datenbank und Dateien sichern
2. **Wartungsmodus**: Bei Updates `php artisan down` verwenden
3. **Monitoring**: Log-Dateien regelmäßig überwachen
4. **Sicherheit**: Regelmäßige Updates von Laravel und Abhängigkeiten