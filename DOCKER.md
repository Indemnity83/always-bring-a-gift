# Docker Deployment Guide

Always Bring a Gift (ABAG) can be deployed as a Docker container for easy self-hosting.

## Quick Start

### Using Docker Run

```bash
docker run -d \
  --name abag \
  -p 8000:8000 \
  -v abag-data:/app/storage \
  ghcr.io/indemnity83/always-bring-a-gift:latest
```

Access at `http://localhost:8000` and check logs for the admin password.

### Using Docker Compose

```yaml
services:
  abag:
    image: ghcr.io/indemnity83/always-bring-a-gift:latest
    container_name: abag
    ports:
      - "8000:8000"
    volumes:
      - abag-data:/app/storage
    restart: unless-stopped

volumes:
  abag-data:
```

Start with:
```bash
docker compose up -d
```

### UnRAID Community Applications

1. **Via Community Applications:**
   - Search for "Always Bring a Gift" in Community Applications
   - Click Install and configure the port if needed
   - Click Apply

2. **Manual Template Installation:**
   - Download [unraid-template.xml](unraid-template.xml)
   - Place in `/boot/config/plugins/dockerMan/templates-user/`
   - Refresh Docker page in UnRAID

The template provides:
- **WebUI Port:** 8000 (customizable)
- **AppData Path:** `/mnt/user/appdata/always-bring-a-gift` (auto-configured)
- **PUID/PGID:** 99/100 (UnRAID defaults, hidden in advanced settings)

That's it! Everything else is auto-configured.

## Environment Variables

All environment variables are optional. The application auto-detects URLs and uses sensible defaults.

### Application URL (Optional)

```yaml
environment:
  - APP_URL=https://gifts.example.com
```

**When to set:**
- Using Authentik OAuth (required for callback redirects)
- Behind a reverse proxy with custom domain

**When NOT needed:**
- Direct access via IP or hostname (auto-detected from request)
- Most UnRAID deployments

### User/Group IDs

```yaml
environment:
  - PUID=99    # User ID (default: 1000, UnRAID: 99)
  - PGID=100   # Group ID (default: 1000, UnRAID: 100)
```

Controls file ownership in the `/app/storage` volume. UnRAID users typically use 99:100 (nobody:users).

### Timezone (Optional)

```yaml
environment:
  - TZ=America/New_York
```

Defaults to UTC. The app doesn't display many times to users, so this is rarely needed.

### Amazon Product Integration (Optional)

```yaml
environment:
  - OPENWEB_NINJA_KEY=your_api_key_here
```

