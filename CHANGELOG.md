# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.2.0](https://github.com/Indemnity83/always-bring-a-gift/compare/always-bring-a-gift-v0.1.0...always-bring-a-gift-v0.2.0) (2025-12-04)


### Features

* add admin system, Docker deployment, and production readiness ([c431130](https://github.com/Indemnity83/always-bring-a-gift/commit/c4311301806fcaad54e5774298b632c378ed6cdc))
* add automated releases with Release Please ([#13](https://github.com/Indemnity83/always-bring-a-gift/issues/13)) ([ec75c40](https://github.com/Indemnity83/always-bring-a-gift/commit/ec75c407d4c06b19edffe63646c12f9b226491c3))
* add birthday tracking and event creation to people ([4596c56](https://github.com/Indemnity83/always-bring-a-gift/commit/4596c566fd6a1db4303e6b3d429908ce662edfde))
* add configurable timeframe selector to dashboard ([27bb4f7](https://github.com/Indemnity83/always-bring-a-gift/commit/27bb4f77d89bb9da7c35330b53810dc11f9993f2))
* add event deletion from person show page ([fb0783d](https://github.com/Indemnity83/always-bring-a-gift/commit/fb0783db1d632470debadc02b73b4178919a3ad5))
* add image and link support to gifts with auto-fetch ([a3e3c5d](https://github.com/Indemnity83/always-bring-a-gift/commit/a3e3c5d97a4ebce20bc7847c0728077a8ea36068))
* add milestone tracking for recurring events ([cb658db](https://github.com/Indemnity83/always-bring-a-gift/commit/cb658db5391bdd4f774264b6be20aa3a57aea663))
* add quick actions to dashboard for improved UX ([c2b19b9](https://github.com/Indemnity83/always-bring-a-gift/commit/c2b19b9bb8f5f53513e5e5efcdf8ed0a7681773b))
* display completed events with visual distinction ([0d5c34c](https://github.com/Indemnity83/always-bring-a-gift/commit/0d5c34c981707e996a600c4249ab9bad0fe98966))
* enhance dashboard with gifts list, compact layout, and no-peeking mode ([5fdb113](https://github.com/Indemnity83/always-bring-a-gift/commit/5fdb113652d582e7ee2bff7e184a74dbea143246))
* enhance person show UI with gift ideas and improved layout ([b81876b](https://github.com/Indemnity83/always-bring-a-gift/commit/b81876bd2259786ee49ff6318323c97937b305dd))
* implement complete gift tracking MVP ([66b92e7](https://github.com/Indemnity83/always-bring-a-gift/commit/66b92e76615b22403088c04770fee09cabf1ab57))
* integrate Authentik SSO authentication with local fallback ([c4bc705](https://github.com/Indemnity83/always-bring-a-gift/commit/c4bc705807244e944dadd75bad2b129be4c99a3b))
* integrate OpenWeb Ninja API for Amazon product images ([0025eaf](https://github.com/Indemnity83/always-bring-a-gift/commit/0025eafa3ce84779314d7864b771d7b3206a2bdf))
* redesign people index with cards and add profile pictures ([f552339](https://github.com/Indemnity83/always-bring-a-gift/commit/f5523393badb4bb8fb2bfa4fbe99936f560102a5))


### Bug Fixes

* add APP_REGISTRATION_ENABLED to .env.example ([3ae7c5c](https://github.com/Indemnity83/always-bring-a-gift/commit/3ae7c5c2a7ef597a9ba3da4bff662a4f187c7e12))
* add configurable trusted proxies support for reverse proxy deployments ([fff5dda](https://github.com/Indemnity83/always-bring-a-gift/commit/fff5dda2f61c3368d5945ba3d9bfd716727e3bb0))
* add missing milestone toggle to event edit form ([6449129](https://github.com/Indemnity83/always-bring-a-gift/commit/6449129efecbbec3ee84583f4e030a7364152a9b))
* align action buttons to bottom of event cards ([587023b](https://github.com/Indemnity83/always-bring-a-gift/commit/587023ba690cf639f949b6fb4cee138414646990))
* event type dropdown not recognizing initial selection ([1d32e19](https://github.com/Indemnity83/always-bring-a-gift/commit/1d32e19d44cdfcf906762c013821a7894688a5c0))
* prevent gift images from being cropped ([c151757](https://github.com/Indemnity83/always-bring-a-gift/commit/c1517573e15bef6cbaa510d7d46dc5dfa5cac101))
* resolve test workflow issues and add missing Unit directory ([612c5fe](https://github.com/Indemnity83/always-bring-a-gift/commit/612c5fe3d15f00af4116b213da0be3fb2a93dd8e))
* upgrade to Alpine 3.21 to resolve ARM64 Docker build failures ([#12](https://github.com/Indemnity83/always-bring-a-gift/issues/12)) ([425dbb1](https://github.com/Indemnity83/always-bring-a-gift/commit/425dbb18c9db13366a9f180e347e65c23f552b17))
* use env() directly for TRUSTED_PROXIES in bootstrap ([#11](https://github.com/Indemnity83/always-bring-a-gift/issues/11)) ([cd8e5e5](https://github.com/Indemnity83/always-bring-a-gift/commit/cd8e5e50b6dbc455dbf5911baf7fa906de6d2c07))


### Documentation

* add CHANGELOG and release process documentation ([4f83802](https://github.com/Indemnity83/always-bring-a-gift/commit/4f838025b1464f1bdb352fd54b68e43ba098806d))
* add README and MIT license ([a58a611](https://github.com/Indemnity83/always-bring-a-gift/commit/a58a6112ca484ddd02c457c8972de17eb7d2c880))

## [Unreleased]

### Added
- Comprehensive README with deployment and development instructions
- MIT License file
- CONTRIBUTING.md with contribution guidelines
- CODE_OF_CONDUCT.md with psychological safety framework (Contributor Covenant v2.1)
- GitHub issue templates (bug report, feature request)
- `.claude` directory to .gitignore
- Configurable trusted proxies support via `TRUSTED_PROXIES` environment variable

### Changed
- Renamed "Build & Publish Docker Images" workflow to "Build" for cleaner badge display
- Docker images now default to trusting all proxies (safe for Traefik/reverse proxy deployments)

### Security
- Trusted proxies now configurable via environment variable instead of hardcoded (prevents security issues in direct deployments)

## [0.1.0] - 2025-12-03

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

[Unreleased]: https://github.com/indemnity83/always-bring-a-gift/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/indemnity83/always-bring-a-gift/releases/tag/v0.1.0
