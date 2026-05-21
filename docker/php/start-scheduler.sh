#!/usr/bin/env sh
set -eu

heartbeat_file="/tmp/scheduler-heartbeat"
trap 'exit 0' INT TERM

while true; do
    date +%s > "$heartbeat_file"
    su-exec www-data php artisan schedule:run --verbose --no-interaction
    sleep "${SCHEDULER_INTERVAL:-60}"
done
