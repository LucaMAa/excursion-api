FROM php:8.4.27-fpm-alpine

RUN apk add --no-cache git unzip icu-dev libzip-dev libxml2-dev oniguruma-dev postgresql-dev libpq \
    && docker-php-ext-install dom intl mbstring opcache pdo_pgsql xml zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html