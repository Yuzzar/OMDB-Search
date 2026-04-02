FROM php:8.2-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath xml zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Copy composer files first for caching
COPY composer.json composer.lock* ./

# Install PHP dependencies
RUN composer install --no-dev --optimize-autoloader --no-scripts

# Copy rest of the app
COPY . .

# Run post-install scripts
RUN composer dump-autoload --optimize

# Set permissions
RUN chmod -R 775 storage bootstrap/cache

EXPOSE $PORT

CMD php artisan config:cache && php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
