#!/bin/bash
# Script de deploy para RoutineApp API
# Ejecutar desde la raíz del proyecto: bash deploy.sh

set -e

echo "==> Instalando dependencias..."
composer install --no-dev --optimize-autoloader

echo "==> Publicando config de Sanctum..."
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider" --tag=sanctum-migrations

echo "==> Corriendo migraciones..."
php artisan migrate --force

echo "==> Seedeando roles..."
php artisan db:seed --class=RoleSeeder --force

echo "==> Creando symlink de storage..."
php artisan storage:link

echo "==> Cacheando config, rutas y vistas..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Listo. App deployada."
