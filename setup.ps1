# Marca la migracion de Spatie como ejecutada y verifica el estado final

Set-Location "E:\routine-app"

function Write-Step($msg) {
    Write-Host "`n>>> $msg" -ForegroundColor Cyan
}

Write-Step "Marcando migracion de Spatie como ejecutada..."
php artisan tinker --execute="DB::table('migrations')->insertOrIgnore(['migration' => '2024_01_01_000007_create_permission_tables', 'batch' => 1]);"

Write-Step "Estado final de migraciones..."
php artisan migrate:status

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "  Todo listo! Podes levantar el servidor con:" -ForegroundColor Green
Write-Host "  php artisan serve" -ForegroundColor White
Write-Host "============================================================" -ForegroundColor Green
