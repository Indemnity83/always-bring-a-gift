# Release Process

This document outlines the manual release process for Always Bring a Gift (ABAG).

## Release Strategy

- **Main branch**: Development happens here, builds tagged as `:dev`
- **Version tags**: Trigger production builds with semantic versioning
- **Pre-1.0 versions**: Use `v0.x.x` for initial releases
- **Post-1.0 versions**: Follow semantic versioning strictly

## Semantic Versioning

- **Major** (v1.0.0 → v2.0.0): Breaking changes, incompatible API changes
- **Minor** (v1.0.0 → v1.1.0): New features, backwards compatible
- **Patch** (v1.0.0 → v1.0.1): Bug fixes, backwards compatible

## Release Checklist

### 1. Prepare the Release

- [ ] Ensure all desired changes are merged to `main`
- [ ] Run tests locally: `php artisan test`
- [ ] Run linter: `vendor/bin/pint`
- [ ] Verify Docker build works: `docker build -t test .`
- [ ] Check GitHub Actions are passing

### 2. Update CHANGELOG.md

Move items from `[Unreleased]` section to a new version section:

```markdown
## [Unreleased]

## [0.2.0] - 2025-12-15

### Added
- New feature X
- New feature Y

### Fixed
- Bug fix Z

### Changed
- Updated behavior of feature A
```

Add the new version link at the bottom:
```markdown
[Unreleased]: https://github.com/indemnity83/always-bring-a-gift/compare/v0.2.0...HEAD
[0.2.0]: https://github.com/indemnity83/always-bring-a-gift/releases/tag/v0.2.0
[0.1.0]: https://github.com/indemnity83/always-bring-a-gift/releases/tag/v0.1.0
```

### 3. Commit and Tag

```bash
# Commit the changelog
git add CHANGELOG.md
git commit -m "docs: prepare CHANGELOG for v0.2.0 release"

# Create an annotated tag
git tag -a v0.2.0 -m "Release v0.2.0"

# Push everything
git push origin main
git push origin v0.2.0
```

### 4. Monitor the Build

- Go to GitHub Actions and watch the build workflow
- Verify Docker images are built for both `amd64` and `arm64`
- Check that images are published to `ghcr.io/indemnity83/always-bring-a-gift:v0.2.0`

### 5. Create GitHub Release (Optional but Recommended)

1. Go to https://github.com/indemnity83/always-bring-a-gift/releases
2. Click "Draft a new release"
3. Choose the tag you just pushed
4. Copy the CHANGELOG section for this version into the description
5. Add any additional notes about:
   - Breaking changes (if any)
   - Migration steps (if any)
   - Docker deployment instructions
6. Mark as "pre-release" if version is < 1.0.0
7. Publish release

### 6. Test the Release

Pull and test the released Docker image:

```bash
docker pull ghcr.io/indemnity83/always-bring-a-gift:v0.2.0
docker run -p 8000:8000 ghcr.io/indemnity83/always-bring-a-gift:v0.2.0
```

## Hotfix Process

If you need to release a critical fix:

1. Create a branch from the release tag: `git checkout -b hotfix/v0.2.1 v0.2.0`
2. Make the fix
3. Update CHANGELOG.md with the patch version
4. Commit, tag as `v0.2.1`, and push
5. Optionally merge the fix back to main if needed

## Rolling Back

If a release has issues:

1. Do NOT delete the tag
2. Create a new release with the fix
3. Update documentation to point to the working version
4. Mark the broken release as "deprecated" in GitHub releases

## CHANGELOG Maintenance

Between releases, update the `[Unreleased]` section as you work:

```markdown
## [Unreleased]

### Added
- Feature description (#123)

### Fixed
- Bug description (#124)

### Changed
- Change description (#125)
```

This keeps the changelog up-to-date and makes release preparation easier.

## Docker Image Tags

After a successful release, these images will be available:

- `ghcr.io/indemnity83/always-bring-a-gift:v0.2.0` - Specific version
- `ghcr.io/indemnity83/always-bring-a-gift:v0.2` - Minor version (if using semver tags in workflow)
- `ghcr.io/indemnity83/always-bring-a-gift:v0` - Major version (if using semver tags in workflow)
- `ghcr.io/indemnity83/always-bring-a-gift:latest` - Latest stable release
- `ghcr.io/indemnity83/always-bring-a-gift:dev` - Latest main branch build

## Notes

- Always use annotated tags (`-a` flag) for releases
- Never force-push tags
- Tag messages should be simple: "Release v0.2.0"
- Keep CHANGELOG.md updated as you work, not just at release time
- Run all tests before tagging
- GitHub Actions will automatically build and push Docker images when you push a tag
