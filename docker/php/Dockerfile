FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    git \
    icu-dev \
    libzip-dev \
    oniguruma-dev \
    mariadb-client \
  && docker-php-ext-install \
    intl \
    mbstring \
    mysqli \
    pdo \
    pdo_mysql \
    zip

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
