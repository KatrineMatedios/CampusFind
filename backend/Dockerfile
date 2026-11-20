# Use this Dockerfile when Render's Root Directory is set to "backend".
# The root-level Dockerfile is used when Root Directory is the repository root.
FROM php:8.3-apache

RUN apt-get update && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libicu-dev \
        libonig-dev \
        libxml2-dev \
        libcurl4-openssl-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql mbstring xml curl zip gd intl bcmath exif fileinfo \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html/backend

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . ./

RUN composer dump-autoload --optimize --no-dev \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data storage bootstrap/cache

RUN sed -ri 's!/var/www/html!/var/www/html/backend/public!g' \
        /etc/apache2/sites-available/000-default.conf \
        /etc/apache2/apache2.conf \
    && printf '%s\n' \
        '<Directory /var/www/html/backend/public>' \
        '    AllowOverride All' \
        '    Require all granted' \
        '</Directory>' > /etc/apache2/conf-available/laravel.conf \
    && a2enconf laravel

# Apache answers OPTIONS requests itself, so Laravel's CORS middleware never
# sees them. Add the headers at the web-server level for preflight requests.
RUN printf '%s\n' \
        '<IfModule mod_headers.c>' \
        '    Header always setifempty Access-Control-Allow-Origin "*"' \
        '    Header always setifempty Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"' \
        '    Header always setifempty Access-Control-Allow-Headers "Content-Type, Authorization, Accept, X-Requested-With, X-XSRF-TOKEN"' \
        '</IfModule>' > /etc/apache2/conf-available/cors.conf \
    && a2enconf cors

COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80

ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
