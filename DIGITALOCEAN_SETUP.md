# DigitalOcean Droplet Setup für SunnyBill Laravel Application

## Übersicht
Diese Anleitung beschreibt die Einrichtung eines Ubuntu 24.10 Droplets auf DigitalOcean für das Hosting der SunnyBill Laravel-Anwendung mit PHP 8.3, Nginx und Let's Encrypt SSL.

## Voraussetzungen
- DigitalOcean Droplet mit Ubuntu 24.10
- A-Record bereits bei DNS-Provider erstellt
- DigitalOcean Managed Database verfügbar
- SSH-Zugang zum Droplet

## 1. Erste Schritte - Server-Update

```bash
# Als root oder mit sudo
sudo apt update && sudo apt upgrade -y
sudo apt install -y curl wget git unzip software-properties-common
```

## 2. PHP 8.3 Installation

```bash
# PHP 8.3 Repository hinzufügen
sudo add-apt-repository ppa:ondrej/php -y
sudo apt update

# PHP 8.3 und erforderliche Extensions installieren
sudo apt install -y php8.3 php8.3-fpm php8.3-cli php8.3-common php8.3-mysql \
php8.3-zip php8.3-gd php8.3-mbstring php8.3-curl php8.3-xml php8.3-bcmath \
php8.3-json php8.3-tokenizer php8.3-fileinfo php8.3-intl php8.3-redis \
php8.3-imagick php8.3-soap php8.3-dom php8.3-simplexml

# PHP-FPM starten und aktivieren
sudo systemctl start php8.3-fpm
sudo systemctl enable php8.3-fpm
```

## 3. Composer Installation

```bash
# Composer herunterladen und installieren
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
sudo chmod +x /usr/local/bin/composer
```

## 4. Nginx Installation und Konfiguration

```bash
# Nginx installieren
sudo apt install -y nginx

# Nginx starten und aktivieren
sudo systemctl start nginx
sudo systemctl enable nginx
```

### Nginx Virtual Host Konfiguration

```bash
# Backup der Standard-Konfiguration
sudo cp /etc/nginx/sites-available/default /etc/nginx/sites-available/default.backup

# Neue Site-Konfiguration erstellen (ersetzen Sie IHRE_DOMAIN.com)
sudo nano /etc/nginx/sites-available/sunnybill
```

**Nginx Konfiguration für sunnybill:**

```nginx
server {
    listen 80;
    server_name IHRE_DOMAIN.com www.IHRE_DOMAIN.com;
    root /var/www/sunnybill/public;
    index index.php index.html index.htm;

    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;

    # Laravel specific configuration
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }

    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_proxied expired no-cache no-store private must-revalidate auth;
    gzip_types text/plain text/css text/xml text/javascript application/x-javascript application/xml+rss;

    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
```

```bash
# Site aktivieren
sudo ln -s /etc/nginx/sites-available/sunnybill /etc/nginx/sites-enabled/
sudo rm /etc/nginx/sites-enabled/default

# Nginx Konfiguration testen
sudo nginx -t

# Nginx neu starten
sudo systemctl restart nginx
```

## 5. Firewall Konfiguration

```bash
# UFW Firewall aktivieren
sudo ufw enable

# HTTP, HTTPS und SSH erlauben
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443

# Status prüfen
sudo ufw status
```

## 6. MySQL Client Installation (für DigitalOcean Database)

```bash
# MySQL Client installieren
sudo apt install -y mysql-client-8.0

# Verbindung zur DigitalOcean Database testen
mysql -h db-cdas-live-fra1-88967-do-user-6994854-0.b.db.ondigitalocean.com -P 25060 -u DATENBANKBENUTZER -p
```

## 7. Laravel Anwendung deployen

```bash
# Webroot-Verzeichnis erstellen
sudo mkdir -p /var/www
cd /var/www

# Repository klonen
sudo git clone https://github.com/DHE-DEV/sunnybilltest.git sunnybill
cd sunnybill

# Berechtigungen setzen
sudo chown -R www-data:www-data /var/www/sunnybill
sudo chmod -R 755 /var/www/sunnybill
sudo chmod -R 775 /var/www/sunnybill/storage
sudo chmod -R 775 /var/www/sunnybill/bootstrap/cache
```

