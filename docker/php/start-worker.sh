#!/usr/bin/env sh
set -eu

exec su-exec www-data php artisan queue:work \
    --verbose \
    --tries="${QUEUE_WORKER_TRIES:-3}" \
    --sleep="${QUEUE_WORKER_SLEEP:-3}" \
    --timeout="${QUEUE_WORKER_TIMEOUT:-120}" \
    --max-time="${QUEUE_WORKER_MAX_TIME:-3600}" \
    --queue="${QUEUE_WORKER_QUEUES:-default}" \
    --force
