FROM composer:1.8 AS composer
FROM php:7.2

RUN apt-get update && apt-get install -y \
    zlib1g-dev libxml2-dev nano git unzip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j$(nproc) zip \
    && pecl install xdebug-2.7.1 \
    && docker-php-ext-enable xdebug

WORKDIR /rest-listeners-bundle

COPY --from=composer /usr/bin/composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

ADD . .

RUN chmod +x docker-entrypoint.sh

ENTRYPOINT ["/rest-listeners-bundle/docker-entrypoint.sh"]
