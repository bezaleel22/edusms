# Build Stage
FROM php:8.2-fpm-alpine AS builder

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
    && composer dump-autoload --no-scripts \
    && find . -type f -exec chmod 644 {} \; \
    && find . -type d -exec chmod 775 {} \; \
    && chmod -R 777 storage bootstrap/cache/ \
    && php artisan route:clear \
    && php artisan config:clear \
    && php artisan cache:clear \
    && php artisan key:generate

# Final Stage
FROM php:8.2-fpm-alpine

# Define environment variables
ENV WORKDIR=/var/www/html
ENV DOCUMENT_ROOT=${WORKDIR}
ENV LARAVEL_PROCS_NUMBER=1
ENV USER=www
ENV UID=1000
ENV GROUP_NAME=www-data

# Install necessary packages and PHP extensions
RUN apk update && \
    apk add --no-cache \
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
    # Install PHP extensions
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath mysqli opcache zip intl \
    && docker-php-source delete \
    # Move to production php.ini
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    # Clean up
    && rm -rf /var/cache/apk/*

# Create user and set permissions
RUN addgroup -g ${UID} ${GROUP_NAME} \
    && adduser -u ${UID} -G ${GROUP_NAME} -s /bin/sh -D ${USER} \
    && mkdir -p /home/${USER}/.composer \
    && chown -R ${USER}:${GROUP_NAME} /home/${USER}

# Copy and set permissions for entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set up Nginx and php configuration (make sure this file exists and is properly configured)
COPY default.conf /etc/nginx/conf.d/default.conf
COPY ./local.ini "$PHP_INI_DIR/local.ini"

# Set working directory
WORKDIR ${DOCUMENT_ROOT}

# Copy application files and vendor directory from the build stage
COPY --from=builder /var/www/html /var/www/html

# Set the correct permissions
RUN chown -R ${USER}:${GROUP_NAME} ${DOCUMENT_ROOT}

EXPOSE 80

# Set the entry point
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]

# Use non-root user
USER ${USER}
