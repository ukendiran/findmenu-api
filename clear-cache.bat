@echo off
echo Clearing Laravel caches...
php artisan route:clear
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo.
echo Cache cleared successfully!
echo.
echo Please restart your Laravel server (php artisan serve)
pause
