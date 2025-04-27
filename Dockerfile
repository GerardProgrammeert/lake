FROM composer:2.6.5 AS composer

FROM php:8.4-fpm-alpine

COPY --from=composer /usr/bin/composer /usr/bin/composer

# Install system dependencies
RUN apk add --no-cache \
    make \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    nano \
    mysql-client \
    nodejs \
    npm \
    && docker-php-ext-configure gd \
        --with-freetype=/usr/include/ \
        --with-jpeg=/usr/include/ \
    && docker-php-ext-install -j$(nproc) gd pdo pdo_mysql zip

#  Add php user with specified UID to ensure matching file ownership between the WSL user
ARG UID=1000
RUN adduser -D -u $UID -s /bin/bash php
USER php

WORKDIR /var/www/html
COPY . /var/www/html
