@echo off
echo ================================
echo   SunnyBill API Test Suite
echo ================================
echo.

echo Checking PHP version...
php -v
echo.

echo ================================
echo Running Basic Connection Test...
echo ================================
php artisan test tests/Feature/BasicConnectionTest.php
echo.

echo ================================
echo Running Factory Tests...
echo ================================
php artisan test tests/Feature/FactoryTest.php
echo.

echo ================================
echo Running Simple API Tests...
echo ================================
php artisan test tests/Feature/Api/SimpleTaskTest.php
echo.

echo ================================
echo Running Authentication Tests...
echo ================================
php artisan test tests/Feature/Api/AuthenticationTest.php
echo.

echo ================================
echo Running Customer API Tests...
echo ================================
php artisan test tests/Feature/Api/CustomerApiTest.php
echo.

echo ================================
echo Running Project API Tests...
echo ================================
php artisan test tests/Feature/Api/ProjectApiTest.php
echo.

echo ================================
echo Running Solar Plant API Tests...
echo ================================
php artisan test tests/Feature/Api/SolarPlantApiTest.php
echo.

echo ================================
echo Running Task API Tests...
echo ================================
php artisan test tests/Feature/Api/TaskApiTest.php
echo.

echo ================================
echo All API Tests Complete!
echo ================================
pause