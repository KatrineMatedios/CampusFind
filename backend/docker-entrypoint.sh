#!/bin/sh

echo "=== CampusFind startup ==="

# Run migrations (non-fatal so the server still starts for debugging)
php artisan migrate --force || echo "WARNING: migrations failed, continuing anyway"

# Cache config for performance (optional, non-fatal)
php artisan config:cache || echo "WARNING: config:cache failed"
php artisan route:cache || echo "WARNING: route:cache failed"

# Link storage if not already linked
php artisan storage:link 2>/dev/null || true

echo "=== Starting Apache ==="
exec apache2-foreground
