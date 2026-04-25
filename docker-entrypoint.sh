#!/bin/bash
set -e

echo "Starting OpenGRC container..."

# Wait for database to be ready (if using external database)
if [ -n "$DB_HOST" ]; then
    echo "Waiting for database connection..."
    max_attempts=30
    attempt=0
    while [ $attempt -lt $max_attempts ]; do
        if php -r "
            try {
                \$dsn = 'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE');
                new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
                echo 'ok';
            } catch (Exception \$e) {
                exit(1);
            }
        " 2>/dev/null | grep -q "ok"; then
            echo "Database connected."
            break
        fi
        attempt=$((attempt + 1))
        echo "Waiting for database... (attempt $attempt/$max_attempts)"
        sleep 2
    done
    if [ $attempt -eq $max_attempts ]; then
        echo "Warning: Could not connect to database after $max_attempts attempts. Continuing anyway..."
    fi
fi

# Run database migrations
echo "Running database migrations..."
php artisan migrate --force

# Seed and create admin user only on first run (check if users table is empty)
USER_COUNT=$(php -r "
    \$dsn = 'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306) . ';dbname=' . getenv('DB_DATABASE');
    \$pdo = new PDO(\$dsn, getenv('DB_USERNAME'), getenv('DB_PASSWORD'));
    echo \$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
" 2>/dev/null || echo "0")

if [ "$USER_COUNT" = "0" ]; then
    echo "First run detected - seeding database and creating admin user..."
    php artisan db:seed --class=SettingsSeeder --force
    php artisan opengrc:create-user "${ADMIN_EMAIL:-admin@opengrc.local}" "${ADMIN_PASSWORD:-admin123}"
    php artisan db:seed --class=RolePermissionSeeder --force
    php artisan settings:set general.name "${APP_NAME:-OpenGRC}"
    php artisan settings:set general.url "${APP_URL:-http://localhost}"
    php artisan settings:set storage.driver private
    php artisan storage:link
fi

# Clear and cache config for production
echo "Caching configuration..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Ensure storage directories have correct permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create PHP-FPM run directory if it doesn't exist
mkdir -p /run/php

# Start cron daemon
echo "Starting cron..."
service cron start

# Start PHP-FPM
echo "Starting PHP-FPM..."
service php8.3-fpm start

# Start Apache in foreground
echo "Starting Apache..."
exec apache2ctl -D FOREGROUND
