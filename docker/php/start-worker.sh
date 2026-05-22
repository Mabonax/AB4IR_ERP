#!/usr/bin/env sh
set -eu

health_dir="/var/www/html/storage/framework/health"
started_file="${health_dir}/worker-started-at"
heartbeat_file="${health_dir}/worker-heartbeat"
heartbeat_interval="${WORKER_HEARTBEAT_INTERVAL:-30}"

mkdir -p "${health_dir}"
date +%s > "${started_file}"

(
    while true; do
        date +%s > "${heartbeat_file}"
        sleep "${heartbeat_interval}"
    done
) &
heartbeat_pid="$!"

cleanup() {
    kill "${heartbeat_pid}" 2>/dev/null || true
    wait "${heartbeat_pid}" 2>/dev/null || true
}

trap cleanup INT TERM EXIT

su-exec www-data php artisan queue:work \
    --verbose \
    --tries="${QUEUE_WORKER_TRIES:-3}" \
    --sleep="${QUEUE_WORKER_SLEEP:-3}" \
    --timeout="${QUEUE_WORKER_TIMEOUT:-120}" \
    --max-time="${QUEUE_WORKER_MAX_TIME:-3600}" \
    --queue="${QUEUE_WORKER_QUEUES:-default}" \
    --force &
worker_pid="$!"

wait "${worker_pid}"
worker_status="$?"

cleanup

exit "${worker_status}"
