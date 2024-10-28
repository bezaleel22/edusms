#!/bin/sh

echo "Starting services..."

# Start PHP-FPM
service php8.1-fpm start

# Wait for PHP-FPM to be ready
while ! nc -z localhost 9000; do
    echo "Waiting for PHP-FPM..."
    sleep 1
done

# Run Artisan commands
echo "Running Artisan commands..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan key:generate

# Start Nginx in the foreground
nginx -g "daemon off;" &

echo "Ready."

# Optionally, handle signals for graceful shutdown
trap 'kill $(jobs -p)' EXIT
tail -s 1 /var/log/nginx/*.log -f | grep --color -P 'error|'
