#!/bin/bash

# SunnyBill Laravel Deployment Script für DigitalOcean
# Verwendung: ./deploy.sh [production|staging]

set -e

# Konfiguration
ENVIRONMENT=${1:-production}
REPO_URL="https://github.com/DHE-DEV/sunnybilltest.git"
APP_DIR="/var/www/sunnybill"
BACKUP_DIR="/var/backups/sunnybill"
DATE=$(date +%Y%m%d_%H%M%S)

# Farben für Output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Logging-Funktion
log() {
    echo -e "${GREEN}[$(date +'%Y-%m-%d %H:%M:%S')] $1${NC}"
}

error() {
    echo -e "${RED}[ERROR] $1${NC}"
    exit 1
}

warning() {
    echo -e "${YELLOW}[WARNING] $1${NC}"
}

# Prüfen ob Script als root oder mit sudo ausgeführt wird
if [[ $EUID -eq 0 ]]; then
    error "Dieses Script sollte nicht als root ausgeführt werden. Verwenden Sie sudo für einzelne Befehle."
fi

# Prüfen ob alle erforderlichen Tools installiert sind
check_requirements() {
    log "Prüfe Systemanforderungen..."
    
    command -v git >/dev/null 2>&1 || error "Git ist nicht installiert"
    command -v php >/dev/null 2>&1 || error "PHP ist nicht installiert"
    command -v composer >/dev/null 2>&1 || error "Composer ist nicht installiert"
    command -v nginx >/dev/null 2>&1 || error "Nginx ist nicht installiert"
    
    # PHP Version prüfen
    PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
    if [[ "$PHP_VERSION" != "8.3" ]]; then
        warning "PHP Version ist $PHP_VERSION, empfohlen ist 8.3"
    fi
    
    log "Alle Anforderungen erfüllt"
}

# Backup erstellen
create_backup() {
    log "Erstelle Backup..."
    
    sudo mkdir -p $BACKUP_DIR
    
    if [ -d "$APP_DIR" ]; then
        # Code-Backup
        sudo tar -czf $BACKUP_DIR/sunnybill_code_$DATE.tar.gz -C /var/www sunnybill
        
        # .env Backup
        if [ -f "$APP_DIR/.env" ]; then
            sudo cp $APP_DIR/.env $BACKUP_DIR/.env_$DATE
        fi
        
        log "Backup erstellt: $BACKUP_DIR/sunnybill_code_$DATE.tar.gz"
    else
        log "Kein vorhandenes Deployment gefunden, überspringe Backup"
    fi
}

# Anwendung herunterladen/aktualisieren
deploy_code() {
    log "Deploye Code..."
    
    if [ -d "$APP_DIR" ]; then
        # Existierendes Repository aktualisieren
        log "Aktualisiere existierendes Repository..."
        cd $APP_DIR
        sudo -u www-data git fetch origin
        sudo -u www-data git reset --hard origin/main
        sudo -u www-data git clean -fd
    else
        # Neues Repository klonen
        log "Klone Repository..."
        sudo mkdir -p /var/www
        cd /var/www
        sudo git clone $REPO_URL sunnybill
        sudo chown -R www-data:www-data $APP_DIR
    fi
}

# Dependencies installieren
install_dependencies() {
    log "Installiere Dependencies..."
    cd $APP_DIR
    
    # Composer Dependencies
    sudo -u www-data composer install --optimize-autoloader --no-dev --no-interaction
    
    # NPM Dependencies (falls vorhanden)
    if [ -f "package.json" ]; then
        if command -v npm >/dev/null 2>&1; then
            log "Installiere NPM Dependencies..."
            sudo -u www-data npm ci --production
            sudo -u www-data npm run build
        else
            warning "NPM nicht gefunden, überspringe Frontend-Build"
        fi
    fi
}

