FROM php:7.4.27-fpm-alpine

MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/laravel

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf postgresql-dev libxml2-dev libpng libpng-dev libzip-dev zip
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pgsql pdo pdo_pgsql soap gd zip

