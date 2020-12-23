FROM composer as composer
COPY composer.json composer.json
COPY composer.lock composer.lock
RUN composer install --prefer-dist --no-interaction --no-scripts
RUN composer dumpautoload -o

FROM php:7.4-fpm-alpine

WORKDIR /app/
COPY src src
COPY test test
COPY --from=composer /app/vendor vendor
COPY phpunit.xml.dist phpunit.xml.dist
