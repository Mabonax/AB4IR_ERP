#!/usr/bin/env sh
set -eu

heartbeat_file="/tmp/scheduler-heartbeat"
heartbeat_ttl="${SCHEDULER_HEARTBEAT_TTL:-180}"

[ -f "$heartbeat_file" ] || exit 1

last_seen="$(cat "$heartbeat_file")"
current_time="$(date +%s)"

[ $((current_time - last_seen)) -lt "$heartbeat_ttl" ]
