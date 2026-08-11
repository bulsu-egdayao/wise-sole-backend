#!/usr/bin/env bash
echo "Running deploy script..."

php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache

echo "Deploy script finished"