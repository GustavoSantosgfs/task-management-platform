#!/bin/sh
set -e

echo "==> Starting Laravel application..."

# Wait for MySQL to be ready using PHP (more reliable than mysql client)
if [ -n "$DB_HOST" ]; then
    echo "==> Waiting for MySQL at $DB_HOST:${DB_PORT:-3306}..."
    max_tries=30
    counter=0

    until php -r "
        try {
            new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE'),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD'),
                [PDO::ATTR_TIMEOUT => 5]
            );
            exit(0);
        } catch (PDOException \$e) {
            fwrite(STDERR, 'PDO Error: ' . \$e->getMessage() . PHP_EOL);
            exit(1);
        }
    "; do
        counter=$((counter + 1))
        if [ $counter -gt $max_tries ]; then
            echo "==> Error: MySQL not available after $max_tries attempts"
            echo "==> Debug: DB_HOST=$DB_HOST, DB_PORT=${DB_PORT:-3306}, DB_DATABASE=$DB_DATABASE, DB_USERNAME=$DB_USERNAME"
            exit 1
        fi
        echo "==> MySQL not ready yet, waiting... ($counter/$max_tries)"
        sleep 2
    done
    echo "==> MySQL is ready!"
fi

# Create storage directories if they don't exist
echo "==> Ensuring storage directories exist..."
mkdir -p /var/www/html/storage/logs
mkdir -p /var/www/html/storage/framework/cache
mkdir -p /var/www/html/storage/framework/sessions
mkdir -p /var/www/html/storage/framework/views
mkdir -p /var/www/html/bootstrap/cache

# Set proper permissions
echo "==> Setting permissions..."
chown -R www-data:www-data /var/www/html/storage
chown -R www-data:www-data /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Generate app key if not set
if [ -z "$APP_KEY" ]; then
    echo "==> Generating application key..."
    php artisan key:generate --force
fi

# Clear and cache configuration
echo "==> Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations if AUTO_MIGRATE is set
if [ "$AUTO_MIGRATE" = "true" ]; then
    echo "==> Running database migrations..."
    php artisan migrate --force
fi

# Seed database if AUTO_SEED is set (useful for first deployment)
if [ "$AUTO_SEED" = "true" ]; then
    echo "==> Seeding database..."
    php artisan db:seed --force
fi

echo "==> Application ready!"

# Execute the main command
exec "$@"
