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

wait_for_database() {
    if [ "${WAIT_FOR_DB:-true}" != "true" ]; then
        return 0
    fi

    echo "Waiting for MySQL at ${DB_HOST}:${DB_PORT}..."

    retry_until "${DB_CONNECT_TIMEOUT:-60}" sh -c '
        if [ -n "${DB_PASSWORD:-}" ]; then
            mysqladmin ping -h "${DB_HOST}" -P "${DB_PORT}" -u"${DB_USERNAME}" -p"${DB_PASSWORD}" --silent
        else
            mysqladmin ping -h "${DB_HOST}" -P "${DB_PORT}" -u"${DB_USERNAME}" --silent
        fi
    '
}

wait_for_redis() {
    if [ "${WAIT_FOR_REDIS:-true}" != "true" ]; then
        return 0
    fi

    echo "Waiting for Redis at ${REDIS_HOST}:${REDIS_PORT}..."

    retry_until "${REDIS_CONNECT_TIMEOUT:-60}" sh -c '
        if [ -n "${REDIS_PASSWORD:-}" ] && [ "${REDIS_PASSWORD}" != "null" ]; then
            redis-cli -h "${REDIS_HOST}" -p "${REDIS_PORT}" -a "${REDIS_PASSWORD}" ping | grep -q PONG
        else
            redis-cli -h "${REDIS_HOST}" -p "${REDIS_PORT}" ping | grep -q PONG
        fi
    '
}

wait_for_database
wait_for_redis
