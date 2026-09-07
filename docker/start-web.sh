#!/usr/bin/env sh
set -eu

php artisan migrate --force --no-interaction
php artisan storage:link || true

exec php artisan serve --host=0.0.0.0 --port="${PORT:-10000}"
