FROM php:8.3-fpm-alpine

WORKDIR /opt/www/MGspa/api

# Pacotes do sistema (PostgreSQL + libs nativas + utilitários)
RUN apk upgrade --update --no-cache && \
    apk add --no-cache \
        bash \
        coreutils \
        autoconf \
        build-base \
        postgresql-dev \
        libxml2-dev \
        libpng \
        libpng-dev \
        jpeg-dev \
        libzip-dev \
        icu-dev \
        zip \
        unzip \
        git \
        curl

# Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin --filename=composer

# Extensões PHP
RUN docker-php-ext-configure gd --with-jpeg && \
    docker-php-ext-install pdo pdo_pgsql pgsql gd zip intl bcmath

# Limites de memória
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/99-custom.ini

CMD ["php-fpm"]
