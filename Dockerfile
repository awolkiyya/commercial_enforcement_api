FROM php:8.3-fpm

# =========================================================
# System dependencies
# =========================================================

RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libcurl4-openssl-dev \
    libicu-dev \
    libpq-dev \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        calendar \
        intl \
        opcache \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# =========================================================
# Composer
# =========================================================

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# =========================================================
# Application directory
# =========================================================

WORKDIR /var/www

# =========================================================
# Composer dependency cache
# =========================================================

COPY composer.json composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts

# =========================================================
# Application source
# =========================================================

COPY . .

# =========================================================
# PHP production configuration
# =========================================================

COPY docker/php/custom.ini \
     /usr/local/etc/php/conf.d/custom.ini

# =========================================================
# Laravel required directories
# =========================================================

RUN mkdir -p \
        storage/logs \
        bootstrap/cache

# =========================================================
# Permissions
# =========================================================

RUN chown -R www-data:www-data \
        storage \
        bootstrap/cache \
    && chmod -R 775 \
        storage \
        bootstrap/cache

# =========================================================
# Laravel package discovery
# =========================================================

RUN php artisan package:discover --ansi || true

# =========================================================
# PHP-FPM
# =========================================================

EXPOSE 9000

USER www-data

CMD ["php-fpm"]