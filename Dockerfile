# ---------- Stage 1: build the CSS and JavaScript ----------
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build

# ---------- Stage 2: PHP + Apache ----------
FROM php:8.4-apache

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PORT=10000

RUN apt-get update \
 && apt-get install -y --no-install-recommends libpq-dev libzip-dev libicu-dev unzip git \
 && docker-php-ext-install pdo_pgsql zip intl bcmath opcache \
 && a2enmod rewrite headers \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Apache: listen on Render's $PORT, serve /public, allow Laravel's .htaccess,
# and keep few workers so we do not use up the database's connection limit.
RUN sed -i 's/Listen 80/Listen ${PORT}/' /etc/apache2/ports.conf \
 && printf '<VirtualHost *:${PORT}>\n  DocumentRoot /var/www/html/public\n  <Directory /var/www/html/public>\n    AllowOverride All\n    Require all granted\n  </Directory>\n  ErrorLog /proc/self/fd/2\n  CustomLog /proc/self/fd/1 combined\n</VirtualHost>\n' > /etc/apache2/sites-available/000-default.conf \
 && printf '<IfModule mpm_prefork_module>\n  StartServers 2\n  MinSpareServers 2\n  MaxSpareServers 4\n  MaxRequestWorkers 10\n</IfModule>\n' > /etc/apache2/conf-available/limits.conf \
 && a2enconf limits \
 && echo 'ServerName localhost' > /etc/apache2/conf-available/servername.conf \
 && a2enconf servername

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
 && printf 'upload_max_filesize=10M\npost_max_size=12M\nmemory_limit=256M\n' > "$PHP_INI_DIR/conf.d/zz-app.ini"

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

COPY . .
COPY --from=assets /app/public/build ./public/build

RUN composer dump-autoload --optimize --no-dev --no-scripts \
 && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs storage/app/public bootstrap/cache \
 && sed -i 's/\r$//' docker-start.sh \
 && chmod +x docker-start.sh \
 && chown -R www-data:www-data storage bootstrap/cache

CMD ["./docker-start.sh"]
