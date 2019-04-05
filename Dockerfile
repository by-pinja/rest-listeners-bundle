FROM composer:1.8 AS composer
FROM php:7.2

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        zlib1g-dev libxml2-dev \
        git nano unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-install -j$(nproc) zip

WORKDIR /test-tools-bundle

COPY --from=composer /usr/bin/composer /usr/bin/composer

ADD composer.json  ./

RUN composer install

ADD . .
