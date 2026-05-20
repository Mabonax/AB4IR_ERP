#!/usr/bin/env sh
set -eu

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is not set. Generate one before starting the production stack." >&2
    exit 1
fi

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

if [ ! -L public/storage ]; then
    php artisan storage:link --quiet || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

if [ "${RUN_ACCESS_CONTROL_RESYNC:-false}" = "true" ]; then
    php artisan access-control:resync
fi

php artisan optimize

exec "$@"
