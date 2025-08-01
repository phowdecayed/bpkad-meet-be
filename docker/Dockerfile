# ---- Stage 1: Build Stage ----
# Use an alias "builder" for this stage
FROM php:8.4-fpm AS builder

# Set working directory
WORKDIR /var/www/html

# Install build dependencies (add composer's requirements)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpq-dev \
    curl

# Install PHP extensions needed for composer
RUN docker-php-ext-install pdo pdo_pgsql pgsql zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy only composer files to leverage Docker cache
COPY database/ database/
COPY composer.json composer.lock ./

# Install dependencies
RUN composer install --no-interaction --no-plugins --no-scripts --prefer-dist --no-dev

# --- Stage 2: Final Application Stage ---
FROM php:8.4-fpm

WORKDIR /var/www/html

# Install only production system dependencies
RUN apt-get update && apt-get install -y \
    libpq-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    libzip-dev \
    libonig-dev \
    libssl-dev \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install production PHP extensions
RUN docker-php-ext-install pdo pdo_pgsql pgsql mbstring exif pcntl bcmath gd zip
RUN pecl install redis && docker-php-ext-enable redis

# Copy the application code AND vendor files from the builder stage
COPY --from=builder /var/www/html/vendor /var/www/html/vendor
COPY . .

# Set correct permissions. This is crucial for when you use named volumes.
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port and start server
EXPOSE 9000
CMD ["php-fpm"]
