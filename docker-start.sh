#!/bin/sh
set -e

PORT=${PORT:-10000}

echo "==> Clearing config and cache..."
php artisan config:clear
php artisan cache:clear

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Seeding database..."
php artisan db:seed --force

echo "==> Starting server on port $PORT..."
exec php artisan serve --host=0.0.0.0 --port=$PORT