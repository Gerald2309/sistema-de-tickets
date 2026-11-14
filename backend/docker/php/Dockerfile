FROM php:8.5-fpm@sha256:baf3ea13a8207fa18e1011bad1016e8b5e59fc974c6966d0efa6f36a16f875f0

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
