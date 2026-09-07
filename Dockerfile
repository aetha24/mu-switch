FROM php:8.4-cli-bookworm AS php-base

WORKDIR /var/www/html

RUN apt-get update \
    && apt-get install -y --no-install-recommends ca-certificates curl libpq-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libzip-dev unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd pdo_pgsql pgsql zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM php-base AS dependencies

COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --no-progress --no-scripts

FROM php-base AS frontend

RUN curl -fsSL https://deb.nodesource.com/setup_24.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY package.json package-lock.json ./
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY . .
# Wayfinder boots Laravel while Vite builds. Give that build-only process an
# isolated database and throwaway key; Render supplies the real values at run time.
RUN touch /tmp/wayfinder.sqlite
ENV APP_ENV=production \
    APP_KEY=base64:QXNkZkdoSktMb3BXZXJ0WXVJb0FzRGZnSGprTG9wV2VyVA== \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/wayfinder.sqlite \
    CACHE_STORE=array \
    SESSION_DRIVER=array \
    QUEUE_CONNECTION=sync
RUN npm ci && npm run build

FROM php-base

COPY . .
COPY --from=dependencies /var/www/html/vendor ./vendor
COPY --from=frontend /var/www/html/public/build ./public/build

RUN chmod +x docker/start-web.sh docker/start-worker.sh \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 10000

CMD ["./docker/start-web.sh"]
