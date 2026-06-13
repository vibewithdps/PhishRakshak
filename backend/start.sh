#!/usr/bin/env bash
set -e

echo "Starting PhishRakshak Laravel backend..."

php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

php artisan migrate --force

php artisan serve --host=0.0.0.0 --port=${PORT:-10000}
