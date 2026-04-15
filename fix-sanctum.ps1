Set-Location "E:\routine-app"
Write-Host ">>> Instalando Laravel Sanctum..." -ForegroundColor Cyan
php C:\php\composer.phar require laravel/sanctum
Write-Host ">>> Listo! Reinicia php artisan serve" -ForegroundColor Green
