# syntax=docker/dockerfile:1.7
#
# MagicAI v11.20 — production baseline for Cloudflare Containers
# Single-container initial deployment: Nginx + PHP-FPM + Queue worker + Scheduler
# Scale-out topology (later): magicai-web / magicai-worker / magicai-scheduler

############################
# 1) PHP dependencies (composer)
############################
FROM composer:2 AS vendor

WORKDIR /build

COPY composer.json composer.lock ./
COPY packages ./packages

RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs


############################
# 3) Production runtime
############################
FROM php:8.4-fpm-alpine AS app

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info

# Alpine packages: nginx, supervisor, runtime tools (ffmpeg for extension/voice
# pipelines, poppler-utils for spatie/pdf-to-text).
# $PHPIZE_DEPS + icu-dev are build-only dependencies (removed at the end).
RUN apk add --no-cache \
        nginx \
        supervisor \
        curl \
        zip \
        unzip \
        git \
        ffmpeg \
        poppler-utils \
        libpng \
        libjpeg-turbo \
        libwebp \
        libzip \
        icu-dev \
        postgresql-dev \
        oniguruma-dev \
        libzip-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        libwebp-dev \
        freetype-dev \
        libxml2-dev \
        $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        xml \
        zip \
        intl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del $PHPIZE_DEPS \
    && apk add --no-cache icu libpq postgresql17-client oniguruma libzip libpng libjpeg-turbo libwebp freetype libxml2

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www

############################
# Application
############################

COPY . .

COPY --from=vendor /build/vendor ./vendor

# Reuse the vendor tree built in stage 1; only regenerate the optimized
# autoloader here (offline, deterministic, no second composer install).
# The magicai-healthy path-repo ships without resources/views but declares
# hasViews() — view:cache scans that path, so an empty dir keeps it happy
# (the app itself never renders healthy:: views).
RUN mkdir -p vendor/magicai/magicai-healthy/resources/views \
    && composer dump-autoload \
    --optimize \
    --classmap-authoritative \
    --no-scripts \
    --no-interaction

############################
# Runtime directories
############################

RUN mkdir -p \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        storage/app/public \
        storage/app/r2-cache \
        bootstrap/cache \
        public/uploads \
        /run/nginx \
    && chown -R www-data:www-data \
        storage \
        bootstrap/cache \
        public/uploads

############################
# PHP
############################

RUN cat > /usr/local/etc/php/conf.d/production.ini <<'EOF'
memory_limit=512M
max_execution_time=300
max_input_time=300
post_max_size=256M
upload_max_filesize=256M
max_file_uploads=50
realpath_cache_size=4096K
realpath_cache_ttl=600
EOF

# CLI (artisan route:cache serializes large closures) — separate ini from FPM
RUN printf 'memory_limit=-1\n' > /usr/local/etc/php/php-cli.ini

############################
# PHP-FPM (listen on loopback only; Nginx is the only entry point)
############################

RUN sed -i 's|^listen = .*|listen = 127.0.0.1:9000|' \
    /usr/local/etc/php-fpm.d/www.conf

############################
# Nginx
############################

COPY docker/nginx.conf /etc/nginx/nginx.conf

############################
# Supervisor
############################

COPY docker/supervisord.conf /etc/supervisord.conf

############################
# Entrypoint
############################

COPY docker/entrypoint.sh /docker-entrypoint.sh

RUN chmod +x /docker-entrypoint.sh

EXPOSE 8080

# Container-level healthcheck (Cloudflare Containers uses the same endpoint
# in its own healthcheck configuration: GET /api/health)
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD curl -fsS http://127.0.0.1:8080/api/health || exit 1

ENTRYPOINT ["/docker-entrypoint.sh"]
