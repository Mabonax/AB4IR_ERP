#!/usr/bin/env sh
set -eu

heartbeat_file="/var/www/html/storage/framework/health/worker-heartbeat"
heartbeat_ttl="${WORKER_HEARTBEAT_TTL:-180}"

pgrep -f "artisan queue:work" >/dev/null
[ -f "$heartbeat_file" ] || exit 1

last_seen="$(cat "$heartbeat_file")"
current_time="$(date +%s)"

[ $((current_time - last_seen)) -lt "$heartbeat_ttl" ]
