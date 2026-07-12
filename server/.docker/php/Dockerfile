# =============================================================
# Stage 1: Base — extensões comuns
# =============================================================
FROM php:8.4-fpm-alpine AS base

RUN apk update && apk upgrade && apk add --no-cache \
        zip unzip curl \
        libzip-dev oniguruma-dev \
        libpng-dev libjpeg-turbo-dev freetype-dev \
        libxml2-dev openssl-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd pdo_mysql mbstring zip opcache intl \
    # Redis via PECL (requer build-deps temporariamente)
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

RUN echo "upload_max_filesize=20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size=20M" >> /usr/local/etc/php/conf.d/uploads.ini

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

FROM base AS development

RUN apk add --no-cache git nano vim bash \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps \
    && rm -rf /tmp/* /var/cache/apk/*

# Configuração completa do Xdebug para dev
RUN { \
        echo "xdebug.mode=develop,coverage,debug"; \
        echo "xdebug.client_host=host.docker.internal"; \
        echo "xdebug.client_port=9003"; \
        echo "xdebug.start_with_request=yes"; \
        echo "xdebug.log=/tmp/xdebug.log"; \
    } > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

USER www-data