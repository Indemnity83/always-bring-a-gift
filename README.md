<div align="center">
  <img src="public/favicon.svg" width="80" height="80" alt="Always Bring a Gift Logo">

  # Always Bring a Gift (ABAG)

  **Never forget another birthday, anniversary, or special occasion**

  [![Tests](https://github.com/Indemnity83/always-bring-a-gift/actions/workflows/tests.yml/badge.svg)](https://github.com/Indemnity83/always-bring-a-gift/actions/workflows/tests.yml)
  [![Build](https://github.com/Indemnity83/always-bring-a-gift/actions/workflows/build.yml/badge.svg)](https://github.com/Indemnity83/always-bring-a-gift/actions/workflows/build.yml)
  [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

</div>

## About

Always Bring a Gift (ABAG) is a thoughtful gift-giving companion that helps you remember important dates and track gift ideas for the special people in your life. Whether it's birthdays, anniversaries, holidays, or other celebrations, ABAG ensures you're always prepared with the perfect gift.

## Features

- 🎂 **Event Management** - Track birthdays, anniversaries, holidays, and custom events
- 🔁 **Recurring Events** - Automatic reminders for yearly celebrations
- 👥 **Person Management** - Keep profiles with photos and event associations
- 💡 **Gift Ideas** - Save and organize gift ideas with Amazon integration
- 📜 **Gift History** - Track what you've given and when
- 🛍️ **Amazon Integration** - Fetch product images and details via OpenWeb Ninja API
- 🔐 **Secure Authentication** - Built-in 2FA support and optional Authentik SSO
- 👨‍💼 **Admin Dashboard** - User management and system oversight
- 🐳 **Easy Deployment** - Docker images for simple setup

## Quick Start

### Docker (Recommended)

```bash
docker run -d \
  -p 8000:8000 \
  -v abag-data:/app/storage \
  ghcr.io/indemnity83/always-bring-a-gift:latest
```

Access the application at `http://localhost:8000`

**Default admin credentials:**
- Email: `admin@example.com`
- Password: _(generated on first run, check logs)_

### Docker Compose

```yaml
version: '3.8'

services:
  abag:
    image: ghcr.io/indemnity83/always-bring-a-gift:latest
    ports:
      - "8000:8000"
    volumes:
      - abag-data:/app/storage
    environment:
      APP_URL: http://localhost:8000
      APP_TIMEZONE: America/New_York
      # Optional: OpenWeb Ninja API for Amazon product images
      # OPENWEB_NINJA_KEY: your_api_key_here
      # Optional: Authentik SSO
      # AUTHENTIK_CLIENT_ID: your_client_id
      # AUTHENTIK_CLIENT_SECRET: your_client_secret
      # AUTHENTIK_BASE_URL: https://auth.example.com
      # AUTHENTIK_REDIRECT_URI: http://localhost:8000/auth/callback

volumes:
  abag-data:
```

### Local Development

Requirements:
- PHP 8.2+
- Composer
- Node.js 20+
- SQLite (or your preferred database)

```bash
# Clone the repository
git clone https://github.com/Indemnity83/always-bring-a-gift.git
cd always-bring-a-gift

# Install dependencies
composer install
npm install

# Set up environment
cp .env.example .env
php artisan key:generate

# Run migrations and seed database
php artisan migrate --seed

# Build assets
npm run build

# Start the development server
php artisan serve
```

## Configuration

### Environment Variables

Key environment variables you may want to configure:

```bash
# Application
APP_URL=http://localhost:8000
APP_TIMEZONE=UTC

# Database (default: SQLite)
DB_CONNECTION=sqlite
DB_DATABASE=/app/storage/database.sqlite

# Amazon Product Integration (optional)
OPENWEB_NINJA_KEY=your_api_key_here

# Authentik SSO (optional)
AUTHENTIK_CLIENT_ID=
AUTHENTIK_CLIENT_SECRET=
AUTHENTIK_BASE_URL=
AUTHENTIK_REDIRECT_URI=
```

### First-Time Setup

On first run, the application will:
1. Create an admin user with a randomly generated password
2. Seed default event types (Birthday, Anniversary, Holiday)
3. Set up the database schema

Check the container logs for the admin password:
```bash
docker logs <container-id>
```

## Tech Stack

- **Backend:** Laravel 12
- **Frontend:** Livewire 3 + Volt
- **UI:** Flux UI + Tailwind CSS 4
- **Authentication:** Laravel Fortify with 2FA support
- **Database:** SQLite (easily switchable to MySQL/PostgreSQL)
- **Deployment:** Docker with multi-architecture support (amd64/arm64)

## Screenshots

_Coming soon!_

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

### Development Guidelines

- Follow Laravel best practices
- Write tests for new features
- Run `vendor/bin/pint` before committing
- Ensure all tests pass with `php artisan test`

## Security

If you discover any security vulnerabilities, please email the maintainer directly instead of using the issue tracker.

## License

This project is open-sourced software licensed under the [MIT license](LICENSE).

## Acknowledgments

- Built with [Laravel](https://laravel.com)
- UI components by [Flux UI](https://fluxui.dev)
- Icons from [Heroicons](https://heroicons.com)
- Amazon product integration via [OpenWeb Ninja](https://rapidapi.com/developer-omniagent/api/openweb-ninja)

## Support

- 📖 [Documentation](https://github.com/Indemnity83/always-bring-a-gift/wiki) _(coming soon)_
- 🐛 [Issue Tracker](https://github.com/Indemnity83/always-bring-a-gift/issues)
- 💬 [Discussions](https://github.com/Indemnity83/always-bring-a-gift/discussions)

---

<div align="center">
  Made with ❤️ for thoughtful gift-givers everywhere
</div>
