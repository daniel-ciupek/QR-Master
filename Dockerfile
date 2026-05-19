# ============================================================
# Stage: base — PHP 8.3 + FrankenPHP + extensions
# ============================================================
FROM dunglas/frankenphp:1-php8.3-alpine AS base

LABEL maintainer="QR-Master"

RUN install-php-extensions \
    pdo_pgsql \
    pgsql \
    redis \
    gd \
    intl \
    zip \
    bcmath \
    pcntl \
    opcache \
    exif \
    calendar \
    sockets

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# ============================================================
# Stage: dev — lokalny development (kod montowany jako volume)
# ============================================================
FROM base AS dev

ENV APP_ENV=local
ENV COMPOSER_ALLOW_SUPERUSER=1

RUN apk add --no-cache \
    git \
    curl \
    unzip \
    nodejs \
    npm

# ============================================================
# Stage: prod — obraz produkcyjny (kod skopiowany, zoptymalizowany)
# ============================================================
FROM base AS prod

ENV APP_ENV=production
ENV COMPOSER_ALLOW_SUPERUSER=1

COPY . .

RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan event:cache \
    && php artisan view:cache

EXPOSE 8000

CMD ["php", "artisan", "octane:start", "--server=frankenphp", "--host=0.0.0.0", "--port=8000"]
