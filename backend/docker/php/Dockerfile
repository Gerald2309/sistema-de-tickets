FROM php:8.5-fpm@sha256:c34bcf437ed61bd5e4c1114494eaf1ffd54253bce2b95d263bfa18b350ee8432

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip pcntl \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
