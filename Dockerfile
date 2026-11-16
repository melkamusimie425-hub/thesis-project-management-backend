FROM php:8.4-cli

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git curl zip unzip libpq-dev libonig-dev libxml2-dev libssl-dev \
    && docker-php-ext-install pdo pdo_pgsql pdo_mysql mbstring xml bcmath \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

# Copy composer files first for layer caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

# Copy app source
COPY . .

# Set up storage directories
RUN mkdir -p storage/framework/sessions \
              storage/framework/views \
              storage/framework/cache \
              storage/logs \
              bootstrap/cache \
    && chmod -R 777 storage bootstrap/cache

EXPOSE 10000

CMD ["sh", "-c", "php artisan config:clear && php artisan cache:clear && php artisan migrate --force && php artisan db:seed --force --class=DatabaseSeeder && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}"]
