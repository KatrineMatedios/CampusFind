#!/bin/sh
set -e

PORT="${PORT:-80}"
sed -i "s|^Listen 80\$|Listen ${PORT}|" /etc/apache2/ports.conf
sed -i "s|<VirtualHost \*:80>|<VirtualHost *:${PORT}>|" /etc/apache2/sites-available/000-default.conf

cd /var/www/html/backend

# Render sets RENDER=true. Its database cache tables are not always available
# to the running web process, so use file-backed cache/sessions there.
if [ -n "${RENDER:-}" ]; then
    export CACHE_STORE=file
    export SESSION_DRIVER=file
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force

if [ "${SEED_DB:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec "$@"
