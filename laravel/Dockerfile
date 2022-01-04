FROM php:7.4.27-fpm-alpine

MAINTAINER mgpapelaria.com.br

RUN apk upgrade --update --no-cache
RUN apk add --no-cache bash-completion coreutils autoconf postgresql-dev
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pgsql pdo pdo_pgsql


RUN apk add libxml2-dev
RUN docker-php-ext-install soap


RUN apk add libpng libpng-dev
RUN docker-php-ext-install gd


RUN apk add libzip-dev zip
RUN docker-php-ext-install zip