Enables fetching product images from Amazon URLs. Get a key from [OpenWeb Ninja API](https://rapidapi.com/developer-omniagent/api/openweb-ninja).

### Authentik SSO (Optional)

```yaml
environment:
  - AUTHENTIK_CLIENT_ID=your-client-id
  - AUTHENTIK_CLIENT_SECRET=your-client-secret
  - AUTHENTIK_BASE_URL=https://authentik.example.com
  - APP_URL=https://gifts.example.com  # Required for OAuth callback
```

See [AUTHENTIK_SETUP.md](AUTHENTIK_SETUP.md) for complete setup instructions.

### Database (Advanced)

By default, uses SQLite in the Docker volume. For PostgreSQL or MySQL:

```yaml
environment:
  - DB_CONNECTION=pgsql
  - DB_HOST=postgres
  - DB_PORT=5432
  - DB_DATABASE=abag
  - DB_USERNAME=abag
  - DB_PASSWORD=secret
```

## Admin User (First Run)

On first run, an admin user is automatically created:
- **Email:** `admin@example.com`
- **Password:** Random password shown in container logs

⚠️ **Important:**
- Password is **only shown once** on first startup
- View with: `docker logs abag | grep Password`
- Change password immediately after first login!

## Data Persistence

The container uses a single volume for all data:

```
/app/storage/
├── database.sqlite    # SQLite database
├── app/               # User uploads (gift images, profile pictures)
├── framework/         # Cache and sessions (files, not database)
├── logs/              # Application logs (also sent to stdout)
└── caddy/             # Caddy server storage (configs, locks, etc.)
```

**What's stored where:**
- **Database:** SQLite file (easily switchable to PostgreSQL/MySQL)
- **Cache:** File-based (not database - better performance)
- **Sessions:** File-based (not database - better performance)
- **Queue:** Database (for reliable job processing)

## Updating

1. Pull latest image:
```bash
docker pull ghcr.io/indemnity83/always-bring-a-gift:latest
```

2. Recreate container:
```bash
docker compose up -d
# or
docker stop abag && docker rm abag && docker run ...
```

Migrations run automatically on container start - no manual intervention needed.

## How It Works

**Container Architecture:**
- **Base Image:** Debian-based FrankenPHP (not Alpine, for better performance)
- **Web Server:** FrankenPHP (production-grade, multi-threaded)
- **Process Manager:** `gosu` drops privileges to PUID:PGID after setup
- **Multi-stage Build:** Minimal runtime image (no build tools, no Node.js)

**Startup Process:**
1. Entrypoint runs as root to create directories
2. Fixes ownership to match PUID:PGID
3. Runs Laravel setup (migrations, seeding, cache optimization)
4. Drops to PUID:PGID and starts FrankenPHP

**Performance Optimizations:**
- Debian (glibc) instead of Alpine (musl) for multi-threaded performance
- File-based sessions/cache instead of database (reduces SQLite contention)
- FrankenPHP instead of php artisan serve (production-ready)
- Optimized environment variables (`GODEBUG=cgocheck=0`, `GOMEMLIMIT`)

## Production Deployment

### Behind Reverse Proxy (Recommended)

**Traefik Example:**
```yaml
services:
  abag:
    image: ghcr.io/indemnity83/always-bring-a-gift:latest
    volumes:
      - abag-data:/app/storage
    environment:
      - APP_URL=https://gifts.example.com
    labels:
      - "traefik.enable=true"
      - "traefik.http.routers.abag.rule=Host(`gifts.example.com`)"
      - "traefik.http.routers.abag.tls.certresolver=letsencrypt"
      - "traefik.http.services.abag.loadbalancer.server.port=8000"
    networks:
      - traefik

networks:
  traefik:
    external: true

volumes:
  abag-data:
```

**Nginx Proxy Manager:**
1. Add proxy host pointing to `abag:8000`
2. Enable SSL with Let's Encrypt
3. Set `APP_URL` to your public domain

**Important for reverse proxies:**
- Set `APP_URL` if using OAuth/SSO
- Ensure `X-Forwarded-*` headers are passed (Laravel auto-configures trusted proxies)

## Troubleshooting

### View Logs

**Container logs (FrankenPHP startup and server logs):**
```bash
# Follow logs in real-time
docker logs -f abag

# Last 100 lines
docker logs --tail 100 abag

# Logs since 1 hour ago
docker logs --since 1h abag
```

**Application logs (Laravel errors, info, debug):**
```bash
# View most recent Laravel logs
docker exec abag tail -f /app/storage/logs/laravel.log

# View last 100 lines
docker exec abag tail -100 /app/storage/logs/laravel.log
```

### Access Container Shell

```bash
docker exec -it abag sh
```

### Reset Admin Password

```bash
docker exec -it abag php artisan tinker
```
Then run:
```php
User::where('email', 'admin@example.com')->first()->update(['password' => Hash::make('newpassword')]);
```

### Permission Issues

If you see permission errors:
1. Check PUID/PGID match your host user
2. Verify volume ownership: `docker exec abag ls -la /app/storage`
3. Fix manually if needed: `docker exec abag chown -R 1000:1000 /app/storage`

### Assets Not Loading

Assets are auto-detected from the HTTP request, so this should work automatically. If you have issues:
1. Check you're not setting `ASSET_URL` (removed - not needed)
2. Verify reverse proxy passes Host header correctly
3. Check browser console for the actual asset URLs

### Performance Issues

If the app feels slow:
1. Verify using Debian image (not Alpine) - check with `docker image inspect`
2. Check logs aren't showing errors
3. Verify not hitting SQLite locks (cache/sessions should be file-based)
4. Monitor with `docker stats abag`

## Building from Source

Multi-stage build minimizes final image size:

```bash
docker build -t always-bring-a-gift:local .
```

**Build stages:**
1. **PHP Builder:** Installs Composer dependencies (discarded)
2. **Frontend Builder:** Builds Vite assets with Node.js (discarded)
3. **Runtime:** Debian + FrankenPHP + compiled assets only

Final image contains no build tools, resulting in smaller size and better security.

## Security Recommendations

1. ✅ **Change default password** immediately after first login
2. ✅ **Use HTTPS** via reverse proxy (Traefik, Nginx, Caddy)
3. ✅ **Regular backups** of the Docker volume
4. ✅ **Keep updated** - pull latest image regularly
5. ✅ **Enable 2FA** in user settings for added security
6. ⚠️ **Don't expose port 8000** directly to internet - use reverse proxy

## Multi-Architecture Support

Images available for:
- `linux/amd64` (x86_64 - Intel/AMD)
- `linux/arm64` (ARM64 - Apple Silicon, Raspberry Pi 4+)

Docker automatically pulls the correct architecture.
