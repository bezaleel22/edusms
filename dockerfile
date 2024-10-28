# Build Stage
FROM php:8.1-fpm-alpine AS builder

# Copy Composer from the Composer image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist \
    --optimize-autoloader \
    && composer dump-autoload --no-scripts \
    # Set proper permissions for files and directories
    && find . -type f -exec chmod 644 {} \; \
    && find . -type d -exec chmod 755 {} \; \
    # Ensure writable permissions for storage and cache directories
    && chmod -R 775 storage bootstrap/cache/ \
    && php artisan route:clear \
    && php artisan config:clear \
    && php artisan cache:clear \
    && php artisan key:generate

# Final Stage
FROM php:8.1-fpm-alpine

# Define environment variables
ENV WORKDIR=/var/www/html
ENV DOCUMENT_ROOT=${WORKDIR}
ENV LARAVEL_PROCS_NUMBER=1
ENV USER=www
ENV UID=1000
ENV GROUP_NAME=www-data

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
    icu-dev \
    freetype-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath mysqli opcache zip intl \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && rm -rf /var/cache/apk/*

# Create user and set permissions
RUN addgroup -g ${UID} ${GROUP_NAME} \
    && adduser -u ${UID} -G ${GROUP_NAME} -s /bin/sh -D ${USER} \
    && mkdir -p /home/${USER}/.composer \
    && chown -R ${USER}:${GROUP_NAME} /home/${USER}

# Copy and set permissions for entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set up Nginx and PHP configuration
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/local.ini "$PHP_INI_DIR/local.ini"

# Set working directory
WORKDIR ${DOCUMENT_ROOT}

# Copy application files and vendor directory from the build stage
COPY --from=builder /var/www/html /var/www/html

# Set the correct permissions for writable directories
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R ${USER}:${GROUP_NAME} ${DOCUMENT_ROOT}

EXPOSE 3000

# Set the entry point
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Use non-root user
USER ${USER}
