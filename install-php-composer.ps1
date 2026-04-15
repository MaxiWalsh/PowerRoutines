# ============================================================
#  Instalador automático de PHP 8.3 + Composer para Windows
#  Ejecutar como Administrador en PowerShell
# ============================================================

$ErrorActionPreference = "Stop"

function Write-Step($msg) {
    Write-Host "`n>>> $msg" -ForegroundColor Cyan
}

# ── 1. Descargar PHP 8.3 NTS x64 (URL dinámica) ─────────────────────────────
Write-Step "Buscando la versión más reciente de PHP 8.3..."

$phpDir = "C:\php"
$phpZip = "$env:TEMP\php.zip"

if (Test-Path "$phpDir\php.exe") {
    Write-Host "  PHP ya existe en $phpDir, saltando descarga." -ForegroundColor Yellow
} else {
    # URLs a intentar en orden
    $phpUrls = @(
        "https://windows.php.net/downloads/releases/php-8.3.29-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/php-8.3.28-nts-Win32-vs16-x64.zip",
        "https://windows.php.net/downloads/releases/php-8.3.27-nts-Win32-vs16-x64.zip"
    )

    $phpUrl = $null
    foreach ($url in $phpUrls) {
        try {
            Write-Host "  Probando: $url" -ForegroundColor Gray
            $response = Invoke-WebRequest -Uri $url -Method Head -UseBasicParsing -ErrorAction Stop
            if ($response.StatusCode -eq 200) {
                $phpUrl = $url
                break
            }
        } catch {
            Write-Host "  No disponible, probando siguiente..." -ForegroundColor Gray
        }
    }

    if (-not $phpUrl) {
        Write-Host "  No se pudo encontrar una URL valida automaticamente." -ForegroundColor Red
        Write-Host "  Andá a https://windows.php.net/downloads/releases/ y copiá la URL del ZIP mas reciente." -ForegroundColor Yellow
        exit 1
    }

    Write-Host "  Descargando: $phpUrl" -ForegroundColor Gray
    Invoke-WebRequest -Uri $phpUrl -OutFile $phpZip -UseBasicParsing

    Write-Host "  Extrayendo en $phpDir..." -ForegroundColor Gray
    New-Item -ItemType Directory -Force -Path $phpDir | Out-Null
    Expand-Archive -Path $phpZip -DestinationPath $phpDir -Force
    Remove-Item $phpZip
    Write-Host "  PHP extraido correctamente." -ForegroundColor Green
}

# ── 2. Configurar php.ini ────────────────────────────────────────────────────
Write-Step "Configurando php.ini..."

$phpIni = "$phpDir\php.ini"
if (-not (Test-Path $phpIni)) {
    Copy-Item "$phpDir\php.ini-development" $phpIni
}

$extensions = @(
    "extension=curl",
    "extension=fileinfo",
    "extension=mbstring",
    "extension=openssl",
    "extension=pdo_mysql",
    "extension=pdo_sqlite",
    "extension=sqlite3",
    "extension=zip"
)

$iniContent = Get-Content $phpIni -Raw
foreach ($ext in $extensions) {
    $commented = ";$ext"
    if ($iniContent -match [regex]::Escape($commented)) {
        $iniContent = $iniContent -replace [regex]::Escape($commented), $ext
        Write-Host "  Habilitada: $ext" -ForegroundColor Gray
    } elseif ($iniContent -notmatch [regex]::Escape($ext)) {
        $iniContent += "`n$ext"
        Write-Host "  Agregada: $ext" -ForegroundColor Gray
    }
}
Set-Content -Path $phpIni -Value $iniContent
Write-Host "  php.ini configurado." -ForegroundColor Green

# ── 3. Agregar PHP al PATH del sistema ──────────────────────────────────────
Write-Step "Agregando PHP al PATH del sistema..."

$currentPath = [System.Environment]::GetEnvironmentVariable("Path", "Machine")
if ($currentPath -notlike "*$phpDir*") {
    [System.Environment]::SetEnvironmentVariable("Path", "$currentPath;$phpDir", "Machine")
    $env:Path = "$env:Path;$phpDir"
    Write-Host "  $phpDir agregado al PATH." -ForegroundColor Green
} else {
    Write-Host "  PHP ya estaba en el PATH." -ForegroundColor Yellow
}

