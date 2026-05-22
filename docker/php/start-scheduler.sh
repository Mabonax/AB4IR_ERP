#!/usr/bin/env sh
set -eu

heartbeat_dir="/var/www/html/storage/framework/health"
heartbeat_file="${heartbeat_dir}/scheduler-heartbeat"
trap 'exit 0' INT TERM

mkdir -p "${heartbeat_dir}"

while true; do
    date +%s > "$heartbeat_file"
    su-exec www-data php artisan schedule:run --verbose --no-interaction
    sleep "${SCHEDULER_INTERVAL:-60}"
done
