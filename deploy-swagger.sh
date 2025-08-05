#!/bin/bash

# Deployment Script für Swagger API Dokumentation auf Live-Server
# Dieses Script sollte auf dem Live-Server ausgeführt werden

echo "🚀 Deploying Swagger API Documentation..."

# 1. Cache leeren
echo "📝 Clearing caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 2. Konfiguration neu laden
echo "⚙️ Reloading configuration..."
php artisan config:cache

# 3. Alte API-Docs löschen
echo "🗑️ Removing old API documentation..."
rm -rf storage/api-docs/*
rm -rf public/docs/*

# 4. Neue API-Docs generieren
echo "📚 Generating new API documentation..."
php artisan l5-swagger:generate

# 5. Berechtigungen setzen
echo "🔐 Setting permissions..."
chmod -R 755 storage/api-docs/
chmod -R 755 public/docs/

# 6. Webserver neu laden (falls nginx)
if command -v nginx &> /dev/null; then
    echo "🔄 Reloading nginx..."
    sudo nginx -s reload
fi

echo "✅ Swagger API Documentation deployment completed!"
echo "📖 Documentation should now be available at: https://prosoltec.voltmaster.cloud/api/documentation"