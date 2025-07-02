# Release Guide 

This document explains how to create releases for the Coupon Manager Plugin.

## Automated Release Process

The plugin uses GitHub Actions to automatically create releases with automatic version incrementing.

### Quick Release (Recommended)

Use the convenient release script or Makefile commands:

**Using the release script:**
```bash
# Create a patch release (0.0.3 → 0.0.4)
./create-release.sh patch

# Create a minor release (0.0.3 → 0.1.0)
./create-release.sh minor

# Create a major release (0.0.3 → 1.0.0)
./create-release.sh major
```

**Using Makefile commands:**
```bash
# Create a patch release
make release-patch

# Create a minor release
make release-minor

# Create a major release
make release-major
```

The script will:
1. Automatically determine the next version number based on existing tags
2. Show you the proposed version and ask for confirmation
3. Create and push the new tag
4. Trigger the GitHub Actions workflow to build and publish the release

### Manual Release Process

If you prefer manual control:

1. **Test your changes locally:**
   ```bash
   ./build-release.sh
   ```
   This creates a test package in `build/coupon-manager-plugin.zip`

2. **Commit and push your changes:**
   ```bash
   git add .
   git commit -m "Prepare release v0.0.4"
   git push origin main
   ```

3. **Create and push a version tag:**
   ```bash
   # Get the latest version first
   git tag --sort=-version:refname | head -1
   
   # Create the next version tag
   git tag v0.0.4
   git push origin v0.0.4
   ```

### GitHub Actions Workflow

The workflow can be triggered in two ways:

1. **Tag Push (automatic):** When you push a version tag, it uses that tag
2. **Manual Trigger:** Go to GitHub Actions → "Create Plugin Release" → "Run workflow" and select the version increment type

The workflow will automatically:
- Build a clean plugin package (excluding development files)
- Create a GitHub release with the incremented version
- Attach the plugin zip file
- Generate release notes

### Version Numbering

The system uses [Semantic Versioning](https://semver.org/):
- **Patch** (`0.0.3` → `0.0.4`) - Bug fixes, small improvements
- **Minor** (`0.0.3` → `0.1.0`) - New features, backwards compatible
- **Major** (`0.0.3` → `1.0.0`) - Breaking changes, major updates

### What Gets Included in Releases

**Included files:**
- `gf-coupon-generator.php` (main plugin file)
- `assets/` (CSS and JavaScript)
- `views/` (PHP templates)
- `README.md`
- `readme.txt` (WordPress plugin directory format)
- `gpl-3.0.txt` (license file)

**Excluded files:**
- `tests/` (test suite)
- `build/` (build artifacts)
- `vendor/` (development dependencies)
- Development configuration files
- Git files and folders

### Manual Release Testing

Before creating a release, test the package:

1. Run the build script:
   ```bash
   ./build-release.sh
   ```

2. Test the generated `build/coupon-manager-plugin.zip`:
   - Extract it to verify contents
   - Upload to a WordPress test site
   - Verify the plugin activates and works correctly

### Troubleshooting

**If the GitHub Action fails:**
1. Check the Actions tab in your GitHub repository
2. Review the error logs
3. Common issues:
   - Missing files in the copy commands
   - Tag already exists (use the automated scripts to avoid this)
   - Repository permissions

**To delete a bad release:**
1. Go to GitHub → Releases
2. Click on the problematic release
3. Click "Delete" and confirm
4. Delete the tag: `git tag -d v0.0.4 && git push origin :refs/tags/v0.0.4`

### Release Checklist

Before creating a release:
- [ ] All tests pass (`make test`)
- [ ] Code passes linting (`make lint`)
- [ ] Local build test successful (`./build-release.sh`)
- [ ] Plugin tested on WordPress site
- [ ] Changes documented in commit messages
- [ ] No development/debug code included

### Current Version

To check the current version:
```bash
make version
# or
git tag --sort=-version:refname | head -1
``` 