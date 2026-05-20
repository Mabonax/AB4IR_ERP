#!/usr/bin/env sh
set -eu

exec php artisan queue:work \
    --verbose \
    --tries="${QUEUE_WORKER_TRIES:-3}" \
    --sleep="${QUEUE_WORKER_SLEEP:-3}" \
    --timeout="${QUEUE_WORKER_TIMEOUT:-120}" \
    --queue="${QUEUE_WORKER_QUEUES:-default}" \
    --force
