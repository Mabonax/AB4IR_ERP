#!/usr/bin/env sh
set -eu

cd /var/www/html

requested_command="$*"
skip_app_key_check="false"
skip_boot_validation="false"

case "$requested_command" in
    *"key:generate"*)
        skip_app_key_check="true"
        skip_boot_validation="true"
        ;;
esac

require_env() {
    variable_name="$1"
    variable_value="$(printenv "$variable_name" 2>/dev/null || true)"

    if [ -z "$variable_value" ]; then
        echo "Required environment variable '$variable_name' is not set." >&2
        exit 1
    fi
}

mkdir -p \
    bootstrap/cache \
    storage/app/public \
    storage/framework/health \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data bootstrap/cache storage
    chmod -R ug+rwx bootstrap/cache storage
fi

require_env APP_NAME
require_env APP_ENV
require_env APP_URL
require_env DB_CONNECTION
require_env DB_HOST
require_env DB_PORT
require_env DB_DATABASE
require_env DB_USERNAME

if [ "$skip_app_key_check" != "true" ]; then
    require_env APP_KEY
fi

wait-for-services

if [ "$skip_boot_validation" != "true" ] && [ "${APP_VALIDATE_ENV_ON_BOOT:-true}" = "true" ]; then
    php artisan system:validate-deployment --services --strict
fi

if [ ! -L public/storage ]; then
    su-exec www-data php artisan storage:link --quiet || true
fi

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    su-exec www-data php artisan migrate --force
fi

if [ "${RUN_ACCESS_CONTROL_RESYNC:-false}" = "true" ]; then
    su-exec www-data php artisan access-control:resync
fi

if [ "${RUN_OPTIMIZE_ON_BOOT:-false}" = "true" ]; then
    su-exec www-data php artisan optimize
fi

exec "$@"
