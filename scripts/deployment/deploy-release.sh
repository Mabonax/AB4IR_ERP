#!/usr/bin/env bash
set -euo pipefail

APP_RUNTIME_IMAGE="${APP_RUNTIME_IMAGE:-}"
WEB_RUNTIME_IMAGE="${WEB_RUNTIME_IMAGE:-}"
GHCR_USERNAME="${GHCR_USERNAME:-}"
GHCR_TOKEN="${GHCR_TOKEN:-}"

bash scripts/deployment/check-host-access.sh
bash scripts/deployment/check-github-ssh.sh

if [[ -n "${GHCR_TOKEN}" ]]; then
    if [[ -z "${GHCR_USERNAME}" ]]; then
        echo "GHCR_USERNAME must be set when GHCR_TOKEN is provided." >&2
        exit 1
    fi

    echo "${GHCR_TOKEN}" | docker login ghcr.io -u "${GHCR_USERNAME}" --password-stdin
fi

git fetch origin
git checkout main
git pull --ff-only origin main

export APP_RUNTIME_IMAGE
export WEB_RUNTIME_IMAGE

docker compose config >/dev/null
docker compose pull

echo "Starting infrastructure services first..."
docker compose up -d --remove-orphans mysql redis

echo "Starting application runtime..."
docker compose up -d app
docker compose exec -T app php artisan system:validate-deployment --services --strict
docker compose exec -T app php artisan migrate --force
docker compose exec -T app php artisan optimize

echo "Starting asynchronous runtimes..."
docker compose up -d worker scheduler
docker compose exec -T worker php artisan queue:restart || true

echo "Starting web entrypoint last..."
docker compose up -d web

echo
echo "Deployment completed successfully."
echo "Active images:"
docker compose images
echo
echo "Health summary:"
docker compose ps
