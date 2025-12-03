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
6. **Update the CHANGELOG.md** in the `[Unreleased]` section
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

Follow conventional commit format:

```
type: short description

Longer description if needed
```

**Types:**
- `feat`: New feature
- `fix`: Bug fix
- `docs`: Documentation changes
- `refactor`: Code refactoring
- `test`: Adding or updating tests
- `chore`: Maintenance tasks

**Examples:**
```
feat: add gift wishlist feature

fix: resolve timezone issue in event notifications

docs: update Docker deployment instructions
```

### Documentation

- Update README.md for significant changes
- Add comments for complex logic
- Update CHANGELOG.md in the `[Unreleased]` section
- Include PHPDoc blocks for new methods

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
