FROM php:8.3-fpm


# =========================
# System dependencies
# =========================
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
    && rm -rf /var/lib/apt/lists/*



# =========================
# Composer
# =========================
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer



WORKDIR /var/www



# =========================
# Dependencies cache
# =========================
COPY composer.json composer.lock ./


RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-scripts



# =========================
# Application
# =========================
COPY . .



# =========================
# PHP configuration
# =========================
COPY docker/php/custom.ini \
     /usr/local/etc/php/conf.d/custom.ini



# =========================
# Laravel optimization
# =========================
RUN php artisan package:discover --ansi || true



# =========================
# Laravel permissions
# =========================
RUN mkdir -p storage/logs bootstrap/cache \
    && chown -R www-data:www-data /var/www \
    && chmod -R 775 storage bootstrap/cache



# =========================
# PHP-FPM
# =========================
EXPOSE 9000


USER www-data


CMD ["php-fpm"]