### Environment-Konfiguration

```bash
# .env Datei erstellen
sudo cp .env.example .env
sudo nano .env
```

**Wichtige .env Einstellungen:**

```env
APP_NAME=SunnyBill
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://IHRE_DOMAIN.com

LOG_CHANNEL=stack
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=db-cdas-live-fra1-88967-do-user-6994854-0.b.db.ondigitalocean.com
DB_PORT=25060
DB_DATABASE=IHRE_DATENBANK
DB_USERNAME=IHR_BENUTZER
DB_PASSWORD=IHR_PASSWORT

BROADCAST_DRIVER=log
CACHE_DRIVER=file
FILESYSTEM_DISK=local
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
SESSION_LIFETIME=120

MEMCACHED_HOST=127.0.0.1

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Laravel Setup abschließen

```bash
# Als www-data Benutzer arbeiten
sudo -u www-data composer install --optimize-autoloader --no-dev

# Application Key generieren
sudo -u www-data php artisan key:generate

# Cache leeren und optimieren
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# Datenbank-Migrationen ausführen (falls erforderlich)
sudo -u www-data php artisan migrate --force

# Storage Link erstellen
sudo -u www-data php artisan storage:link
```

## 8. Let's Encrypt SSL-Zertifikat installieren

```bash
# Certbot installieren
sudo apt install -y certbot python3-certbot-nginx

# SSL-Zertifikat erstellen (ersetzen Sie IHRE_DOMAIN.com)
sudo certbot --nginx -d IHRE_DOMAIN.com -d www.IHRE_DOMAIN.com

# Automatische Erneuerung testen
sudo certbot renew --dry-run
```

## 9. PHP-FPM Optimierung

```bash
# PHP-FPM Pool-Konfiguration bearbeiten
sudo nano /etc/php/8.3/fpm/pool.d/www.conf
```

**Empfohlene Einstellungen für Production:**

```ini
; Process manager settings
pm = dynamic
pm.max_children = 50
pm.start_servers = 5
pm.min_spare_servers = 5
pm.max_spare_servers = 35
pm.max_requests = 500

; Security
security.limit_extensions = .php
```

```bash
# PHP.ini für Production optimieren
sudo nano /etc/php/8.3/fpm/php.ini
```

**Wichtige PHP.ini Einstellungen:**

```ini
; Basic settings
memory_limit = 256M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M

; Security
expose_php = Off
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log

; Session
session.cookie_httponly = 1
session.cookie_secure = 1
session.use_strict_mode = 1

; OPcache
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
opcache.revalidate_freq = 60
opcache.fast_shutdown = 1
```

```bash
# PHP-FPM neu starten
sudo systemctl restart php8.3-fpm
```

## 10. Monitoring und Logs

```bash
# Log-Verzeichnisse erstellen
sudo mkdir -p /var/log/nginx
sudo mkdir -p /var/log/laravel

# Laravel Logs konfigurieren
sudo chown -R www-data:www-data /var/www/sunnybill/storage/logs
```

### Nginx Logs erweiterte Konfiguration

```bash
# Nginx Konfiguration für erweiterte Logs
sudo nano /etc/nginx/sites-available/sunnybill
```

**Log-Konfiguration hinzufügen:**

```nginx
# Logs
access_log /var/log/nginx/sunnybill_access.log;
error_log /var/log/nginx/sunnybill_error.log;
```

## 11. Backup-Script erstellen

```bash
# Backup-Script erstellen
sudo nano /usr/local/bin/sunnybill-backup.sh
```

**Backup-Script:**

```bash
#!/bin/bash
DATE=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/var/backups/sunnybill"
APP_DIR="/var/www/sunnybill"

# Backup-Verzeichnis erstellen
mkdir -p $BACKUP_DIR

# Code-Backup
tar -czf $BACKUP_DIR/sunnybill_code_$DATE.tar.gz -C /var/www sunnybill

# Uploads-Backup
tar -czf $BACKUP_DIR/sunnybill_storage_$DATE.tar.gz -C $APP_DIR/storage app

# Alte Backups löschen (älter als 7 Tage)
find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete

