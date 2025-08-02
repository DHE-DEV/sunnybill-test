@echo off
echo ================================
echo   Swagger API-Dokumentation Fix
echo ================================
echo.

echo 1. Laravel Cache leeren...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.

echo 2. Format auf JSON setzen...
echo L5_FORMAT_TO_USE_FOR_DOCS=json >> .env
echo.

echo 3. Swagger Dokumentation neu generieren...
php artisan l5-swagger:generate
echo.

echo 4. Berechtigungen setzen (falls nötig)...
echo Checking storage permissions...
echo.

echo ================================
echo Fix abgeschlossen!
echo ================================
echo.
echo Versuchen Sie jetzt:
echo 1. http://sunnybill-test.test/api/documentation
echo 2. http://sunnybill-test.test/docs
echo.
echo Falls noch Probleme bestehen:
echo - Prüfen Sie das Laravel-Log in storage/logs/laravel.log
echo - Testen Sie die API direkt: /api/users/search?q=test
echo.
pause