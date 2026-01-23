FROM php:8.2-cli

# Install system dependencies and PHP extensions
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    curl \
    zip \
    unzip \
    sqlite3 \
    libsqlite3-dev \
    libzip-dev \
    libonig-dev \
    libxml2-dev \
    && docker-php-ext-install pdo_sqlite mbstring zip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader

# Clear bootstrap cache (contains references to dev packages that aren't installed)
RUN rm -f bootstrap/cache/*.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage

# Create SQLite database
RUN touch database/database.sqlite \
    && chmod 664 database/database.sqlite \
    && chown www-data:www-data database/database.sqlite

# Expose port
EXPOSE 8000

# Run Laravel server
CMD sh -c "\
    [ ! -f .env ] && cp .env.example .env || true && \
    rm -f bootstrap/cache/*.php && \
    php artisan package:discover --ansi && \
    php artisan key:generate --force && \
    php artisan migrate --force && \
    php artisan db:seed --force && \
    php artisan serve --host=0.0.0.0 --port=8000 \
"
