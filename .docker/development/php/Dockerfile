FROM php:8.3.16-cli

RUN apt-get update \
    && apt-get install -y \
        libzip-dev \
    && docker-php-ext-install \
        zip

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions xdebug-stable

RUN apt-get clean; rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /usr/share/doc/*

COPY --from=composer/composer:latest-bin /composer /usr/bin/composer

ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /var/www/app
