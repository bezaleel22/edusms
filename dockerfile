# Use the PHP 8.1 FPM Alpine image as the base
FROM php:8.1-fpm-alpine

# Define environment variables
ENV DOCUMENT_ROOT=/var/www/html
ENV LARAVEL_PROCS_NUMBER=1
ENV USER=www
ENV UID=1000
ENV GROUP_NAME=www-data

# Set the working directory
WORKDIR ${DOCUMENT_ROOT}

# Install necessary packages and PHP extensions
RUN apk add --no-cache --update \
    nginx \
    curl \
    zip \
    unzip \
    shadow \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libxml2-dev \
    libzip-dev \  
    icu-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath mysqli opcache zip intl \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/cache/apk/*

COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install Composer dependencies
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader

COPY . .

# Create user and set permissions
RUN adduser -u ${UID} -G ${GROUP_NAME} -s /bin/sh -D ${USER} \
    && mkdir -p /home/${USER}/.composer \
    && chown -R ${USER}:${GROUP_NAME} /home/${USER} \
    && chown -R ${USER}:${GROUP_NAME} ${DOCUMENT_ROOT}\
    && chmod -R 775 storage bootstrap/cache 

# Set up Nginx configuration (if needed)
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/local.ini "$PHP_INI_DIR/local.ini"

# Set the correct permissions for writable directories
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R ${USER}:${GROUP_NAME} ${DOCUMENT_ROOT}

# Expose the port that Nginx will use
EXPOSE 3000

# Set the entry point
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Use non-root user
USER ${USER}
