#!/usr/bin/env sh
set -eu

composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan optimize
php artisan system:validate-deployment --services --strict
php artisan system:deployment-status
