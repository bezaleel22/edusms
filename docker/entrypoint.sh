#!/bin/sh
set -e

init() {
    if [ ! -f "vendor/autoload.php" ]; then
        composer install \
            --no-interaction \
            --no-plugins \
            --no-scripts \
            --no-dev \
            --prefer-dist
        
        composer dump-autoload --no-scripts

        find . -type f -exec chmod 644 {} \;
        find . -type d -exec chmod 775 {} \;
        chmod -R 777 storage bootstrap/cache/
        
        php artisan route:clear
        php artisan config:clear
        php artisan cache:clear
        php artisan key:generate
    fi
}

init
php-fpm