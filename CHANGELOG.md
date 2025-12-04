# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0](https://github.com/indemnity83/always-bring-a-gift/releases/tag/v0.1.0) - 2025-12-03

### Added
- Initial release of Always Bring a Gift (ABAG)
- Person management system with profile pictures
- Event tracking (birthdays, anniversaries, holidays, etc.)
- Gift idea management with Amazon integration
- Gift history tracking
- Recurring event support with automatic reminders
- Admin dashboard with user management
- Two-factor authentication (2FA) support
- Authentik SSO integration (optional)
- Docker deployment configuration
- SQLite database for easy setup
- GitHub Actions CI/CD pipeline
  - Automated testing
  - Code linting with Laravel Pint
  - Multi-platform Docker image builds (amd64/arm64)

### Technical Details
- Built with Laravel 12
- Livewire 3 with Volt for reactive UI
- Flux UI component library
- Tailwind CSS 4 for styling
- Fortify for authentication
- PHP 8.2+
- SQLite database (easily switchable to other databases)

### Deployment
- Docker images published to GitHub Container Registry
- Tagged releases: `ghcr.io/indemnity83/always-bring-a-gift:v0.1.0`
- Development builds: `ghcr.io/indemnity83/always-bring-a-gift:dev`
- Supports both amd64 and arm64 architectures
