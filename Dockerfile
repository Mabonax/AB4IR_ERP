# syntax=docker/dockerfile:1.7

FROM php:8.4-fpm-alpine AS php-base
WORKDIR /var/www/html

ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
        bash \
        fcgi \
        icu-dev \
        libpng-dev \
        libzip-dev \
        mariadb-client \
        oniguruma-dev \
        procps \
        redis \
        su-exec \
        unzip \
        zip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        opcache \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

FROM composer:2 AS composer-deps
WORKDIR /app

COPY composer.json composer.lock ./

RUN --mount=type=cache,target=/tmp/cache/composer \
    composer install \
        --no-dev \
        --prefer-dist \
        --no-interaction \
        --no-progress \
        --optimize-autoloader \
        --no-scripts

FROM node:22-alpine AS node-deps
WORKDIR /app

COPY package.json package-lock.json ./

RUN --mount=type=cache,target=/root/.npm \
    npm ci

FROM php-base AS asset-builder
WORKDIR /var/www/html

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_NAME=AB4IRERP \
    APP_URL=http://localhost \
    APP_KEY=base64:build-only-placeholder-build-only-placeholder \
    LOG_CHANNEL=stderr \
    CACHE_STORE=array \
    QUEUE_CONNECTION=sync \
    SESSION_DRIVER=array \
    DB_CONNECTION=sqlite \
    DB_DATABASE=/tmp/build.sqlite \
    PUBLIC_REGISTRATION_ENABLED=false

COPY --from=node:22-alpine /usr/local /usr/local
COPY --from=composer-deps /app/vendor /var/www/html/vendor
COPY --from=node-deps /app/node_modules /var/www/html/node_modules

COPY artisan composer.json composer.lock package.json package-lock.json components.json eslint.config.js tsconfig.json vite.config.ts ./
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes

RUN mkdir -p \
        bootstrap/cache \
        public/build \
        storage/app/public \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/testing \
        storage/framework/views \
        storage/logs \
    && touch /tmp/build.sqlite \
    && php artisan package:discover --ansi \
    && php artisan wayfinder:generate --with-form \
    && npm run build \
    && rm -rf node_modules

FROM php-base AS app
WORKDIR /var/www/html

COPY . /var/www/html
COPY --from=composer-deps /app/vendor /var/www/html/vendor
COPY --from=asset-builder /var/www/html/public/build /var/www/html/public/build

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/php/start-app.sh /usr/local/bin/start-app
COPY docker/php/start-worker.sh /usr/local/bin/start-worker
COPY docker/php/start-scheduler.sh /usr/local/bin/start-scheduler
COPY docker/php/healthcheck-app.sh /usr/local/bin/healthcheck-app
COPY docker/php/healthcheck-worker.sh /usr/local/bin/healthcheck-worker
COPY docker/php/healthcheck-scheduler.sh /usr/local/bin/healthcheck-scheduler
COPY docker/php/wait-for-services.sh /usr/local/bin/wait-for-services

RUN rm -f /var/www/html/.env \
    && mkdir -p \
        /var/www/html/bootstrap/cache \
        /var/www/html/storage/app/public \
        /var/www/html/storage/framework/cache/data \
        /var/www/html/storage/framework/sessions \
        /var/www/html/storage/framework/testing \
        /var/www/html/storage/framework/views \
        /var/www/html/storage/logs \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod +x \
        /usr/local/bin/app-entrypoint \
        /usr/local/bin/start-app \
        /usr/local/bin/start-worker \
        /usr/local/bin/start-scheduler \
        /usr/local/bin/healthcheck-app \
        /usr/local/bin/healthcheck-worker \
        /usr/local/bin/healthcheck-scheduler \
        /usr/local/bin/wait-for-services

ENTRYPOINT ["app-entrypoint"]
CMD ["start-app"]

FROM nginx:1.29-alpine AS web
WORKDIR /var/www/html

COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app --chown=nginx:nginx /var/www/html/public /var/www/html/public

EXPOSE 8080
USER nginx
