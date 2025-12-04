# Contributing to Always Bring a Gift

Thank you for considering contributing to Always Bring a Gift! We welcome contributions from the community.

## How Can I Contribute?

### Reporting Bugs

Before creating bug reports, please check the [issue tracker](https://github.com/Indemnity83/always-bring-a-gift/issues) to avoid duplicates.

When creating a bug report, include:
- A clear, descriptive title
- Steps to reproduce the issue
- Expected vs actual behavior
- Environment details (OS, PHP version, Docker version, etc.)
- Screenshots if applicable
- Any relevant log output

### Suggesting Enhancements

Enhancement suggestions are tracked as GitHub issues. When creating an enhancement suggestion:
- Use a clear, descriptive title
- Provide a detailed description of the proposed feature
- Explain why this enhancement would be useful
- Include mockups or examples if applicable

### Pull Requests

1. **Fork the repository** and create your branch from `main`
2. **Follow the development setup** in the README
3. **Make your changes** following the guidelines below
4. **Test your changes** thoroughly
5. **Update documentation** if needed
6. **Use conventional commits** (see Commit Messages below)
7. **Submit a pull request**

## Development Guidelines

### Code Style

- Follow Laravel best practices
- Run `vendor/bin/pint` before committing to ensure consistent code style
- Write clear, descriptive commit messages
- Keep commits focused and atomic

### Testing

- Write tests for new features
- Update existing tests if you change functionality
- Ensure all tests pass: `php artisan test`
- Aim for good test coverage

### Commit Messages

**We use conventional commits to automate releases and changelog generation.**

Follow this format:

```
type: short description

Longer description if needed
```

**Types and Their Impact:**
- `feat:` - New feature → triggers **minor** version bump (v0.2.0 → v0.3.0)
- `fix:` - Bug fix → triggers **patch** version bump (v0.2.0 → v0.2.1)
- `feat!:` or `BREAKING CHANGE:` - Breaking change → triggers **major** version bump
- `docs:` - Documentation only (no version bump)
- `refactor:` - Code refactoring (no version bump)
- `test:` - Adding or updating tests (no version bump)
- `chore:` - Maintenance tasks (no version bump)
- `ci:` - CI/CD changes (no version bump)

**Examples:**
```
feat: add gift wishlist feature
# This will bump the minor version and appear in the changelog

fix: resolve timezone issue in event notifications
# This will bump the patch version and appear in the changelog

docs: update Docker deployment instructions
# This won't bump the version but helps documentation

feat!: change authentication to OAuth2
# Breaking change - will bump major version
```

**Tips:**
- Keep the first line under 72 characters
- Use present tense: "add" not "added"
- Focus on what and why, not how
- Reference issues: `fix: resolve login bug (#123)`

### Documentation

- Update README.md for significant changes
- Add comments for complex logic
- Include PHPDoc blocks for new methods
- **Note:** CHANGELOG.md is auto-generated from commit messages - don't edit it manually

## Development Setup

### Local Development

```bash
# Clone your fork
git clone https://github.com/YOUR_USERNAME/always-bring-a-gift.git
cd always-bring-a-gift

# Install dependencies
composer install
npm install

# Setup environment
cp .env.example .env
php artisan key:generate

# Run migrations
php artisan migrate --seed

# Build assets
npm run dev

# Run tests
php artisan test
```

### Running with Docker

```bash
docker build -t abag-dev .
docker run -p 8000:8000 -v $(pwd):/app abag-dev
```

## Code Review Process

1. Maintainers will review your PR as soon as possible
2. Address any feedback or requested changes
3. Once approved, a maintainer will merge your PR

## Community

- Be respectful and inclusive
- Follow the [Code of Conduct](CODE_OF_CONDUCT.md)
- Help others in discussions and issues

## Questions?

Feel free to open an issue for questions or start a discussion in the [Discussions](https://github.com/Indemnity83/always-bring-a-gift/discussions) tab.

---

Thank you for contributing to Always Bring a Gift! 🎁
