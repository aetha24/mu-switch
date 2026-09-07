#!/usr/bin/env sh
set -eu

exec php artisan queue:work --tries=3 --timeout=90 --sleep=3
