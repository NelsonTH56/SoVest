#!/bin/bash
set -e

# Use PORT from environment (Render sets this) or default to 10000
PORT=${PORT:-10000}

# Update Apache to listen on the correct port
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80/:${PORT}/" /etc/apache2/sites-available/000-default.conf

# Run migrations (optional - comment out if you prefer manual migrations)
php artisan migrate --force

# Cache config at runtime (when env vars are available)
php artisan config:cache

# Start Apache
exec apache2-foreground
