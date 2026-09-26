#!/bin/sh
#
# MagicAI v11.20 production entrypoint
# Prepares the runtime, refreshes Laravel caches, then hands off to
# supervisord (Nginx + PHP-FPM + Queue worker + Scheduler).

set -e

cd /var/www

echo "==> MagicAI production startup"

mkdir -p \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/public \
    storage/app/r2-cache \
    bootstrap/cache \
    public/uploads

chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    public/uploads

echo "==> Clearing stale Laravel caches"

php artisan optimize:clear

echo "==> Rebuilding Laravel caches"

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services"

exec /usr/bin/supervisord \
    -c /etc/supervisord.conf
