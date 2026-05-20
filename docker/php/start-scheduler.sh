#!/usr/bin/env sh
set -eu

while true; do
    php artisan schedule:run --verbose --no-interaction
    sleep "${SCHEDULER_INTERVAL:-60}"
done
