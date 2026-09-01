#!/bin/bash
set -e

# Copy .env if not exists
if [ ! -f /var/www/html/.env ]; then
    if [ -f /var/www/html/.env.docker ]; then
        echo "Creating .env from .env.docker..."
        cp /var/www/html/.env.docker /var/www/html/.env
    elif [ -f /var/www/html/.env.example ]; then
        echo "Creating .env from .env.example..."
        cp /var/www/html/.env.example /var/www/html/.env
    fi
fi

# Ensure storage and bootstrap directories exist
mkdir -p /var/www/html/storage/framework/sessions \
         /var/www/html/storage/framework/views \
         /var/www/html/storage/framework/cache \
         /var/www/html/storage/logs \
         /var/www/html/bootstrap/cache \
         /var/www/html/public/uploads

# Permissions
chmod -R 777 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public/uploads

# Install composer dependencies if vendor folder does not exist
if [ ! -d /var/www/html/vendor ]; then
    echo "Vendor directory not found. Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

# Generate application key if missing in .env
if [ -f /var/www/html/.env ]; then
    if ! grep -q "^APP_KEY=base64:" /var/www/html/.env; then
        echo "Generating application key..."
        php artisan key:generate --force
    fi
fi

# Storage symlink
if [ ! -L /var/www/html/public/storage ]; then
    echo "Creating storage symlink..."
    php artisan storage:link || true
fi

# Run pending migrations automatically
if [ "$1" = "php-fpm" ]; then
    echo "Checking database connection and running pending migrations..."
    php artisan migrate --force || true
fi

echo "Laravel application is ready. Starting process: $@"
exec "$@"
