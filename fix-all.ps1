# ============================================================
#  Fix completo - corre todo lo que falta
# ============================================================
Set-Location "E:\routine-app"

function Write-Step($msg) {
    Write-Host "`n>>> $msg" -ForegroundColor Cyan
}

Write-Step "Limpiando cache de configuracion..."
php artisan config:clear
php artisan cache:clear

Write-Step "Corriendo migraciones pendientes..."
php artisan migrate --force

Write-Step "Marcando migracion de Spatie como ejecutada (ya existia)..."
php artisan tinker --execute="DB::table('migrations')->insertOrIgnore(['migration' => '2024_01_01_000007_create_permission_tables', 'batch' => 1]);"

Write-Step "Re-seeding roles con ambos guards (web y api)..."
php artisan db:seed --class=RoleSeeder --force

Write-Step "Estado final de todas las migraciones..."
php artisan migrate:status

Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "  Listo! Reinicia php artisan serve y probá el register." -ForegroundColor Green
Write-Host "============================================================" -ForegroundColor Green
