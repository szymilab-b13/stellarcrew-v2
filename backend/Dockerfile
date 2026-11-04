# StellarCrew v2 — Backend (Symfony 8 + PHP 8.4)
# Baseline image. Composer/sources wired up in BL-SETUP-002/003.

FROM php:8.4-fpm-alpine AS base

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        linux-headers \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        intl \
        bcmath \
        zip \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

CMD ["php-fpm"]

FROM base AS dev

RUN apk add --no-cache $PHPIZE_DEPS \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del $PHPIZE_DEPS

COPY <<'EOF' /usr/local/etc/php/conf.d/dev.ini
memory_limit = 512M
display_errors = On
error_reporting = E_ALL
opcache.enable = 0
xdebug.mode = develop,debug
xdebug.client_host = host.docker.internal
EOF
