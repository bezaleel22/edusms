FROM php:8.2-fpm-alpine

ENV user=www
ENV uid=1000

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
RUN apk add --no-cache curl zip unzip shadow \
    libpng-dev \ 
    libzip-dev	\
    libjpeg-turbo-dev \
    libpng-dev \
    libxml2-dev \
    icu-dev \
    freetype-dev 

RUN IPE_GD_WITHOUTAVIF=1 docker-php-ext-install -j$(nproc) gd pdo_mysql bcmath mysqli opcache zip intl \
    && docker-php-source delete

RUN useradd -G www-data,root -u $uid -d /home/$user $user
RUN mkdir -p /home/$user/.composer && \
    chown -R $user:$user /home/$user

COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

WORKDIR /var/www/html
CMD entrypoint.sh
USER $USER