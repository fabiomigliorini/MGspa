FROM php:8.2.4-fpm-alpine3.17

MAINTAINER mgpapelaria.com.br

WORKDIR /opt/www/MGspa/laravel

RUN apk upgrade --update --no-cache
RUN apk add --no-cache \
    memcached \
    bash-completion \
    coreutils \
    autoconf \
    postgresql-dev \
    libxml2-dev \
    libpng \
    libpng-dev \
    jpeg-dev \
    libzip-dev \
    zip \
    libmemcached-dev \
    gcc \
    build-base \
    tesseract-ocr \
    mupdf-tools \
    poppler-utils
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-configure gd --with-jpeg
RUN docker-php-ext-install pgsql pdo pdo_pgsql soap gd zip
RUN pecl install memcached
RUN echo extension=memcached.so >> /usr/local/etc/php/conf.d/memcached.ini
RUN composer install

#CMD ["/usr/bin/memcached --user=memcached"]
CMD /usr/bin/memcached --user=memcached & php-fpm
