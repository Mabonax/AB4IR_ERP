#!/usr/bin/env sh
set -eu

exec su-exec www-data php-fpm -F
