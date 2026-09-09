@echo off
echo Stopping Laravel server...
taskkill /F /IM php.exe 2>nul
timeout /t 2 /nobreak >nul

echo Clearing caches...
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear

echo Starting Laravel server...
php artisan serve

