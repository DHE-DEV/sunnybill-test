@echo off
echo ==========================================
echo   SWAGGER API DOKUMENTATION - KOMPLETT FIX
echo ==========================================
echo.

echo 1. Cache komplett leeren...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
echo.

echo 2. Alte API-Docs löschen...
if exist "storage\api-docs\api-docs.json" del "storage\api-docs\api-docs.json"
if exist "storage\api-docs\api-docs.yaml" del "storage\api-docs\api-docs.yaml"
echo.

echo 3. Swagger Dokumentation neu generieren...
php artisan l5-swagger:generate
echo.

echo 4. Prüfen ob JSON-Datei erstellt wurde...
if exist "storage\api-docs\api-docs.json" (
    echo ✓ JSON-Datei erfolgreich erstellt
) else (
    echo ✗ JSON-Datei nicht erstellt - Swagger-Annotations fehlen möglicherweise
)
echo.

echo ==========================================
echo   VERFÜGBARE URLS TESTEN:
echo ==========================================
echo.
echo 1. Swagger UI:
echo    http://sunnybill-test.test/api/documentation
echo.
echo 2. Alternative Swagger UI:
echo    http://sunnybill-test.test/docs
echo.
echo 3. Direkte API-Docs:
echo    http://sunnybill-test.test/docs/api-docs.json
echo.
echo 4. API-Endpunkte direkt testen:
echo    http://sunnybill-test.test/api/users/search?q=test
echo    http://sunnybill-test.test/api/users/all
echo.
echo ==========================================
echo   Bei anhaltenden Problemen:
echo ==========================================
echo.
echo 1. Laravel-Log prüfen: storage/logs/laravel.log
echo 2. Browser-Konsole prüfen (F12)
echo 3. API direkt mit curl/Postman testen
echo.
echo ODER einfach die API direkt nutzen - sie funktioniert auch ohne Swagger!
echo.
pause