# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.3.0](https://github.com/Indemnity83/always-bring-a-gift/compare/v0.2.0...v0.3.0) (2025-12-05)


### ⚠ BREAKING CHANGES

* The anniversary field has been added into the create_people migration. Existing users upgrading to this version must manually add the anniversary column to their database with: ALTER TABLE people ADD COLUMN anniversary DATE;

### Features

* add bulk import for people with CSV/vCard support ([#30](https://github.com/Indemnity83/always-bring-a-gift/issues/30)) ([0790dd0](https://github.com/Indemnity83/always-bring-a-gift/commit/0790dd0c6bd95cb7aeb23b2cc346ae0c98bd4498))
* make trusted proxies configurable via environment variable ([#36](https://github.com/Indemnity83/always-bring-a-gift/issues/36)) ([0f7a1fa](https://github.com/Indemnity83/always-bring-a-gift/commit/0f7a1fa9bd693b1b781dfd1f0a70735d4525f252))


### Bug Fixes

* improve mobile layout responsiveness ([#29](https://github.com/Indemnity83/always-bring-a-gift/issues/29)) ([4ac3454](https://github.com/Indemnity83/always-bring-a-gift/commit/4ac3454794491ee05ed304663442447f0215aa33))
* remove release-type from workflow to use config file ([c3c87f6](https://github.com/Indemnity83/always-bring-a-gift/commit/c3c87f6243d151d61c299f68cda38c4c623f57a3))


### Miscellaneous Chores

* add release-please manifest with current version ([aa57a66](https://github.com/Indemnity83/always-bring-a-gift/commit/aa57a66d7dedfabacc6f5990b64227609ae99a9d))
* allow manual triggering of release workflow ([#33](https://github.com/Indemnity83/always-bring-a-gift/issues/33)) ([880517b](https://github.com/Indemnity83/always-bring-a-gift/commit/880517b0a82411b9337c6fac551bc1d9130060ab))
* configure release-please for pre-1.0 versioning ([f844b08](https://github.com/Indemnity83/always-bring-a-gift/commit/f844b08d13fd3bbfcbceeef2aca0f2b931f56c7d))
* remove user registration functionality ([#27](https://github.com/Indemnity83/always-bring-a-gift/issues/27)) ([bf9aa80](https://github.com/Indemnity83/always-bring-a-gift/commit/bf9aa80b96b58ffaa1b68e58cf9be8653c36488c))
* update unraid-template.xml with beta metadata and improvements ([#37](https://github.com/Indemnity83/always-bring-a-gift/issues/37)) ([8db8f55](https://github.com/Indemnity83/always-bring-a-gift/commit/8db8f55388f9c873b2ada2d951b6c396c1e1190d))

## [0.2.0](https://github.com/Indemnity83/always-bring-a-gift/compare/v0.1.0...v0.2.0) (2025-12-05)


### Features

* add automated releases with Release Please ([#13](https://github.com/Indemnity83/always-bring-a-gift/issues/13)) ([ec75c40](https://github.com/Indemnity83/always-bring-a-gift/commit/ec75c407d4c06b19edffe63646c12f9b226491c3))
* add UnRAID support and simplify deployment documentation ([#26](https://github.com/Indemnity83/always-bring-a-gift/issues/26)) ([1655c32](https://github.com/Indemnity83/always-bring-a-gift/commit/1655c3212927f6033f23e55da4018582b848929b))


### Bug Fixes

* add configurable trusted proxies support for reverse proxy deployments ([fff5dda](https://github.com/Indemnity83/always-bring-a-gift/commit/fff5dda2f61c3368d5945ba3d9bfd716727e3bb0))
* correct trusted proxy configuration for HTTPS URL generation ([#18](https://github.com/Indemnity83/always-bring-a-gift/issues/18)) ([3d49ca3](https://github.com/Indemnity83/always-bring-a-gift/commit/3d49ca3afc71604026bf297d591ecc6c3ae7f9cb))
* improve Authentik OAuth error handling and add stateless mode ([#24](https://github.com/Indemnity83/always-bring-a-gift/issues/24)) ([26c7a1b](https://github.com/Indemnity83/always-bring-a-gift/commit/26c7a1b70d0e07f76a0bae6707175e8f28ffdadb))
* upgrade to Alpine 3.21 to resolve ARM64 Docker build failures ([#12](https://github.com/Indemnity83/always-bring-a-gift/issues/12)) ([425dbb1](https://github.com/Indemnity83/always-bring-a-gift/commit/425dbb18c9db13366a9f180e347e65c23f552b17))
* use env() directly for TRUSTED_PROXIES in bootstrap ([#11](https://github.com/Indemnity83/always-bring-a-gift/issues/11)) ([cd8e5e5](https://github.com/Indemnity83/always-bring-a-gift/commit/cd8e5e50b6dbc455dbf5911baf7fa906de6d2c07))


### Performance Improvements

* enable comprehensive caching for Docker builds ([#21](https://github.com/Indemnity83/always-bring-a-gift/issues/21)) ([cad1ca5](https://github.com/Indemnity83/always-bring-a-gift/commit/cad1ca59e33cf5be8efe8a95364b8a6ae46d30f1))
* optimize FrankenPHP performance and reduce SQLite contention ([#25](https://github.com/Indemnity83/always-bring-a-gift/issues/25)) ([0a03b52](https://github.com/Indemnity83/always-bring-a-gift/commit/0a03b52ad407e857f2f5d3efcd85d4829a532828))
* replace php artisan serve with FrankenPHP for production performance ([#23](https://github.com/Indemnity83/always-bring-a-gift/issues/23)) ([57b294c](https://github.com/Indemnity83/always-bring-a-gift/commit/57b294c3add511c31fc3e767e24f121b7ec5d653))


### Miscellaneous Chores

* add Dependabot auto-merge workflow ([62f27a7](https://github.com/Indemnity83/always-bring-a-gift/commit/62f27a74adda10f6488df31e44abb88d25a677e4))
* add Dependabot configuration for automated dependency updates ([f874fcf](https://github.com/Indemnity83/always-bring-a-gift/commit/f874fcf5dce0b9a42a730d44aec662146e6e4f91))
* bump @tailwindcss/oxide-linux-x64-gnu from 4.1.11 to 4.1.17 ([#5](https://github.com/Indemnity83/always-bring-a-gift/issues/5)) ([a3984ac](https://github.com/Indemnity83/always-bring-a-gift/commit/a3984ac0e2085606294a4be598586c8c01784960))
* bump axios from 1.11.0 to 1.13.2 ([#8](https://github.com/Indemnity83/always-bring-a-gift/issues/8)) ([992faef](https://github.com/Indemnity83/always-bring-a-gift/commit/992faef4bc23c18ded943d7a605d7db0f4940862))
* bump laravel/sail from 1.48.1 to 1.50.0 ([#3](https://github.com/Indemnity83/always-bring-a-gift/issues/3)) ([3520485](https://github.com/Indemnity83/always-bring-a-gift/commit/35204850bc418db4acfd9725f9ac5583c807d1be))
* bump lightningcss-linux-x64-gnu from 1.30.1 to 1.30.2 ([#9](https://github.com/Indemnity83/always-bring-a-gift/issues/9)) ([15f7d04](https://github.com/Indemnity83/always-bring-a-gift/commit/15f7d042dd74603a1fa7f57068ed8250c9f57304))
* bump livewire/flux from 2.9.0 to 2.9.1 ([#10](https://github.com/Indemnity83/always-bring-a-gift/issues/10)) ([077f334](https://github.com/Indemnity83/always-bring-a-gift/commit/077f33453c8d6fc07630f3fb25bc03a4f7d212f7))
* bump tailwindcss from 4.1.11 to 4.1.17 ([#7](https://github.com/Indemnity83/always-bring-a-gift/issues/7)) ([e92da83](https://github.com/Indemnity83/always-bring-a-gift/commit/e92da836d3aadc4bdf6dab9d9e4f038245a26521))
* bump vite from 7.0.6 to 7.2.6 ([#4](https://github.com/Indemnity83/always-bring-a-gift/issues/4)) ([5da4811](https://github.com/Indemnity83/always-bring-a-gift/commit/5da48119f95b3db3e807713dca52763a16e430db))
* prepare for automated releases ([#17](https://github.com/Indemnity83/always-bring-a-gift/issues/17)) ([4ee040a](https://github.com/Indemnity83/always-bring-a-gift/commit/4ee040ad3ca9d2a8fac05a81c3040c945fc69aef))
* prepare repository for public release ([2db6f1a](https://github.com/Indemnity83/always-bring-a-gift/commit/2db6f1a998b972dad530d6da963d466518605357))

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
