#!/usr/bin/env bash
set -euo pipefail

app_port="${APP_PORT:-8080}"
health_timeout="${POST_DEPLOY_HEALTH_TIMEOUT:-120}"

retry_until() {
    timeout="$1"
    shift

    start_time="$(date +%s)"

    until "$@"; do
        current_time="$(date +%s)"

        if (( current_time - start_time >= timeout )); then
            return 1
        fi

        sleep 2
    done
}

wait_for_http_up() {
    curl -fsS "http://127.0.0.1:${app_port}/up" >/dev/null
}

wait_for_service_state() {
    service="$1"

    container_id="$(docker compose ps -q "${service}")"
    [[ -n "${container_id}" ]] || return 1

    running_state="$(docker inspect --format '{{.State.Status}}' "${container_id}")"
    [[ "${running_state}" == "running" ]] || return 1

    health_state="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "${container_id}")"
    [[ "${health_state}" == "healthy" || "${health_state}" == "none" ]]
}

echo "Verifying health endpoint..."
retry_until "${health_timeout}" wait_for_http_up || {
    echo "Health endpoint /up did not become ready within ${health_timeout}s." >&2
    exit 1
}

echo "Verifying deployment validation..."
docker compose exec -T app php artisan system:validate-deployment --services --strict

echo "Verifying deployment status..."
docker compose exec -T app php artisan system:deployment-status --json

echo "Verifying container health states..."
for service in app worker scheduler web; do
    if ! retry_until "${health_timeout}" wait_for_service_state "${service}"; then
        container_id="$(docker compose ps -q "${service}")"
        if [[ -z "${container_id}" ]]; then
            echo "Service '${service}' does not have a running container." >&2
        else
            running_state="$(docker inspect --format '{{.State.Status}}' "${container_id}")"
            health_state="$(docker inspect --format '{{if .State.Health}}{{.State.Health.Status}}{{else}}none{{end}}' "${container_id}")"
            echo "Service '${service}' failed verification. State=${running_state}, Health=${health_state}" >&2
        fi
        exit 1
    fi
done

echo "Post-deploy verification passed."
