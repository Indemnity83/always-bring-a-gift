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

# Stage 3: Final runtime image with FrankenPHP
# Using Debian-based image instead of Alpine for better performance
# See: https://frankenphp.dev/docs/performance/#binary-selection
FROM dunglas/frankenphp:1-php8.2

# Install only runtime dependencies
# pdo_sqlite, bcmath, and curl are already included in base image
RUN apt-get update && apt-get install -y --no-install-recommends \
    gosu \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /app

# Copy application files
COPY . .

# Copy built assets from frontend builder
COPY --from=frontend-builder /app/public/build ./public/build

# Copy PHP dependencies from php builder
COPY --from=php-builder /app/vendor ./vendor

# Copy Caddyfile configuration
COPY docker/Caddyfile /etc/caddy/Caddyfile
RUN chmod 644 /etc/caddy/Caddyfile

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

# Use file-based drivers to reduce SQLite contention
ENV SESSION_DRIVER=file
ENV CACHE_STORE=file

# FrankenPHP performance optimizations
# See: https://frankenphp.dev/docs/performance/
ENV GODEBUG=cgocheck=0
ENV GOMEMLIMIT=512MiB

# App code: read-only for everyone
RUN find /app -type d -exec chmod 755 {} \; \
    && find /app -type f -exec chmod 644 {} \; \
    && chmod -R 775 storage bootstrap/cache

EXPOSE 8000

HEALTHCHECK --interval=30s --timeout=3s --start-period=40s --retries=3 \
    CMD curl -f http://localhost:8000/up || exit 1

ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]
