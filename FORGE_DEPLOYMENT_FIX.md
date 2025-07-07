# Laravel Forge Deployment Fix für VoltMaster

## Problem
Das Forge Deployment-Script schlägt fehl mit:
```
fatal: Need to specify how to reconcile divergent branches.
```

## Lösung für Laravel Forge

### Option 1: Forge Deployment Script anpassen (Empfohlen)

Ersetzen Sie in Ihrem Forge Deployment Script diese Zeile:
```bash
git pull origin $FORGE_SITE_BRANCH
```

Mit:
```bash
# Git für Merge-Strategie konfigurieren
git config pull.rebase false

# Pull mit expliziter Merge-Strategie
git pull origin $FORGE_SITE_BRANCH --no-rebase
```

### Option 2: Manueller Fix auf dem Server

SSH in Ihren Server und führen Sie folgende Befehle aus:

```bash
# Wechseln Sie in das Projektverzeichnis
cd /home/forge/sunnybill-test.chargedata.eu

# Git für Merge-Strategie konfigurieren
git config pull.rebase false

# Harter Reset auf den neuesten Stand (sicher, da Forge immer deployed)
git fetch origin
git reset --hard origin/main

# Laravel Caches leeren
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

### Option 3: Komplettes Forge Deployment Script

Hier ist das vollständige, aktualisierte Deployment Script für Forge:

```bash
cd /home/forge/sunnybill-test.chargedata.eu

# Git für Merge-Strategie konfigurieren (einmalig)
git config pull.rebase false

# Sicherer Pull mit Fallback
git pull origin $FORGE_SITE_BRANCH --no-rebase || {
    echo "Pull failed, performing hard reset..."
    git fetch origin
    git reset --hard origin/$FORGE_SITE_BRANCH
}

$FORGE_COMPOSER install --no-dev --no-interaction --prefer-dist --optimize-autoloader

# Laravel Caches leeren für neue Features
if [ -f artisan ]; then
    $FORGE_PHP artisan config:clear
    $FORGE_PHP artisan cache:clear
    $FORGE_PHP artisan view:clear
fi

# Prevent concurrent php-fpm reloads
touch /tmp/fpmlock 2>/dev/null || true
( flock -w 10 9 || exit 1
    echo 'Reloading PHP FPM...'; sudo -S service $FORGE_PHP_FPM reload ) 9</tmp/fpmlock

if [ -f artisan ]; then
    $FORGE_PHP artisan migrate --force
    
    # Production Optimierungen
    $FORGE_PHP artisan config:cache
    $FORGE_PHP artisan route:cache
    $FORGE_PHP artisan view:cache
fi
```

## Sofortige Lösung (Jetzt ausführen)

1. **SSH in Ihren Forge Server:**
   ```bash
   ssh forge@sunnybill-test.chargedata.eu
   ```

2. **Führen Sie diese Befehle aus:**
   ```bash
   cd /home/forge/sunnybill-test.chargedata.eu
   git config pull.rebase false
   git fetch origin
   git reset --hard origin/main
   php artisan config:clear
   php artisan cache:clear
   php artisan view:clear
   ```

3. **Testen Sie das Deployment erneut in Forge**

## Was wurde aktualisiert

Nach dem erfolgreichen Deployment sehen Sie:

✅ **Erdkugel-Animation verlangsamt** (50% langsamer, ruhiger)
✅ **Arc-Farben zu hellem Gelb** (#ffd700 statt verschiedene Farben)
✅ **Technology Overview Section** (neue graue Section mit 6 Karten)
✅ **Features Section** (weiße Überschrift auf dunklem Hintergrund)
✅ **FontAwesome-Icons** (rote professionelle Icons statt Emojis)
✅ **Zentrierte Feature-Karten** (bessere Lesbarkeit)

## Forge Dashboard Einstellungen

In Ihrem Forge Dashboard können Sie auch:
1. Gehen Sie zu Ihrer Site
2. Klicken Sie auf "Deployment Script"
3. Fügen Sie `git config pull.rebase false` vor dem `git pull` Befehl hinzu

## Verifikation

Nach dem Deployment besuchen Sie Ihre Website und prüfen Sie:
- Die Erdkugel rotiert langsamer
- Gelbe Verbindungslinien auf der Erdkugel
- Neue "Modernste Technologie" Section
- Weiße "Kernfunktionen" Überschrift
- Rote FontAwesome Icons in den Feature-Karten
