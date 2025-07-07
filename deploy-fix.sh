#!/bin/bash

# VoltMaster Deployment Fix Script
# Löst das Problem mit divergierenden Branches auf dem Live-Server

echo "🚀 VoltMaster Deployment Fix wird gestartet..."

# Backup des aktuellen Zustands
echo "📦 Erstelle Backup des aktuellen Zustands..."
git stash push -m "Backup vor Deployment Fix $(date)"

# Fetch neueste Änderungen
echo "📥 Lade neueste Änderungen von GitHub..."
git fetch origin

# Reset auf den neuesten main Branch (forciert)
echo "🔄 Setze lokalen Branch auf neuesten main zurück..."
git reset --hard origin/main

# Konfiguriere Git für zukünftige Pulls
echo "⚙️ Konfiguriere Git für automatische Merge-Strategie..."
git config pull.rebase false

# Stelle sicher, dass wir auf dem main Branch sind
echo "🌿 Wechsle zu main Branch..."
git checkout main

# Pull mit Merge-Strategie
echo "⬇️ Führe sicheren Pull durch..."
git pull origin main --no-rebase

# Composer Dependencies aktualisieren
echo "📚 Aktualisiere Composer Dependencies..."
composer install --no-dev --optimize-autoloader

# Laravel Caches leeren
echo "🧹 Leere Laravel Caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Storage Link erstellen (falls nicht vorhanden)
echo "🔗 Erstelle Storage Link..."
php artisan storage:link

# Optimierungen für Production
echo "⚡ Führe Production-Optimierungen durch..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Deployment Fix erfolgreich abgeschlossen!"
echo "🌐 Die VoltMaster Welcome-Seite sollte jetzt mit allen neuen Features verfügbar sein."
echo ""
echo "📋 Durchgeführte Änderungen:"
echo "   - Erdkugel-Animation verlangsamt"
echo "   - Arc-Farben zu hellem Gelb geändert"
echo "   - Technology Overview Section hinzugefügt"
echo "   - Features Section mit weißem Text und dunklem Hintergrund"
echo "   - FontAwesome-Icons in rot implementiert"
echo "   - Zentrierte Feature-Karten"
echo ""
echo "🔧 Bei weiteren Problemen führen Sie folgende Befehle auf dem Server aus:"
echo "   git config pull.rebase false"
echo "   git pull origin main --no-rebase"
