#!/bin/sh
set -e

# Render (and most container platforms) tell the app which port to listen on.
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

# Only needed when uploads use the local "public" disk.
php artisan storage:link --force >/dev/null 2>&1 || true

php artisan migrate --force

# The seeder is idempotent, so seeding on every Render deploy keeps the demo
# accounts available without requiring a manual environment change.
if [ -n "${RENDER:-}" ] || [ "${SEED_DB:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec "$@"
