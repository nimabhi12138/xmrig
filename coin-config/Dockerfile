# syntax=docker/dockerfile:1

FROM composer:2 AS composer

FROM php:8.1-apache

RUN apt-get update \
	&& apt-get install -y --no-install-recommends git unzip libzip-dev \
	&& rm -rf /var/lib/apt/lists/* \
	&& docker-php-ext-configure zip \
	&& docker-php-ext-install pdo_mysql zip

RUN a2enmod rewrite
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

COPY --from=composer /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY . /var/www/html

RUN chown -R www-data:www-data storage bootstrap/cache \
	&& chmod -R 777 storage bootstrap/cache

ENTRYPOINT ["/bin/bash", "/var/www/html/docker/entrypoint.sh"]