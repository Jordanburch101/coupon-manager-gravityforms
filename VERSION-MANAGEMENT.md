# Version Management Guide

This document explains how version numbers are automatically managed in the Coupon Manager Plugin.

## 🔄 Automatic Version Updates

**Good news!** Version numbers are now **automatically updated** when you create releases. You no longer need to manually update version references in your code.

## 📍 Version References in Code

The plugin maintains version numbers in two critical files:

1. **`gf-coupon-generator.php`** - Plugin header version
   ```php
   * Version: 0.0.4
   ```

2. **`readme.txt`** - WordPress stable tag
   ```
   Stable tag: 0.0.4
   ```

## 🚀 Automated Release Process

When you create a release using any of these methods, **version numbers are automatically updated**:

### Method 1: Release Scripts (Recommended)
```bash
# These automatically update version numbers
./create-release.sh patch    # 0.0.4 → 0.0.5
./create-release.sh minor    # 0.0.4 → 0.1.0
./create-release.sh major    # 0.0.4 → 1.0.0
```

### Method 2: Makefile Commands
```bash
# These also automatically update version numbers
make release-patch    # 0.0.4 → 0.0.5
make release-minor    # 0.0.4 → 0.1.0
make release-major    # 0.0.4 → 1.0.0
```

### Method 3: GitHub Actions Manual Trigger
1. Go to GitHub Actions → "Create Plugin Release"
2. Click "Run workflow"
3. Select version increment type (patch/minor/major)
4. Version numbers are automatically updated in the workflow

## 🔧 What Happens During Release

When you create a release, the system:

1. **Determines next version** based on current tags
2. **Updates plugin files** with new version numbers:
   - `gf-coupon-generator.php` (plugin header)
   - `readme.txt` (stable tag)
3. **Commits the changes** to main branch
4. **Creates and pushes** the new version tag
5. **Triggers GitHub Actions** to build and publish the release

## 🛠️ Manual Version Updates (Optional)

If you need to update version numbers manually:

### Using the Update Script
```bash
./update-version.sh 1.2.3
```

### Using Makefile
```bash
make update-version VERSION=1.2.3
```

### Manual Editing
You can also edit the files directly, but the automated methods are recommended.

## 📊 Version Checking

Check current version:
```bash
make version                           # Shows: Current version: 0.0.4
git tag --sort=-version:refname | head -1  # Shows: v0.0.4
```

## 🔄 Version Synchronization

The system ensures all version references stay synchronized:

- **Plugin Header**: `* Version: X.Y.Z`
- **Readme Stable Tag**: `Stable tag: X.Y.Z`
- **Git Tag**: `vX.Y.Z`

## 📋 Best Practices

1. **Use automated release methods** - They handle version updates for you
2. **Don't manually edit version numbers** unless necessary
3. **Always test locally** before creating releases
4. **Use semantic versioning**:
   - **Patch** (0.0.4 → 0.0.5): Bug fixes, small improvements
   - **Minor** (0.0.4 → 0.1.0): New features, backwards compatible  
   - **Major** (0.0.4 → 1.0.0): Breaking changes, major updates

## 🚨 Troubleshooting

### Version Mismatch Issues
If versions get out of sync, use the update script:
```bash
./update-version.sh 0.0.5  # Set to specific version
```

### Failed Release Due to Version Conflicts
The automated system prevents tag conflicts, but if you encounter issues:
```bash
# Check current state
git tag --sort=-version:refname | head -5
make version

# Fix if needed
./update-version.sh <correct_version>
git add gf-coupon-generator.php readme.txt
git commit -m "Fix version synchronization"
```

## 📝 Summary

✅ **Version numbers are automatically updated** when creating releases  
✅ **No manual editing required** for normal releases  
✅ **All version references stay synchronized**  
✅ **Multiple convenient methods** available (scripts, Makefile, GitHub Actions)  
✅ **Prevents version conflicts** and human errors  

The days of manually updating version numbers are over! 🎉 