# Verificar
$phpVersion = & "$phpDir\php.exe" -v 2>&1 | Select-Object -First 1
Write-Host "  $phpVersion" -ForegroundColor Green

# ── 4. Instalar Composer ─────────────────────────────────────────────────────
Write-Step "Instalando Composer..."

$composerPhar = "$phpDir\composer.phar"
$composerBat  = "$phpDir\composer.bat"

if (Test-Path $composerBat) {
    Write-Host "  Composer ya está instalado." -ForegroundColor Yellow
} else {
    # Descargar composer.phar directamente (sin instalador gráfico)
    Invoke-WebRequest -Uri "https://getcomposer.org/composer-stable.phar" -OutFile $composerPhar -UseBasicParsing

    # Crear un .bat para poder llamar 'composer' desde cualquier lado
    Set-Content -Path $composerBat -Value "@echo off`r`n`"$phpDir\php.exe`" `"$composerPhar`" %*"

    Write-Host "  Composer instalado en $phpDir." -ForegroundColor Green
}

$composerVersion = & "$phpDir\php.exe" "$composerPhar" -V 2>&1 | Select-Object -First 1
Write-Host "  $composerVersion" -ForegroundColor Green

# ── 5. Crear proyecto Laravel en E:\routine-app ──────────────────────────────
Write-Step "Creando proyecto Laravel en E:\routine-app..."

$target = "E:\routine-app"

# Backup de los archivos que ya generamos
$backupDir = "$env:TEMP\routine-backup"
New-Item -ItemType Directory -Force -Path $backupDir | Out-Null
foreach ($folder in @("app", "database", "routes")) {
    $src = Join-Path $target $folder
    if (Test-Path $src) {
        Copy-Item $src -Destination $backupDir -Recurse -Force
    }
}
Write-Host "  Backup guardado en $backupDir" -ForegroundColor Yellow

# Crear Laravel en carpeta temporal y mover
$tmpDir = "$env:TEMP\laravel-new"
Remove-Item $tmpDir -Recurse -Force -ErrorAction SilentlyContinue
& "$phpDir\php.exe" "$composerPhar" create-project laravel/laravel $tmpDir

Write-Host "  Copiando Laravel a $target..." -ForegroundColor Gray
Get-ChildItem $tmpDir | ForEach-Object {
    $dest = Join-Path $target $_.Name
    if (-not (Test-Path $dest)) {
        Copy-Item $_.FullName -Destination $dest -Recurse -Force
    }
}
Remove-Item $tmpDir -Recurse -Force -ErrorAction SilentlyContinue

# Restaurar nuestros archivos (tienen prioridad sobre los de Laravel)
foreach ($folder in @("app", "database", "routes")) {
    $src = Join-Path $backupDir $folder
    if (Test-Path $src) {
        Copy-Item $src -Destination $target -Recurse -Force
    }
}
Remove-Item $backupDir -Recurse -Force -ErrorAction SilentlyContinue
Write-Host "  Archivos del proyecto restaurados." -ForegroundColor Green

# ── 6. Instalar dependencias del proyecto ────────────────────────────────────
Set-Location $target

Write-Step "Instalando spatie/laravel-permission..."
& "$phpDir\php.exe" "$composerPhar" require spatie/laravel-permission

Write-Step "Configurando Sanctum..."
& "$phpDir\php.exe" artisan install:api --no-interaction

# ── FIN ──────────────────────────────────────────────────────────────────────
Write-Host "`n============================================================" -ForegroundColor Green
Write-Host "  Instalacion completada!" -ForegroundColor Green
Write-Host "  PHP 8.3     -> C:\php\php.exe" -ForegroundColor White
Write-Host "  Composer    -> C:\php\composer.bat" -ForegroundColor White
Write-Host "  Laravel     -> E:\routine-app" -ForegroundColor White
Write-Host "============================================================" -ForegroundColor Green
Write-Host "`nProximo paso: editar E:\routine-app\.env con tus datos de MySQL y correr:" -ForegroundColor Yellow
Write-Host "  cd E:\routine-app" -ForegroundColor White
Write-Host "  php artisan migrate" -ForegroundColor White
