#!/usr/bin/env sh
set -eu

php artisan migrate --force --no-interaction
php artisan storage:link || true

# Render's web service has no separate cron process. Keep Laravel's scheduler
# beside the web server so expired mobile-money prompts are closed promptly
# without requiring an extra paid worker service.
php artisan schedule:work &
scheduler_pid=$!

php artisan serve --host=0.0.0.0 --port="${PORT:-10000}" &
web_pid=$!

shutdown() {
    kill "$scheduler_pid" "$web_pid" 2>/dev/null || true
}

trap shutdown INT TERM EXIT
wait "$web_pid"
