#!/bin/bash
set -e

# Render assigns a port via the PORT environment variable at runtime —
# Apache needs to listen on that exact port, not the default 80.
PORT="${PORT:-80}"
sed -i "s/80/${PORT}/g" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/g" /etc/apache2/sites-enabled/000-default.conf
sed -i "s/:80 /:${PORT} /g" /etc/apache2/sites-enabled/000-default.conf

echo "Running Laravel deploy steps..."
php artisan migrate --force
php artisan storage:link || true
php artisan config:clear
php artisan route:clear

echo "Starting Apache on port ${PORT}..."
exec apache2-foreground