echo "Backup completed: $DATE"
```

```bash
# Script ausführbar machen
sudo chmod +x /usr/local/bin/sunnybill-backup.sh

# Cron-Job für tägliche Backups
sudo crontab -e
# Folgende Zeile hinzufügen:
# 0 2 * * * /usr/local/bin/sunnybill-backup.sh
```

## 12. Performance-Optimierung

### Redis Installation (optional, für Caching)

```bash
# Redis installieren
sudo apt install -y redis-server

# Redis konfigurieren
sudo nano /etc/redis/redis.conf
# Uncomment: maxmemory 256mb
# Uncomment: maxmemory-policy allkeys-lru

# Redis starten
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

### Laravel Queue Worker (optional)

```bash
# Supervisor installieren
sudo apt install -y supervisor

# Queue Worker Konfiguration
sudo nano /etc/supervisor/conf.d/sunnybill-worker.conf
```

**Supervisor Konfiguration:**

```ini
[program:sunnybill-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/sunnybill/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/sunnybill/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
# Supervisor neu laden
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start sunnybill-worker:*
```

## 13. Sicherheits-Checkliste

- [ ] Firewall konfiguriert (nur Port 22, 80, 443 offen)
- [ ] SSH Key-basierte Authentifizierung aktiviert
- [ ] Root-Login deaktiviert
- [ ] Fail2ban installiert
- [ ] SSL-Zertifikat installiert und automatische Erneuerung konfiguriert
- [ ] PHP expose_php deaktiviert
- [ ] Nginx Server-Token versteckt
- [ ] Regelmäßige Updates geplant
- [ ] Backup-System eingerichtet

### Fail2ban Installation

```bash
# Fail2ban installieren
sudo apt install -y fail2ban

# Konfiguration erstellen
sudo cp /etc/fail2ban/jail.conf /etc/fail2ban/jail.local
sudo nano /etc/fail2ban/jail.local
```

## 14. Deployment-Workflow

Für zukünftige Updates:

```bash
# 1. Code aktualisieren
cd /var/www/sunnybill
sudo -u www-data git pull origin main

# 2. Dependencies aktualisieren
sudo -u www-data composer install --optimize-autoloader --no-dev

# 3. Cache leeren
sudo -u www-data php artisan config:clear
sudo -u www-data php artisan cache:clear
sudo -u www-data php artisan view:clear

# 4. Cache neu erstellen
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 5. Migrationen ausführen (falls vorhanden)
sudo -u www-data php artisan migrate --force

# 6. PHP-FPM neu starten
sudo systemctl restart php8.3-fpm
```

## Troubleshooting

### Häufige Probleme und Lösungen

1. **Permission-Probleme:**
   ```bash
   sudo chown -R www-data:www-data /var/www/sunnybill
   sudo chmod -R 755 /var/www/sunnybill
   sudo chmod -R 775 /var/www/sunnybill/storage
   sudo chmod -R 775 /var/www/sunnybill/bootstrap/cache
   ```

2. **Nginx 502 Bad Gateway:**
   ```bash
   sudo systemctl status php8.3-fpm
   sudo systemctl restart php8.3-fpm
   sudo systemctl restart nginx
   ```

3. **SSL-Probleme:**
   ```bash
   sudo certbot certificates
   sudo certbot renew
   ```

4. **Database Connection Issues:**
   ```bash
   # Verbindung testen
   mysql -h db-cdas-live-fra1-88967-do-user-6994854-0.b.db.ondigitalocean.com -P 25060 -u USERNAME -p
   ```

## Monitoring-Commands

```bash
# System-Status prüfen
sudo systemctl status nginx
sudo systemctl status php8.3-fpm
sudo systemctl status mysql

# Logs überwachen
sudo tail -f /var/log/nginx/sunnybill_error.log
sudo tail -f /var/www/sunnybill/storage/logs/laravel.log

# Disk-Space prüfen
df -h

# Memory-Usage prüfen
free -h

# Process-Monitoring
htop
```

Diese Anleitung bietet eine vollständige Einrichtung für Ihr SunnyBill Laravel-Projekt auf DigitalOcean mit allen erforderlichen Komponenten für eine Production-Umgebung.