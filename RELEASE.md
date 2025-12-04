# Release Process

This document outlines the automated release process for Always Bring a Gift (ABAG) using Release Please.

## How Automated Releases Work

Releases are fully automated using [Release Please](https://github.com/googleapis/release-please):

1. **Commit using conventional commits** - Use prefixes like `feat:`, `fix:`, `chore:`, etc.
2. **Push to main** - Release Please analyzes commits since the last release
3. **Review the Release PR** - Release Please creates/updates a PR with:
   - Updated CHANGELOG.md
   - Bumped version in composer.json
   - All changes grouped by type
4. **Merge the Release PR** - This automatically:
   - Creates a GitHub release
   - Tags the commit (e.g., `v0.3.0`)
   - Triggers Docker builds for the new version

## Conventional Commits

Use these prefixes to control versioning:

- `feat:` - New feature → **Minor** version bump (v0.2.0 → v0.3.0)
- `fix:` - Bug fix → **Patch** version bump (v0.2.0 → v0.2.1)
- `feat!:` or `BREAKING CHANGE:` - Breaking change → **Major** version bump (v0.2.0 → v1.0.0)
- `chore:`, `ci:`, `docs:`, `refactor:`, `test:` - No version bump (included in next release)

Examples:
```bash
git commit -m "feat: add email notifications for gift reminders"
git commit -m "fix: resolve timezone issue in event recurrence"
git commit -m "feat!: change API authentication to OAuth2"
```

## Release Workflow

### Normal Release Process

1. **Develop with conventional commits**
   ```bash
   git commit -m "feat: add Amazon wishlist integration"
   git commit -m "fix: correct date formatting on event cards"
   git push origin main
   ```

2. **Release Please automatically creates/updates a PR**
   - Check the "Release PR" in your pull requests
   - Review the generated CHANGELOG.md
   - Verify the version bump is correct

3. **Merge the Release PR**
   - Click "Squash and merge" or "Merge pull request"
   - Release Please will:
     - Create a GitHub release
     - Tag the version (e.g., `v0.3.0`)
     - Trigger Docker image build

4. **Docker images are automatically published**
   - `ghcr.io/indemnity83/always-bring-a-gift:v0.3.0` - Specific version
   - `ghcr.io/indemnity83/always-bring-a-gift:v0.3` - Minor version
   - `ghcr.io/indemnity83/always-bring-a-gift:v0` - Major version
   - `ghcr.io/indemnity83/always-bring-a-gift:latest` - Latest release

### Hotfix Process

For critical production fixes:

1. **Fix on main with conventional commit**
   ```bash
   git commit -m "fix: critical security patch for auth"
   git push origin main
   ```

2. **Release Please will create a patch release PR**
   - Version will be bumped: v0.2.0 → v0.2.1

3. **Merge immediately for urgent deployment**

## Manual Intervention (Rare Cases)

### If Release PR is Incorrect

1. Close the Release PR (don't merge)
2. Edit `.release-please-manifest.json` to set the correct version
3. Commit: `git commit -m "chore: adjust release version"`
4. Push - Release Please will create a new PR

### If You Need to Skip a Release

Simply don't merge the Release PR. Release Please will keep it updated as new commits are added.

## CHANGELOG Maintenance

**You don't need to manually update CHANGELOG.md!**

Release Please generates it from your commit messages. Just write good conventional commits:

```bash
# Good commit messages
git commit -m "feat: add recurring event templates"
git commit -m "fix: resolve timezone bug in calendar view"
git commit -m "docs: update deployment guide with Kubernetes"

# Bad commit messages (avoid these)
git commit -m "updates"
git commit -m "WIP"
git commit -m "fix stuff"
```

## Semantic Versioning

We follow [Semantic Versioning](https://semver.org/):

- **Major** (v1.0.0 → v2.0.0): Breaking changes (use `feat!:` or `BREAKING CHANGE:`)
- **Minor** (v1.0.0 → v1.1.0): New features (use `feat:`)
- **Patch** (v1.0.0 → v1.0.1): Bug fixes (use `fix:`)

### Pre-1.0 Versions

We're currently pre-1.0 (v0.x.x), which means:
- Minor version changes may include breaking changes
- We're not yet API stable
- Use `v1.0.0` when the API is stable and production-ready

## Docker Image Strategy

After each release:

- **Versioned tags**: `v0.3.0`, `v0.3`, `v0`, `latest`
- **Development tag**: `dev` (updated on every main branch push)

Always use specific versions in production: `ghcr.io/indemnity83/always-bring-a-gift:v0.3.0`

## Rolling Back

If a release has issues:

1. **Do NOT delete tags or releases**
2. **Create a fix and release a new version**
3. **Update documentation** to recommend the working version
4. **Mark broken release** in GitHub releases notes

## Testing Releases

Pull and test Docker images locally:

```bash
# Test specific version
docker pull ghcr.io/indemnity83/always-bring-a-gift:v0.3.0
docker run -p 8000:8000 ghcr.io/indemnity83/always-bring-a-gift:v0.3.0

# Test latest
docker pull ghcr.io/indemnity83/always-bring-a-gift:latest
docker run -p 8000:8000 ghcr.io/indemnity83/always-bring-a-gift:latest
```

## Troubleshooting

### Release PR Not Created

- Check that commits use conventional commit format
- Ensure the `release` workflow ran successfully
- Verify `PERSONAL_TOKEN` secret is configured

### Version Bump is Wrong

- Check your commit messages match conventional commit format
- If needed, close the PR and adjust `.release-please-manifest.json`

### Docker Build Failed

- Check GitHub Actions logs
- Ensure tests are passing before merging Release PR

## Notes

- Releases are automated - don't manually create tags
- Use conventional commits for all changes
- Release Please tracks versions in `.release-please-manifest.json`
- CHANGELOG.md is auto-generated from commits
- GitHub releases are created automatically
- Docker images are built and pushed automatically on tag creation
