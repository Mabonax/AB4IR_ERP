#!/usr/bin/env bash
set -euo pipefail

remote_url="$(git remote get-url origin)"

if [[ "${remote_url}" != git@github.com:* ]]; then
    echo "Git remote is not using GitHub SSH." >&2
    echo "Current remote: ${remote_url}" >&2
    echo "Fix it with:" >&2
    echo "  git remote set-url origin git@github.com:Mabonax/AB4IR_ERP.git" >&2
    exit 1
fi

echo "Origin remote uses SSH: ${remote_url}"
echo "Verifying GitHub SSH access..."

if ssh -T git@github.com 2>&1 | grep -Eq "successfully authenticated|Hi "; then
    echo "GitHub SSH access: ok"
else
    echo "GitHub SSH verification failed. Ensure the server deploy key is loaded and granted repo access." >&2
    exit 1
fi
