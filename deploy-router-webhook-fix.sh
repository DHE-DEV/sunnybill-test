#!/bin/bash

# Router Webhook Production Fix Deployment Script
# Run this script on production server to fix VoltMaster webhook issues

echo "======================================="
echo "Router Webhook Production Fix Deployment"
echo "======================================="

# Step 1: Pull latest changes
echo "Pulling latest changes from GitHub..."
git fetch origin main
git checkout main
git pull origin main

# Step 2: Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Step 3: Clear application caches
echo "Clearing application caches..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Step 4: Test webhook endpoint
echo "Testing webhook endpoint..."
curl -X POST \
  -H "Content-Type: application/json" \
  -d '{"VoltMaster":{"connstate":"Connected","psstate":"attached","netstate":"Roaming","imei":"860302050594539","iccid":"89882280666147621019","model":"RG501Q-EU","manuf":"Quectel","serial":"MPY23GM0M002670","revision":"RG501QEUAAR12A11M4G_04_202_04_202","imsi":"901405114762101","simstate":"inserted","pinstate":"OK","modemtime":"25/09/07,13:33:44","rssi":-65,"rscp":0,"ecio":0,"rsrp":-93,"sinr":7,"rsrq":-11,"cellid":"34604288","operator":"Telekom_Deutschland_GER","opernum":26201,"conntype":"5G-NSA","temp":350,"pincount":3,"network":"5G-NSA,26201","serving":"3,LTE,FDD,262,01","modem":"2-1","ip":{"100.114.107.1":null}}}' \
  https://prosoltec.voltmaster.cloud/api/router-webhook/akdiWalGJmDNPdRtGVbZYORrmJGONQWr

echo ""
echo "Deployment completed!"
echo ""
echo "Changes made:"
echo "- Fixed RouterWebhookLog model to allow NULL router_id"
echo "- Added VoltMaster data transformation for complex webhook format"
echo "- Added error handling to prevent webhook logging from breaking main functionality"
echo "- Fixed IP address extraction from both string and object formats"
echo ""
echo "The webhook should now process VoltMaster data correctly."
