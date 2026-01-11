FROM php:8.2-apache

RUN a2enmod rewrite headers

RUN apt-get update \
 && apt-get install -y --no-install-recommends sqlite3 libsqlite3-dev pkg-config \
 && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo pdo_sqlite

# --- NEW LINE ADDED BELOW ---
COPY uploads.ini /usr/local/etc/php/conf.d/uploads.ini

RUN mkdir -p /var/www/html/data \
 && chown -R www-data:www-data /var/www/html/data \
 && chmod -R 775 /var/www/html/data

WORKDIR /var/www/html