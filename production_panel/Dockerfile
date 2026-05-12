# ─── Stage 1: Node.js asset build ───────────────────────────────────────────
FROM node:20-alpine AS assets

WORKDIR /app

COPY package*.json ./
RUN if [ -f package-lock.json ]; then \
        npm ci --ignore-scripts; \
    else \
        npm install --ignore-scripts; \
    fi

COPY . .
RUN npm run build

# ─── Stage 2: PHP + Laravel production ───────────────────────────────────────
FROM php:8.2-fpm-alpine AS app

# Install system dependencies
RUN apk add --no-cache \
    bash \
    nginx \
    supervisor \
    postgresql-client \
    git \
    curl \
    libpq-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    libzip-dev \
    oniguruma-dev \
    icu-dev \
    && docker-php-ext-configure gd --with-jpeg \
    && docker-php-ext-install \
        pdo_pgsql \
        mbstring \
        zip \
        exif \
        bcmath \
        gd \
        pcntl \
        intl \
        opcache

# Install Redis PHP extension
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy source
COPY . .

# Copy built assets from stage 1
COPY --from=assets /app/public/build ./public/build

# Install PHP dependencies (production only)
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader \
    --no-scripts

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# PHP-FPM config
COPY docker/php/php.ini /usr/local/etc/php/conf.d/app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Nginx config
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Supervisor config
COPY docker/supervisor/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# OPcache warmup
RUN docker-php-ext-enable opcache

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
