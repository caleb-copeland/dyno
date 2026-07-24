# syntax=docker/dockerfile:1
# =============================================================================
# Production image — Dyno (Laravel 13 + Filament v5 + Livewire 4).
#
# Runtime: FrankenPHP (dunglas/frankenphp, PHP 8.4) — single process/container,
# stock Caddyfile serves ./public. TLS is terminated by Traefik upstream, so
# the server listens on plain HTTP :8080 (unprivileged; runs as www-data).
#
# Code + built assets are baked in; nothing is bind-mounted. Runtime caches
# (config/route/view/event) are built at BOOT by docker/entrypoint.sh, since
# the environment is only known at boot. Dev keeps using compose.yaml (Sail).
# =============================================================================

FROM dunglas/frankenphp:1-php8.4 AS base

# pdo_mysql (DB), intl (Filament), gmp + bcmath (minishlink/web-push crypto),
# zip (composer), pcntl (scheduler signals/timeouts).
RUN install-php-extensions bcmath gmp intl pcntl pdo_mysql zip

WORKDIR /app

# ---- Stage 1: PHP dependencies ----------------------------------------------
FROM base AS vendor

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY composer.json composer.lock ./
# --no-scripts: post-autoload-dump runs artisan (needs the full app) — the
# optimized autoloader is re-dumped in the final stage once code is present.
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    --prefer-dist --no-scripts --no-progress

# ---- Stage 2: frontend build (Breeze auth pages use Vite; app is inline CSS) -
FROM node:22-alpine AS assets

WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---- Final stage: runtime ----------------------------------------------------
FROM base AS runtime

ENV SERVER_NAME=:8080

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    { \
        echo 'memory_limit = 256M'; \
        echo 'expose_php = Off'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.validate_timestamps = 0'; \
        echo 'opcache.memory_consumption = 192'; \
        echo 'opcache.max_accelerated_files = 20000'; \
    } > "$PHP_INI_DIR/conf.d/zz-app.ini"

# App source (see .dockerignore), then deps + built assets.
COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# Runtime dir skeleton (excluded from the build context) — artisan needs these.
RUN mkdir -p \
        storage/app/public \
        storage/app/private \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache

# Re-dump the optimized autoloader now app code is present; publish Filament v5
# static assets into public/.
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative && \
    php artisan filament:assets && \
    rm /usr/local/bin/composer

RUN rm -f public/hot && \
    rm -rf public/storage && ln -s /app/storage/app/public public/storage && \
    chown -R www-data:www-data storage bootstrap/cache && \
    # Caddy state dirs for the non-root runtime user.
    chown -R www-data:www-data /config /data

COPY --chmod=755 docker/entrypoint.sh /usr/local/bin/app-entrypoint

USER www-data
EXPOSE 8080

HEALTHCHECK --interval=15s --timeout=5s --start-period=45s --retries=5 \
    CMD curl -fsS http://127.0.0.1:8080/up || exit 1

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]
