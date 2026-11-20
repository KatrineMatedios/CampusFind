#!/bin/sh
set -e

cd /var/www/html

# ---------------------------------------------------------------------------
# 1. Listen on the port the host injects (Render uses 10000, Docker uses 80).
# ---------------------------------------------------------------------------
PORT="${PORT:-80}"
printf 'Listen %s\n' "$PORT" > /etc/apache2/ports.conf
sed -i "s|<VirtualHost \\*:80>|<VirtualHost *:${PORT}>|g" \
    /etc/apache2/sites-available/000-default.conf

# ---------------------------------------------------------------------------
# 2. Force the database settings this image actually provides.
#    MariaDB runs inside this container, so SQLite must never be selected.
# ---------------------------------------------------------------------------
export DB_CONNECTION=mysql
export DB_HOST=127.0.0.1
export DB_PORT=3306
export DB_DATABASE=campus_lost_found
export DB_USERNAME=root
export DB_PASSWORD=
export CACHE_STORE=file
export QUEUE_CONNECTION=sync
export SESSION_DRIVER=file
export SESSION_DOMAIN=
export SESSION_SECURE_COOKIE=false

upsert_env() {
    key="$1"
    value="$2"
    if grep -q "^${key}=" .env 2>/dev/null; then
        sed -i "s|^${key}=.*|${key}=${value}|" .env
    else
        printf '%s=%s\n' "$key" "$value" >> .env
    fi
}

upsert_env DB_CONNECTION mysql
upsert_env DB_HOST 127.0.0.1
upsert_env DB_PORT 3306
upsert_env DB_DATABASE campus_lost_found
upsert_env DB_USERNAME root
upsert_env DB_PASSWORD ''
upsert_env CACHE_STORE file
upsert_env SESSION_DRIVER file
upsert_env SESSION_DOMAIN ''

# ---------------------------------------------------------------------------
# 3. Origins. The Vite app on Vercel calls this API directly, so allow the
#    Vercel origin. Wildcards also cover Vercel preview deployments.
# ---------------------------------------------------------------------------
strip_host() {
    printf '%s' "$1" | sed -E 's#^[a-zA-Z][a-zA-Z0-9+.-]*://##; s#[/:?#].*$##'
}

RENDER_URL="${RENDER_EXTERNAL_URL:-}"
FRONTEND_URL="${FRONTEND_URL:-}"
RENDER_HOST="$(strip_host "$RENDER_URL")"
FRONTEND_HOST="$(strip_host "$FRONTEND_URL")"

ALLOWED_ORIGINS="$(printf '%s,%s' "$RENDER_URL" "$FRONTEND_URL" \
    | sed 's/^,//; s/,$//; s/,,/,/g')"
# Token auth does not use Sanctum's cookie/CSRF flow. Keep stateful domains
# local-only so production requests are never blocked by CSRF validation.
STATEFUL_DOMAINS='localhost,127.0.0.1'

export CORS_ALLOWED_ORIGINS="$ALLOWED_ORIGINS"
export CORS_ALLOWED_ORIGIN_PATTERNS='https://*.vercel.app'
export SANCTUM_STATEFUL_DOMAINS="$STATEFUL_DOMAINS"

upsert_env CORS_ALLOWED_ORIGINS "$ALLOWED_ORIGINS"
upsert_env CORS_ALLOWED_ORIGIN_PATTERNS 'https://*.vercel.app'
upsert_env SANCTUM_STATEFUL_DOMAINS "$STATEFUL_DOMAINS"

if [ -n "$RENDER_URL" ]; then
    export SESSION_SECURE_COOKIE=true
    upsert_env APP_URL "$RENDER_URL"
fi
if [ -n "$FRONTEND_URL" ]; then
    upsert_env FRONTEND_URL "$FRONTEND_URL"
fi

# ---------------------------------------------------------------------------
# 4. APP_KEY.
# ---------------------------------------------------------------------------
if [ -n "${APP_KEY:-}" ]; then
    upsert_env APP_KEY "$APP_KEY"
elif ! grep -q '^APP_KEY=base64:' .env 2>/dev/null; then
    php artisan key:generate --force
fi

# ---------------------------------------------------------------------------
# 5. Start the bundled MariaDB server.
# ---------------------------------------------------------------------------
mkdir -p /run/mysqld
chown mysql:mysql /run/mysqld
mysqld --user=mysql --datadir=/var/lib/mysql --bind-address=127.0.0.1 \
    --innodb_buffer_pool_size=64M --max_connections=50 --skip-name-resolve \
    > /var/log/mysql.log 2>&1 &

echo "Waiting for MariaDB..."
i=0
until mysqladmin ping --silent 2>/dev/null; do
    i=$((i + 1))
    if [ "$i" -gt 60 ]; then
        echo "MariaDB failed to start"
        cat /var/log/mysql.log || true
        exit 1
    fi
    sleep 1
done

mysql -u root <<'SQL' 2>/dev/null || true
CREATE DATABASE IF NOT EXISTS campus_lost_found;
CREATE USER IF NOT EXISTS 'root'@'127.0.0.1' IDENTIFIED BY '';
GRANT ALL PRIVILEGES ON *.* TO 'root'@'127.0.0.1' WITH GRANT OPTION;
FLUSH PRIVILEGES;
SQL

# ---------------------------------------------------------------------------
# 6. Prepare Laravel.
# ---------------------------------------------------------------------------
php artisan optimize:clear > /dev/null 2>&1 || true
php artisan migrate --force

COUNT="$(php -r '
    try {
        $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=campus_lost_found", "root", "");
        echo $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    } catch (Exception $e) { echo "0"; }
' 2>/dev/null || echo '0')"

if [ "$COUNT" = "0" ]; then
    echo "Seeding demo data..."
    php artisan db:seed --force
fi

php artisan storage:link --force > /dev/null 2>&1 || true

echo "CampusFind API is ready on port ${PORT}."

exec apache2-foreground
