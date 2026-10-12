# ─── Stage 1: web assets (Vite) ───
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY vite.config.js ./
# Tailwind reads the Blade views (@source '../views')
COPY resources ./resources
RUN npm run build                     # → /app/public/build

# ─── Stage 2: the application ───
FROM php:8.5-apache AS app

## ─── A3: PHP base image and extensions ───

RUN apt-get update \
    && apt-get install -y --no-install-recommends libicu-dev libzip-dev unzip \
    && docker-php-ext-install pdo_mysql intl zip bcmath opcache \
    && rm -rf /var/lib/apt/lists/* \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -ri 's/^expose_php\s*=.*/expose_php = Off/' "$PHP_INI_DIR/php.ini"

## ─── A4: Setting apache document rule and pre-fork configuration to handle multiple requests ───

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite

## ─── A5: Installing composer dependancies ───

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction

## ─── A6: Building assets ───

COPY . .
COPY --from=assets /app/public/build ./public/build
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && chown -R www-data:www-data storage bootstrap/cache
EXPOSE 80
# CMD inherited from php:8.5-apache (apache2-foreground)

