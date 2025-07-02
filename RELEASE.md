# Release Guide

This document explains how to create releases for the Coupon Manager Plugin.

## Automated Release Process

The plugin uses GitHub Actions to automatically create releases when you push version tags to the repository.

### Creating a Release

1. **Test your changes locally:**
   ```bash
   ./build-release.sh
   ```
   This creates a test package in `build/coupon-import-plugin.zip`

2. **Commit and push your changes:**
   ```bash
   git add .
   git commit -m "Prepare release v1.0.0"
   git push origin main
   ```

3. **Create and push a version tag:**
   ```bash
   git tag v1.0.0
   git push origin v1.0.0
   ```

4. **GitHub Actions will automatically:**
   - Build a clean plugin package (excluding development files)
   - Create a GitHub release
   - Attach the plugin zip file
   - Generate release notes

### Version Numbering

Use [Semantic Versioning](https://semver.org/):
- `v1.0.0` - Major release (breaking changes)
- `v1.1.0` - Minor release (new features)
- `v1.0.1` - Patch release (bug fixes)
- `v1.0.0-beta.1` - Pre-release versions

### What Gets Included in Releases

**Included files:**
- `gf-coupon-generator.php` (main plugin file)
- `assets/` (CSS and JavaScript)
- `views/` (PHP templates)
- `vendor/` (production Composer dependencies)
- `README.md`

**Excluded files:**
- `tests/` (test suite)
- `build/` (build artifacts)
- Development configuration files
- Git files and folders

### Manual Release Testing

Before creating a tag, test the release package:

1. Run the build script:
   ```bash
   ./build-release.sh
   ```

2. Test the generated `build/coupon-import-plugin.zip`:
   - Extract it to verify contents
   - Upload to a WordPress test site
   - Verify the plugin activates and works correctly

### Troubleshooting

**If the GitHub Action fails:**
1. Check the Actions tab in your GitHub repository
2. Review the error logs
3. Common issues:
   - Missing files in the copy commands
   - Composer dependency issues
   - Zip creation problems

**To delete a bad release:**
1. Go to GitHub → Releases
2. Click on the problematic release
3. Click "Delete" and confirm
4. Delete the tag: `git tag -d v1.0.0 && git push origin :refs/tags/v1.0.0`

### Release Checklist

Before creating a release:
- [ ] All tests pass
- [ ] Version number updated in plugin header
- [ ] CHANGELOG.md updated (if you have one)
- [ ] Local build test successful
- [ ] Plugin tested on WordPress site
- [ ] No development files included in package 