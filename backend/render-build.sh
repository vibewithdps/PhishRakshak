#!/usr/bin/env bash
# Exit on error
set -e

# Install dependencies
composer install --no-dev --optimize-autoloader

# Run migrations
php artisan migrate --force
