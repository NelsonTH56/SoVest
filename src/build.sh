#!/usr/bin/env bash
# Render.com build script for Laravel + Vite

# Exit on error
set -e

# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
npm install

# Build frontend assets with Vite
npm run build

# Laravel optimizations
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
