# PHP dependencies
FROM composer:2.6 AS vendor
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist && \
    composer dump-autoload --optimize

# Frontend build
FROM node:20-alpine AS frontend
WORKDIR /app
COPY . .
COPY --from=vendor /app/vendor/ vendor/ 
RUN npm ci && \
    npm run build

# Final production image
FROM alpine:3.19

# Set Laravel logging to stderr
ENV LOG_CHANNEL=stderr \
    LOG_DEPRECATIONS_CHANNEL=stderr \
    LOG_LEVEL=debug

# Install system dependencies and PHP
RUN apk add --no-cache \
    nginx \
    supervisor \
    su-exec \
    php83 \
    php83-fpm \
    php83-pdo \
    php83-pdo_mysql \
    php83-pdo_pgsql \
    php83-opcache \
    php83-tokenizer \
    php83-session \
    php83-fileinfo \
    php83-curl \
    php83-dom \
    php83-xml \
    php83-xmlwriter \
    php83-mbstring \
    php83-zip && \
    ln -s /usr/bin/php83 /usr/bin/php && \
    ln -s /usr/sbin/php-fpm83 /usr/sbin/php-fpm

# Create service users and group
RUN addgroup -S quickdrop && \
    adduser -S -G quickdrop -h /var/www/html -s /sbin/nologin quickdrop && \
    adduser -S -G quickdrop -H -h /var/lib/php -s /sbin/nologin php && \
    adduser nginx quickdrop

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} \
    /var/www/html/storage/logs \
    /var/www/html/bootstrap/cache \
    /var/lib/nginx \
    /var/log/nginx \
    /var/log/php83 \
    /var/lib/php/session && \
    chown -R nginx:quickdrop /var/lib/nginx /var/log/nginx && \
    chown -R php:quickdrop /var/log/php83 /var/lib/php/session && \
    chown -R quickdrop:quickdrop /var/www/html && \
    chmod 755 /var/www/html

# Configure PHP
COPY .docker/php.ini /etc/php83/conf.d/custom.ini
COPY .docker/php-fpm.conf /etc/php83/php-fpm.d/www.conf

# Configure nginx
COPY .docker/nginx.conf /etc/nginx/
COPY .docker/nginx-laravel.conf /etc/nginx/conf.d/default.conf

# Configure supervisor
COPY .docker/supervisord.ini /etc/supervisor.d/supervisord.ini

# Set working directory
WORKDIR /var/www/html

# Copy application files and build artifacts
COPY --chown=quickdrop:quickdrop . .
COPY --chown=quickdrop:quickdrop --from=vendor /app/vendor/ vendor/
COPY --chown=quickdrop:quickdrop --from=frontend /app/public/build/ public/build/

# Set specific directory permissions
RUN chmod -R 775 storage bootstrap/cache

# Copy and set entrypoint
COPY .docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]