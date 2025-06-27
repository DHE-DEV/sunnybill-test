#!/bin/bash

# SunnyBill Deployment Script
# Verwendung: ./deploy.sh [production|staging]

set -e

ENVIRONMENT=${1:-production}
APP_DIR="/var/www/sunnybill-test"
BACKUP_DIR="/var/backups/sunnybill"
DATE=$(date +%Y%m%d_%H%M%S)

echo "🚀 Starting SunnyBill deployment for $ENVIRONMENT environment..."

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Funktionen
log_info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

log_warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Prüfe ob Benutzer root ist oder sudo hat
if [[ $EUID -eq 0 ]]; then
    SUDO=""
else
    SUDO="sudo"
fi

# 1. Backup erstellen
log_info "Creating backup..."
$SUDO mkdir -p $BACKUP_DIR
if [ -d "$APP_DIR" ]; then
    $SUDO tar -czf "$BACKUP_DIR/sunnybill_backup_$DATE.tar.gz" -C "$APP_DIR" .
    log_info "Backup created: $BACKUP_DIR/sunnybill_backup_$DATE.tar.gz"
fi

# 2. Wartungsmodus aktivieren (falls App bereits existiert)
if [ -f "$APP_DIR/artisan" ]; then
    log_info "Enabling maintenance mode..."
    cd $APP_DIR
    $SUDO php artisan down --message="Deployment in progress" --retry=60
fi

# 3. Code aktualisieren
log_info "Updating code..."
if [ -d "$APP_DIR/.git" ]; then
    cd $APP_DIR
    $SUDO git fetch origin
    $SUDO git reset --hard origin/main
else
    log_warn "No git repository found. Please upload code manually."
fi

# 4. Abhängigkeiten installieren
log_info "Installing dependencies..."
cd $APP_DIR
$SUDO composer install --no-dev --optimize-autoloader --no-interaction

# 5. NPM Build (falls package.json existiert)
if [ -f "$APP_DIR/package.json" ]; then
    log_info "Building frontend assets..."
    npm ci --production
    npm run build
fi

# 6. Umgebungskonfiguration
log_info "Setting up environment..."
if [ ! -f "$APP_DIR/.env" ]; then
    if [ -f "$APP_DIR/.env.$ENVIRONMENT" ]; then
        $SUDO cp "$APP_DIR/.env.$ENVIRONMENT" "$APP_DIR/.env"
        log_info "Copied .env.$ENVIRONMENT to .env"
    else
        log_error ".env file not found! Please create .env file."
        exit 1
    fi
fi

# 7. Anwendungsschlüssel generieren (falls nicht vorhanden)
if ! grep -q "APP_KEY=base64:" "$APP_DIR/.env"; then
    log_info "Generating application key..."
    $SUDO php artisan key:generate --force
fi

# 8. Berechtigungen setzen
log_info "Setting permissions..."
$SUDO chown -R www-data:www-data $APP_DIR
$SUDO chmod -R 755 $APP_DIR
$SUDO chmod -R 775 $APP_DIR/storage
$SUDO chmod -R 775 $APP_DIR/bootstrap/cache

# 9. Storage Link erstellen
log_info "Creating storage link..."
$SUDO php artisan storage:link

# 10. Datenbank-Migrationen
log_info "Running database migrations..."
$SUDO php artisan migrate --force

# 11. Cache optimieren
log_info "Optimizing cache..."
$SUDO php artisan config:cache
$SUDO php artisan route:cache
$SUDO php artisan view:cache

# 12. Wartungsmodus deaktivieren
log_info "Disabling maintenance mode..."
$SUDO php artisan up

# 13. Gesundheitscheck
log_info "Performing health check..."
HTTP_STATUS=$(curl -s -o /dev/null -w "%{http_code}" https://sunnybill-test.chargedata.eu)
if [ "$HTTP_STATUS" = "200" ]; then
    log_info "✅ Health check passed! Application is running."
else
    log_error "❌ Health check failed! HTTP Status: $HTTP_STATUS"
    exit 1
fi

# 14. Alte Backups aufräumen (behalte nur die letzten 5)
log_info "Cleaning up old backups..."
$SUDO find $BACKUP_DIR -name "sunnybill_backup_*.tar.gz" -type f -mtime +7 -delete

log_info "🎉 Deployment completed successfully!"
log_info "Application URL: https://sunnybill-test.chargedata.eu"
log_info "Admin URL: https://sunnybill-test.chargedata.eu/admin/login"

# Deployment-Zusammenfassung
echo ""
echo "📋 Deployment Summary:"
echo "- Environment: $ENVIRONMENT"
echo "- Backup: $BACKUP_DIR/sunnybill_backup_$DATE.tar.gz"
echo "- Application: https://sunnybill-test.chargedata.eu"
echo "- Admin Panel: https://sunnybill-test.chargedata.eu/admin/login"
echo ""
echo "🔧 Next steps:"
echo "1. Test the application functionality"
echo "2. Create admin user if needed: php artisan tinker"
echo "3. Monitor logs: tail -f $APP_DIR/storage/logs/laravel.log"