#!/usr/bin/env bash
set -euo pipefail

if ! command -v docker >/dev/null 2>&1; then
    echo "Docker is not installed or not on PATH." >&2
    exit 1
fi

if ! docker info >/dev/null 2>&1; then
    echo "Docker daemon is not reachable." >&2
    echo "If you see a docker.sock permission error, add your user to the docker group and re-login:" >&2
    echo "  sudo usermod -aG docker ${USER}" >&2
    echo "  newgrp docker" >&2
    echo "If the current shell still does not see the new group, log out and back in before retrying." >&2
    exit 1
fi

echo "Docker daemon access: ok"
docker compose version

if id -nG "${USER}" | grep -qw docker; then
    echo "Docker group membership: ok"
else
    echo "Warning: ${USER} is not in the docker group. sudo may still be required." >&2
fi
