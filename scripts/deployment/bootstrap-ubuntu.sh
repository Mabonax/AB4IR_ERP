#!/usr/bin/env bash
set -euo pipefail

if [[ "${EUID}" -ne 0 ]]; then
    echo "Run this script as root or via sudo." >&2
    exit 1
fi

if [[ ! -r /etc/os-release ]]; then
    echo "Cannot detect OS release metadata." >&2
    exit 1
fi

. /etc/os-release

if [[ "${ID}" != "ubuntu" ]]; then
    echo "This bootstrap script only supports Ubuntu." >&2
    exit 1
fi

if [[ -z "${VERSION_CODENAME:-}" ]]; then
    echo "Ubuntu codename detection failed." >&2
    exit 1
fi

echo "Preparing Docker Engine repository for Ubuntu ${VERSION_CODENAME}..."

apt-get update
apt-get install -y ca-certificates curl gnupg lsb-release openssh-client git

for package in docker.io docker-doc docker-compose docker-compose-v2 podman-docker containerd runc; do
    if dpkg -s "${package}" >/dev/null 2>&1; then
        apt-get remove -y "${package}"
    fi
done

install -m 0755 -d /etc/apt/keyrings

if [[ -f /etc/apt/sources.list.d/docker.list ]]; then
    rm -f /etc/apt/sources.list.d/docker.list
fi

curl -fsSL https://download.docker.com/linux/ubuntu/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
chmod a+r /etc/apt/keyrings/docker.gpg

cat >/etc/apt/sources.list.d/docker.list <<EOF
deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/ubuntu ${VERSION_CODENAME} stable
EOF

apt-get update
apt-get install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin

systemctl enable docker
systemctl start docker

echo
echo "Docker Engine installed successfully."
echo "Next steps:"
echo "1. Add your deployment user to the docker group:"
echo "   sudo usermod -aG docker <deploy-user>"
echo "2. Re-login or run: newgrp docker"
echo "3. Verify access with: docker version && docker compose version"
