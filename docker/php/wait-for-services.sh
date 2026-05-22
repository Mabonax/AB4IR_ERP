#!/usr/bin/env sh
set -eu

retry_until() {
    timeout="$1"
    shift

    start_time="$(date +%s)"

    until "$@"; do
        current_time="$(date +%s)"

        if [ $((current_time - start_time)) -ge "$timeout" ]; then
            echo "Timed out waiting for dependent service." >&2
            return 1
        fi

        sleep 2
    done
}

wait_for_tcp_endpoint() {
    target_host="$1"
    target_port="$2"

    php -r '
        $host = $argv[1];
        $port = (int) $argv[2];
        $connection = @fsockopen($host, $port, $errno, $error, 2);

        if ($connection === false) {
            fwrite(STDERR, sprintf("Endpoint %s:%d is not reachable yet.\n", $host, $port));
            exit(1);
        }

        fclose($connection);
    ' "$target_host" "$target_port"
}

wait_for_database() {
    if [ "${WAIT_FOR_DB:-true}" != "true" ]; then
        return 0
    fi

    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."

    retry_until "${DB_CONNECT_TIMEOUT:-60}" wait_for_tcp_endpoint "${DB_HOST}" "${DB_PORT}"
}

wait_for_redis() {
    if [ "${WAIT_FOR_REDIS:-true}" != "true" ]; then
        return 0
    fi

    echo "Waiting for Redis at ${REDIS_HOST}:${REDIS_PORT}..."

    retry_until "${REDIS_CONNECT_TIMEOUT:-60}" wait_for_tcp_endpoint "${REDIS_HOST}" "${REDIS_PORT}"
}

wait_for_database
wait_for_redis
