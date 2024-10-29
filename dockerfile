FROM webdevops/php-nginx:8.1-alpine
ENV DOCUMENT_ROOT=/var/www/html
ENV WEB_DOCUMENT_ROOT ${DOCUMENT_ROOT}/public
ENV PHP_DISMOD=bz2,calendar,exiif,ffi,intl,gettext,ldap,imap,pdo_pgsql,pgsql,soap,sockets,sysvmsg,sysvsm,sysvshm,shmop,apcu,vips,yaml,mongodb,amqp

ENV APP_ENV production
WORKDIR ${DOCUMENT_ROOT}

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

# Copy Composer binary from the Composer official Docker image
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --no-dev \
    --prefer-dist\
    --optimize-autoloader

RUN chown -R application:application . \
    && find . -type d -exec chmod 755 {} \; \
    && find . -type f -exec chmod 644 {} \; \
    && chmod -R 775 ${DOCUMENT_ROOT}/storage ${DOCUMENT_ROOT}/bootstrap/cache ${DOCUMENT_ROOT}/public/uploads \   