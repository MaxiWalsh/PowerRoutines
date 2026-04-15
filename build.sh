#!/usr/bin/env bash
set -e

echo "==> Instalando dependencias PHP..."
composer install --no-dev --optimize-autoloader

echo "==> Cacheando config, rutas y vistas..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Corriendo migraciones..."
php artisan migrate --force

echo "==> Creando storage symlink..."
php artisan storage:link

echo "==> ¡Build completado!"
