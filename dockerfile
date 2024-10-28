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

# # Add composer
# COPY composer.json composer.lock ./
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist\
    --optimize-autoloader

# Create user and set permissions
RUN adduser -u ${UID} -G ${GROUP_NAME} -s /bin/sh -D ${USER} \
    && chown -R ${USER}:${GROUP_NAME} ${DOCUMENT_ROOT} \
    && chmod -R 775 ${DOCUMENT_ROOT}/storage ${DOCUMENT_ROOT}/bootstrap/cache

# Set up Nginx configuration (if needed)
COPY docker/default.conf /etc/nginx/conf.d/default.conf
COPY docker/local.ini "$PHP_INI_DIR/local.ini"

# Expose the port that Nginx will use
EXPOSE 3000

# Entrypoint and user setup
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
USER ${USER}
