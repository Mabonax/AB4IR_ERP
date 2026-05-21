#!/usr/bin/env sh
set -eu

pgrep -f "artisan queue:work" >/dev/null
