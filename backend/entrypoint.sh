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

    # Never use the repo's SQLite file in a read-only container.
    export DB_CONNECTION=mysql

    # Support Render's MySQL env group names when individual DB_* values
    # were not copied into this web service manually.
    if [ -z "${DB_HOST:-}" ] && [ -n "${MYSQL_HOST:-}" ]; then
        export DB_HOST="$MYSQL_HOST"
    fi
    if [ -z "${DB_PORT:-}" ] && [ -n "${MYSQL_PORT:-}" ]; then
        export DB_PORT="$MYSQL_PORT"
    fi
    if [ -z "${DB_DATABASE:-}" ] && [ -n "${MYSQL_DATABASE:-}" ]; then
        export DB_DATABASE="$MYSQL_DATABASE"
    fi
    if [ -z "${DB_USERNAME:-}" ] && [ -n "${MYSQL_USER:-}" ]; then
        export DB_USERNAME="$MYSQL_USER"
    fi
    if [ -z "${DB_PASSWORD:-}" ] && [ -n "${MYSQL_PASSWORD:-}" ]; then
        export DB_PASSWORD="$MYSQL_PASSWORD"
    fi
fi

php artisan storage:link --force >/dev/null 2>&1 || true
php artisan migrate --force

# The seeder is idempotent, so seeding on every Render deploy keeps the demo
# accounts available without requiring a manual environment change.
if [ -n "${RENDER:-}" ] || [ "${SEED_DB:-false}" = "true" ]; then
    php artisan db:seed --force
fi

exec "$@"