# Environment-Konfiguration
configure_environment() {
    log "Konfiguriere Environment..."
    cd $APP_DIR
    
    if [ ! -f ".env" ]; then
        if [ -f ".env.example" ]; then
            sudo -u www-data cp .env.example .env
            log "Bitte .env Datei konfigurieren: $APP_DIR/.env"
            warning "Deployment pausiert - konfigurieren Sie die .env Datei und führen Sie das Script erneut aus"
            exit 0
        else
            error ".env.example nicht gefunden"
        fi
    fi
    
    # Application Key generieren falls nicht vorhanden
    if ! grep -q "APP_KEY=base64:" .env; then
        log "Generiere Application Key..."
        sudo -u www-data php artisan key:generate --force
    fi
}

# Datenbank-Migrationen
run_migrations() {
    log "Führe Datenbank-Migrationen aus..."
    cd $APP_DIR
    
    # Prüfe Datenbankverbindung
    if sudo -u www-data php artisan migrate:status >/dev/null 2>&1; then
        # Führe Migrationen aus
        sudo -u www-data php artisan migrate --force
        log "Migrationen erfolgreich ausgeführt"
    else
        warning "Datenbankverbindung fehlgeschlagen - überspringe Migrationen"
        warning "Bitte prüfen Sie die Datenbank-Konfiguration in der .env Datei"
    fi
}

# Cache optimieren
optimize_cache() {
    log "Optimiere Cache..."
    cd $APP_DIR
    
    # Cache leeren
    sudo -u www-data php artisan config:clear
    sudo -u www-data php artisan cache:clear
    sudo -u www-data php artisan view:clear
    sudo -u www-data php artisan route:clear
    
    # Cache neu erstellen
    sudo -u www-data php artisan config:cache
    sudo -u www-data php artisan route:cache
    sudo -u www-data php artisan view:cache
    
    # OPcache leeren
    if command -v php-fpm8.3 >/dev/null 2>&1; then
        sudo systemctl reload php8.3-fpm
    fi
}

# Berechtigungen setzen
set_permissions() {
    log "Setze Berechtigungen..."
    
    # Basis-Berechtigungen
    sudo chown -R www-data:www-data $APP_DIR
    sudo chmod -R 755 $APP_DIR
    
    # Spezielle Berechtigungen für writable Verzeichnisse
    sudo chmod -R 775 $APP_DIR/storage
    sudo chmod -R 775 $APP_DIR/bootstrap/cache
    
    # Storage Link erstellen falls nicht vorhanden
    if [ ! -L "$APP_DIR/public/storage" ]; then
        sudo -u www-data php artisan storage:link
    fi
}

# Services neu starten
restart_services() {
    log "Starte Services neu..."
    
    # PHP-FPM neu starten
    sudo systemctl restart php8.3-fpm
    
    # Nginx neu laden
    sudo systemctl reload nginx
    
    # Queue Workers neu starten (falls vorhanden)
    if command -v supervisorctl >/dev/null 2>&1; then
        if sudo supervisorctl status | grep -q "sunnybill-worker"; then
            sudo supervisorctl restart sunnybill-worker:*
            log "Queue Workers neu gestartet"
        fi
    fi
}

# Health Check
health_check() {
    log "Führe Health Check durch..."
    
    # Prüfe ob PHP-FPM läuft
    if ! systemctl is-active --quiet php8.3-fpm; then
        error "PHP-FPM läuft nicht"
    fi
    
    # Prüfe ob Nginx läuft
    if ! systemctl is-active --quiet nginx; then
        error "Nginx läuft nicht"
    fi
    
    # Prüfe Laravel-Anwendung
    cd $APP_DIR
    if sudo -u www-data php artisan --version >/dev/null 2>&1; then
        log "Laravel-Anwendung ist funktionsfähig"
    else
        error "Laravel-Anwendung hat Probleme"
    fi
    
    # Prüfe Datenbankverbindung
    if sudo -u www-data php artisan migrate:status >/dev/null 2>&1; then
        log "Datenbankverbindung erfolgreich"
    else
        warning "Datenbankverbindung fehlgeschlagen"
    fi
}

