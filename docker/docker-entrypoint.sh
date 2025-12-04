#!/bin/sh
set -e

echo "🚀 Starting Always Bring a Gift (ABAG) container..."

if [ -z "$APP_URL" ]; then
    echo "🛑 Error: The APP_URL environment variable is required."
    exit 1
fi

# Ensure storage directory structure exists
echo "📁 Ensuring storage directory structure..."
mkdir -p storage/app/public
mkdir -p storage/framework/cache/data
mkdir -p storage/framework/sessions
mkdir -p storage/framework/testing
mkdir -p storage/framework/views
mkdir -p storage/logs
mkdir -p storage/caddy

echo "💻 Setup Environment:"
php artisan config:clear --quiet
export APP_TIMEZONE="${TZ:-UTC}"
export ASSET_URL="$APP_URL"
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

# Generate app key if not set
APP_KEY_VALUE=$(grep -E '^APP_KEY=' .env | cut -d '=' -f 2-)
if [ -z "$APP_KEY_VALUE" ]; then
    echo "🔑 Generating application key..."
    php artisan key:generate --force
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

# Handle PUID/PGID for Unraid and similar systems
PUID=${PUID:-1000}
PGID=${PGID:-1000}

# Set ownership to the configured user
echo "🔒 Fixing ownership on writable dirs for $PUID:$PGID..."
for path in storage bootstrap/cache; do
  if [ -d "$path" ]; then
    # Only touch entries that don't already match PUID/PGID
    find "$path" \( ! -user "$PUID" -o ! -group "$PGID" \) -exec chown "$PUID:$PGID" {} +
  fi
done

echo "🔒 Ensuring permissions for writable dirs..."
find storage bootstrap/cache -type d -exec chmod 775 {} \;
find storage bootstrap/cache -type f -exec chmod 664 {} \;

echo "✅ Application ready!"

# Start FrankenPHP server as the configured user with Caddyfile
exec gosu "$PUID:$PGID" frankenphp run --config /etc/caddy/Caddyfile
