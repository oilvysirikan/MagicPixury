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

echo "==> License portal preflight (read-only, never fabricates state)"

php -r '
$path = "storage/app/portal";
if (!file_exists($path)) {
    echo "PORTAL: MISSING — every web request will redirect to the license installer route (LaravelInstaller::license). Complete activation in the browser after first boot; do NOT create an empty file, it does not satisfy the check.\n";
    exit(0);
}
$raw = trim((string) @file_get_contents($path));
$data = @unserialize($raw, ["allowed_classes" => false]);
if (!is_array($data)) {
    echo "PORTAL: UNREADABLE — file exists but is not a valid serialized array. Re-activate via the license installer route.\n";
    exit(0);
}
$key = $data["liquid_license_domain_key"] ?? null;
$type = $data["liquid_license_type"] ?? null;
$blocked = $data["blocked"] ?? false;
if ($blocked) { echo "PORTAL: BLOCKED — license revoked upstream, app will abort(500). Contact support.\n"; exit(0); }
if (!$key) { echo "PORTAL: INVALID — present but liquid_license_domain_key is empty. Re-activate via the license installer route.\n"; exit(0); }
echo "PORTAL: OK — license type [" . $type . "], domain key present.\n";
'

if [ "${MIGRATE_ON_BOOT:-}" = "true" ]; then
    echo "==> MIGRATE_ON_BOOT=true: running migrations (migrate --force, no seeding)"
    php artisan migrate --force
else
    echo "==> MIGRATE_ON_BOOT not set: skipping auto-migrate."
    echo "    First deploy must run once (one-shot job, NOT on every boot):"
    echo "      php artisan app:populate-db   # migrate + seed + admin + entity/engine seeders"
fi

echo "==> Clearing stale Laravel caches"

php artisan optimize:clear

echo "==> Rebuilding Laravel caches"

php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting services"

exec /usr/bin/supervisord \
    -c /etc/supervisord.conf
