# syntax=docker/dockerfile:1

# ---------------------------------------------------------------------------
# Stage: vendor — production Composer dependencies only, no dev tooling.
# ---------------------------------------------------------------------------
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --no-interaction \
        --prefer-dist \
        --ignore-platform-reqs

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------------------------------------------------------------------------
# Stage: frontend — compiled Vite assets, node toolchain never reaches runtime.
#
# Needs vendor/ from the `vendor` stage before building: resources/css/app.css
# imports vendor/livewire/flux/dist/flux.css directly, and Tailwind's content
# scan also globs vendor/livewire/flux*/stubs -- the Flux UI package ships its
# compiled CSS/Blade stubs through Composer, not npm.
#
# VITE_* build args are public browser-facing config only (the Reverb *app
# key*, not the app secret) -- safe to pass as build args, unlike real
# secrets. They get compiled as literal strings into the built JS, so the
# public Reverb hostname must be known at image-build time, not deploy time.
# ---------------------------------------------------------------------------
FROM node:20-alpine AS frontend

ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ENV VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY . .
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# ---------------------------------------------------------------------------
# Stage: runtime — php-fpm application image. Used for the app, queue,
# scheduler, and Reverb services (different command, same image).
# ---------------------------------------------------------------------------
FROM php:8.4-fpm-bookworm AS runtime

RUN apt-get update && apt-get install -y --no-install-recommends \
        libzip-dev \
        libpq-dev \
        libsqlite3-dev \
        libonig-dev \
        libcurl4-openssl-dev \
        libxml2-dev \
        unzip \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pdo_sqlite \
        pcntl \
        bcmath \
        mbstring \
        curl \
        dom \
        xml \
        zip \
    && docker-php-ext-enable opcache \
    && apt-get purge -y --auto-remove libzip-dev libpq-dev libsqlite3-dev libonig-dev libcurl4-openssl-dev libxml2-dev unzip \
    && rm -rf /var/lib/apt/lists/*

COPY docker/php/opcache.ini /usr/local/etc/php/conf.d/zz-opcache.ini
COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini

WORKDIR /var/www/html

RUN groupadd --gid 1000 app \
    && useradd --uid 1000 --gid app --shell /bin/bash --create-home app

COPY --chown=app:app --from=vendor /app/vendor ./vendor
COPY --chown=app:app --from=frontend /app/public/build ./public/build
COPY --chown=app:app . .

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs storage/database storage/app/private bootstrap/cache \
    && chown -R app:app storage bootstrap/cache \
    && chmod -R u+rwX storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER app

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]

LABEL org.opencontainers.image.source="https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi"
LABEL org.opencontainers.image.description="Warehouse-Koperasi Laravel application runtime"
LABEL org.opencontainers.image.licenses="UNLICENSED"

# ---------------------------------------------------------------------------
# Stage: web — static nginx front, serving the exact public/ this commit
# built. Kept in the same Dockerfile so it can never drift from the PHP
# runtime it fronts; published as its own small, immutable image (the
# duplication this avoids is four copies of the *application*, not this).
# ---------------------------------------------------------------------------
FROM nginx:1.27-alpine AS web

COPY --from=runtime /var/www/html/public /var/www/html/public
COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

EXPOSE 8000

LABEL org.opencontainers.image.source="https://github.com/stephenprasetyachrismawan/WareHouse-Koperasi"
LABEL org.opencontainers.image.description="Warehouse-Koperasi nginx static/proxy front"
LABEL org.opencontainers.image.licenses="UNLICENSED"
