# ==============================================================================
# JRV CRM - Multi-Stage Production Dockerfile for Horizontally Scaled Nodes
# ==============================================================================

FROM php:8.2-fpm-alpine as base

# Install system dependencies & PHP extensions required for Laravel, MySQL, Redis & GD
RUN apk update && apk add --no-cache \
    bash \
    curl \
    libpng-dev \
    libzip-dev \
    zip \
    unzip \
    git \
    oniguruma-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    mysql-client \
    linux-headers \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql mbstring zip exif pcntl bcmath gd \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/cache/apk/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer dependency files first to leverage Docker cache
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application source code
COPY . .

# Generate optimized autoloader
RUN composer dump-autoload --optimize --no-dev

# Set permissions for Laravel storage and cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 8000

# Start built-in server or php-fpm (or octane if installed)
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
