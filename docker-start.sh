#!/bin/sh
set -e
cd /var/www/html

php artisan package:discover --ansi
php artisan storage:link --force || true
php artisan migrate --force

php artisan config:cache
php artisan route:cache || true
php artisan view:cache || true

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