# Cleanup alte Backups
cleanup_backups() {
    log "Bereinige alte Backups..."
    
    # Lösche Backups älter als 7 Tage
    sudo find $BACKUP_DIR -name "*.tar.gz" -mtime +7 -delete 2>/dev/null || true
    sudo find $BACKUP_DIR -name ".env_*" -mtime +7 -delete 2>/dev/null || true
    
    log "Backup-Bereinigung abgeschlossen"
}

# Deployment-Zusammenfassung
deployment_summary() {
    log "=== Deployment-Zusammenfassung ==="
    echo "Environment: $ENVIRONMENT"
    echo "Deployment-Zeit: $DATE"
    echo "Repository: $REPO_URL"
    echo "App-Verzeichnis: $APP_DIR"
    
    if [ -f "$APP_DIR/.env" ]; then
        APP_ENV=$(grep "APP_ENV=" $APP_DIR/.env | cut -d '=' -f2)
        APP_URL=$(grep "APP_URL=" $APP_DIR/.env | cut -d '=' -f2)
        echo "App Environment: $APP_ENV"
        echo "App URL: $APP_URL"
    fi
    
    # Git-Informationen
    if [ -d "$APP_DIR/.git" ]; then
        cd $APP_DIR
        CURRENT_COMMIT=$(git rev-parse --short HEAD)
        CURRENT_BRANCH=$(git rev-parse --abbrev-ref HEAD)
        echo "Git Branch: $CURRENT_BRANCH"
        echo "Git Commit: $CURRENT_COMMIT"
    fi
    
    log "=== Deployment erfolgreich abgeschlossen ==="
}

# Rollback-Funktion
rollback() {
    log "Führe Rollback durch..."
    
    # Finde letztes Backup
    LATEST_BACKUP=$(sudo find $BACKUP_DIR -name "sunnybill_code_*.tar.gz" -type f -printf '%T@ %p\n' | sort -n | tail -1 | cut -d' ' -f2-)
    
    if [ -z "$LATEST_BACKUP" ]; then
        error "Kein Backup für Rollback gefunden"
    fi
    
    log "Verwende Backup: $LATEST_BACKUP"
    
    # Aktuelles Deployment sichern
    if [ -d "$APP_DIR" ]; then
        sudo mv $APP_DIR ${APP_DIR}_failed_$DATE
    fi
    
    # Backup wiederherstellen
    sudo mkdir -p /var/www
    cd /var/www
    sudo tar -xzf $LATEST_BACKUP
    
    # Berechtigungen wiederherstellen
    set_permissions
    
    # Services neu starten
    restart_services
    
    log "Rollback abgeschlossen"
}

# Hauptfunktion
main() {
    log "Starte SunnyBill Deployment ($ENVIRONMENT)..."
    
    # Prüfe Parameter
    if [ "$1" = "rollback" ]; then
        rollback
        exit 0
    fi
    
    # Deployment-Schritte
    check_requirements
    create_backup
    deploy_code
    install_dependencies
    configure_environment
    run_migrations
    optimize_cache
    set_permissions
    restart_services
    health_check
    cleanup_backups
    deployment_summary
}

# Script-Hilfe
show_help() {
    echo "SunnyBill Laravel Deployment Script"
    echo ""
    echo "Verwendung:"
    echo "  $0 [production|staging]  - Normales Deployment"
    echo "  $0 rollback              - Rollback zum letzten Backup"
    echo "  $0 help                  - Diese Hilfe anzeigen"
    echo ""
    echo "Beispiele:"
    echo "  $0 production            - Production Deployment"
    echo "  $0 staging               - Staging Deployment"
    echo "  $0 rollback              - Rollback durchführen"
}

# Parameter verarbeiten
case "$1" in
    help|--help|-h)
        show_help
        exit 0
        ;;
    rollback)
        main rollback
        ;;
    production|staging|"")
        main
        ;;
    *)
        echo "Unbekannter Parameter: $1"
        show_help
        exit 1
        ;;
esac