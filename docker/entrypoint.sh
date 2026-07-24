#!/bin/sh
# Production entrypoint. Idempotent: safe to run on every boot.
#
# All containers (app / scheduler) rebuild the Laravel runtime caches from the
# *current* environment — caches are deliberately NOT baked into the image
# because env vars are only known at boot. Only the `app` role runs migrations,
# so the containers never race on the schema.
set -e

role="${CONTAINER_ROLE:-app}"

retry() {
    n=0
    until "$@"; do
        n=$((n + 1))
        if [ "$n" -ge 20 ]; then
            echo "[entrypoint] '$*' failed after ${n} attempts — giving up" >&2
            return 1
        fi
        echo "[entrypoint] '$*' failed (database not ready?), retrying (${n}/20)…"
        sleep 3
    done
}

if [ "$role" = "app" ]; then
    echo "[entrypoint] role=app — running migrations"
    retry php artisan migrate --force
fi

echo "[entrypoint] role=${role} — rebuilding runtime caches"
retry php artisan config:cache
retry php artisan route:cache
php artisan view:cache
php artisan event:cache

exec "$@"
