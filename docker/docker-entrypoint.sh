#!/bin/sh
set -e

echo "🚀 Starting Always Bring a Gift (ABAG) container..."

# Handle PUID/PGID for Unraid and similar systems (set early for correct ownership)
PUID=${PUID:-1000}
PGID=${PGID:-1000}

# Ensure user/group exist for gosu
if ! getent group "$PGID" >/dev/null 2>&1; then
    groupadd -g "$PGID" abag
fi

if ! id -u "$PUID" >/dev/null 2>&1; then
    useradd -u "$PUID" -g "$PGID" -m -s /bin/sh abag
fi

RUN_USER=$(getent passwd "$PUID" | cut -d: -f1)
RUN_USER=${RUN_USER:-abag}

# Graceful shutdown for background workers
shutdown() {
    echo "🛑 Shutting down background processes..."
    if [ -n "${scheduler_pid:-}" ]; then
        kill "$scheduler_pid" 2>/dev/null || true
    fi
    if [ -n "${worker_pid:-}" ]; then
        kill "$worker_pid" 2>/dev/null || true
    fi
    if [ -n "${server_pid:-}" ]; then
        kill "$server_pid" 2>/dev/null || true
    fi
    wait || true
    exit 0
}

trap shutdown INT TERM

# Ensure storage directory structure exists with correct ownership
echo "📁 Ensuring storage directory structure..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/caddy

# Set permissions early so log files created during setup are writable
echo "🔒 Setting storage permissions..."
chown -R "$PUID:$PGID" storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

echo "💻 Setup Environment:"
php artisan config:clear --quiet
export APP_TIMEZONE="${TZ:-UTC}"
php artisan config:show app.env
php artisan config:show app.debug
php artisan config:show app.url
php artisan config:show app.timezone

# Run package discovery (after all directories are ready)
echo "📦 Running package discovery..."
php artisan package:discover

# Create .env file if it doesn't exist (needed for key:generate)
if [ ! -f .env ]; then
    echo "📝 Creating .env file from .env.example..."
    cp .env.example .env
fi

# If APP_KEY is provided via env, ensure it is persisted into .env before generation attempts
if [ -n "$APP_KEY" ]; then
    if grep -qE '^APP_KEY=' .env; then
        sed -i "s/^APP_KEY=.*/APP_KEY=${APP_KEY}/" .env
    else
        echo "APP_KEY=${APP_KEY}" >> .env
    fi
fi

# Generate app key if not set
APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env | cut -d '=' -f 2-)
if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force || true

    APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env | cut -d '=' -f 2-)
    if [ -z "$APP_KEY_VALUE" ]; then
        echo "⚠️ Artisan key:generate failed, generating a key manually..."
        GENERATED_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));")
        if grep -qE '^APP_KEY=' .env; then
            sed -i "s/^APP_KEY=.*/APP_KEY=${GENERATED_KEY}/" .env
        else
            echo "APP_KEY=${GENERATED_KEY}" >> .env
        fi
    fi
fi

# Create SQLite database file
if [ ! -f "$DB_DATABASE" ]; then
    echo "📚 Creating SQLite database file..."
    touch "$DB_DATABASE"
    php artisan db:show
fi

# Create symbolic link for storage if it doesn't exist
if [ ! -L "/app/public/storage" ]; then
    echo "🔗 Creating storage symbolic link..."
    php artisan storage:link
fi

# Run migrations
echo "📦 Running database migrations..."
php artisan migrate --force

# Check if we need to run the seeder (check if EventTypes table is empty)
echo "🌱 Checking if initial seeding is needed..."
SEED_NEEDED=$(php artisan tinker --execute="echo App\\Models\\EventType::count();" 2>/dev/null || echo "0")

if [ "$SEED_NEEDED" = "0" ]; then
    echo "🌱 Running initial database seeder..."
    php artisan db:seed --force
    echo "✅ Database seeded successfully"
else
    echo "✅ Database already seeded, skipping..."
fi

# Cache configuration and routes for better performance
echo "✨ Optimizing application..."
php artisan optimize

# Final permission check (in case new files were created during setup)
echo "🔒 Final permission check..."
chown -R "$PUID:$PGID" storage bootstrap/cache
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;
find bootstrap/cache -type f -exec chmod 664 {} \;

# Ensure log files are writable by group
chmod 666 storage/logs/*.log 2>/dev/null || true

echo "✅ Application ready!"

# Start Laravel scheduler in background as the configured user
(
  echo "⏱️  Starting scheduler loop..."
  while true; do
    gosu "$RUN_USER:$PGID" php artisan schedule:run --no-ansi --quiet || true
    sleep 60
  done
) &
scheduler_pid=$!

# Start queue worker in background for processing notifications (with restart loop)
(
  echo "📬 Starting queue worker..."
  while true; do
    gosu "$RUN_USER:$PGID" php artisan queue:work --queue=notifications --sleep=3 --tries=3 --max-time=3600 || true
    echo "📬 Queue worker restarting..."
    sleep 5
  done
) &
worker_pid=$!

# Start FrankenPHP server as the configured user with Caddyfile
gosu "$RUN_USER:$PGID" frankenphp run --config /etc/caddy/Caddyfile &
server_pid=$!
wait "$server_pid"
