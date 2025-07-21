#!/bin/sh

# Set proper permissions (run as root)
chown -R www:www /var/www/html/storage
chown -R www:www /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

# Create nginx directories
mkdir -p /var/log/nginx /var/run/nginx
chmod -R 755 /var/log/nginx /var/run/nginx

# Wait for database to be ready
echo "Waiting for database connection..."
while ! nc -z postgres 5432; do
  sleep 1
done
echo "Database is ready!"

# Copy environment file if it doesn't exist
if [ ! -f /var/www/html/.env ]; then
    cp /var/www/html/.env.example /var/www/html/.env
    echo "Created .env file from .env.example"
fi

# Generate application key if not set
if ! grep -q "APP_KEY=base64:" /var/www/html/.env; then
    php artisan key:generate --force
    echo "Generated new application key"
fi

# Clear and cache configuration
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Publish Livewire assets - CRITICAL for Docker deployment
php artisan vendor:publish --force --tag=livewire:assets
echo "Published Livewire assets"

# Publish Filament assets - CRITICAL for CSS/JS loading
php artisan filament:assets
echo "Published Filament assets"

# Run database migrations
php artisan migrate --force

# Seed the database (if needed) - only if tables are empty
php artisan db:seed --force --class=DatabaseSeeder

# Cache configuration for better performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Create symbolic link for storage if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Set final permissions
chown -R www:www /var/www/html
find /var/www/html -type f -exec chmod 644 {} \;
find /var/www/html -type d -exec chmod 755 {} \;
chmod -R 775 /var/www/html/storage
chmod -R 775 /var/www/html/bootstrap/cache

echo "Laravel application is ready!"

# Start supervisor (which manages nginx, php-fpm, and Laravel queue)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
