# Stage 1: Install PHP dependencies
FROM composer:2 AS php-builder

WORKDIR /app

# Copy composer files
COPY composer.json composer.lock ./

# Install dependencies (with cache mount for faster rebuilds)
RUN --mount=type=cache,target=/tmp/cache \
    COMPOSER_CACHE_DIR=/tmp/cache composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --prefer-dist \
    --no-scripts

# Stage 2: Build frontend assets
FROM node:20-alpine AS frontend-builder

WORKDIR /app

# Copy package files
COPY package*.json ./

# Install dependencies (with cache mount for faster rebuilds)
RUN --mount=type=cache,target=/root/.npm \
    npm ci

# Copy vendor folder from PHP builder (needed for CSS references)
COPY --from=php-builder /app/vendor ./vendor

# Copy source files needed for build
COPY resources ./resources
COPY vite.config.js ./

# Build assets
RUN npm run build

# Stage 3: Final runtime image
# Using edge repository to get newer busybox that may have QEMU fixes
FROM php:8.2-cli-alpine3.21

# Install only runtime dependencies
# Note: Disable busybox triggers to workaround QEMU emulation issues
# on ARM64 builds (busybox 1.37.0-r29 bug)
RUN apk add --no-cache --no-scripts \
    curl \
    sqlite \
    sqlite-dev \
    su-exec \
    && docker-php-ext-install pdo pdo_sqlite bcmath \
    && rm -rf /var/cache/apk/*

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Copy built assets from frontend builder
COPY --from=frontend-builder /app/public/build ./public/build

# Copy PHP dependencies from php builder
COPY --from=php-builder /app/vendor ./vendor

# Copy entrypoint scripts
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod 755 /usr/local/bin/docker-entrypoint.sh

ENV APP_ENV=production
ENV APP_DEBUG=false
ENV APP_REGISTRATION_ENABLED=false
ENV APP_TIMEZONE=UTC
ENV DB_CONNECTION=sqlite
ENV DB_DATABASE=/app/storage/database.sqlite
ENV LOG_CHANNEL=daily
ENV LOG_LEVEL=info
ENV TRUSTED_PROXIES=*

# App code: read-only for everyone
RUN find /app -type d -exec chmod 755 {} \; \
    && find /app -type f -exec chmod 644 {} \; \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
