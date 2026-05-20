FROM node:22-alpine AS frontend-builder
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY vite.config.ts tsconfig.json components.json eslint.config.js ./
RUN npm run build

FROM composer:2 AS composer-builder
WORKDIR /app

COPY composer.json composer.lock ./
COPY artisan ./artisan
COPY app ./app
COPY bootstrap ./bootstrap
COPY config ./config
COPY database ./database
COPY public ./public
COPY resources ./resources
COPY routes ./routes

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --optimize-autoloader

FROM php:8.4-fpm-alpine AS app
WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        fcgi \
        $PHPIZE_DEPS \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        zip \
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
    && apk del $PHPIZE_DEPS \
    && rm -rf /tmp/pear

COPY docker/php/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/php/zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf

COPY . /var/www/html
COPY --from=composer-builder /app/vendor /var/www/html/vendor
COPY --from=frontend-builder /app/public/build /var/www/html/public/build

RUN rm -f /var/www/html/.env \
    && mkdir -p /var/www/html/storage/app/public \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R ug+rwx /var/www/html/storage /var/www/html/bootstrap/cache

COPY docker/php/entrypoint.sh /usr/local/bin/app-entrypoint
COPY docker/php/start-app.sh /usr/local/bin/start-app
COPY docker/php/start-worker.sh /usr/local/bin/start-worker
COPY docker/php/start-scheduler.sh /usr/local/bin/start-scheduler

RUN chmod +x /usr/local/bin/app-entrypoint /usr/local/bin/start-app /usr/local/bin/start-worker /usr/local/bin/start-scheduler

USER www-data

ENTRYPOINT ["app-entrypoint"]
CMD ["start-app"]

FROM nginx:1.29-alpine AS web
WORKDIR /var/www/html
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY --from=app /var/www/html/public /var/www/html/public
