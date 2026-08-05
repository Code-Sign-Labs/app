FROM php:8.2-fpm-alpine

LABEL maintainer="CodeSign"
LABEL description="PHP 8.2-FPM for CodeSign"
# GD + YAML + BZ2 + ZIP + ICU + MBSTRING + XML dependencies
RUN apk add --no-cache \
    libpng-dev \
    freetype-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    libxml2-dev \
    yaml-dev \
    bzip2-dev \
    zlib-dev \
    $PHPIZE_DEPS

# Native PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        gd \
        zip \
        exif \
        intl \
        mbstring \
        opcache \
        xml \
        dom \
        bcmath \
        bz2 \
    && docker-php-ext-enable \
        pdo_mysql \
        opcache \
        bz2

# YAML via PECL
RUN pecl install yaml \
    && docker-php-ext-enable yaml

RUN apk add --no-cache mysql-client

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY docker/php/php.ini /usr/local/etc/php/conf.d/custom.ini

RUN addgroup -g 1000 appgroup && \
    adduser -u 1000 -G appgroup -s /bin/sh -D appuser

# CRON
RUN apk add --no-cache busybox-suid \
        && mkdir -p /var/spool/cron/crontabs \
        && chmod 1777 /var/spool/cron/crontabs \
        && touch /var/log/cron.log \
        && chown -R appuser:appgroup /var/log/cron.log /var/spool/cron/crontabs

WORKDIR /var/www/html

RUN chown -R appuser:appgroup /var/www/html

# ============================================
# Entrypoint
# ============================================
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

USER appuser

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]