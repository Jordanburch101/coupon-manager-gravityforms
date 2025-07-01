# Build System Documentation

This document explains how to build production-ready packages of the GF Coupon Generator plugin for client deployment.

## Quick Start

To build a production-ready zip file:

```bash
make build
```

This creates a zip file in the `build/` directory that contains only the necessary files for the plugin to run.

## Available Commands

### Build Commands

- **`make build`** - Full build with validation (recommended)
  - Creates a production-ready zip file
  - Validates the build structure
  - Shows detailed output and file sizes
  - Excludes all development files

- **`make quick-build`** - Fast build without validation
  - Faster than the full build
  - Skips validation steps
  - Good for quick testing

- **`make release`** - Clean and build for release
  - Cleans previous builds
  - Creates a fresh production build
  - Best for final releases

### Utility Commands

- **`make help`** - Show all available commands
- **`make clean`** - Remove build files and temporary files
- **`make check`** - Verify plugin file structure
- **`make version`** - Show current plugin version
- **`make size`** - Show build size information

### Development Commands

- **`make install-deps`** - Install development dependencies
- **`make test`** - Run all tests
- **`make lint`** - Run code style checks
- **`make coverage`** - Generate test coverage report

## Build Output

### Files Included in Production Build

✅ **Core Plugin Files:**
- `gf-coupon-generator.php` - Main plugin file
- `README.md` - Plugin documentation
- `composer.json` - Dependency information

✅ **Runtime Assets:**
- `assets/css/admin.css` - Admin interface styles
- `assets/js/admin.js` - Admin interface JavaScript
- `views/admin-page.php` - Admin page template

### Files Excluded from Production Build

❌ **Development Files:**
- `tests/` - Unit and integration tests
- `.github/` - CI/CD workflows
- `vendor/` - Development dependencies
- `phpcs.xml.dist` - Code standards configuration
- `phpunit.xml.dist` - Testing configuration
- `README-TESTING.md` - Testing documentation
- `build-plugin.sh` - Build script
- `Makefile` - Build commands
- `BUILD.md` - This documentation

## Build Process Details

The build system:

1. **Validates Environment** - Ensures you're in the correct directory
2. **Cleans Previous Builds** - Removes old build artifacts
3. **Creates Build Structure** - Sets up the build directory
4. **Copies Production Files** - Only includes necessary files
5. **Removes Development Files** - Cleans any development artifacts
6. **Validates Build** - Ensures all required files are present
7. **Creates Zip Archive** - Packages everything for distribution

## Usage Examples

### Basic Build
```bash
# Create a production build
make build
```

### Clean Build for Release
```bash
# Clean everything and create fresh build
make release
```

### Check Build Size
```bash
# Build the plugin
make build

# Check the size
make size
```

### Development Workflow
```bash
# Install development dependencies
make install-deps

# Run tests
make test

# Check code style
make lint

# Create production build
make build
```

## Build Output Location

All build outputs are created in the `build/` directory:

```
build/
├── gf-coupon-generator/          # Extracted plugin files
│   ├── gf-coupon-generator.php
│   ├── README.md
│   ├── assets/
│   ├── views/
│   └── composer.json
└── gf-coupon-generator-v1.0.0.zip # Production zip file
```

## Installation Instructions for Clients

Once you have the zip file, provide these instructions to your client:

### Method 1: WordPress Admin Upload
1. Go to WordPress Admin → Plugins → Add New
2. Click "Upload Plugin"
3. Select the `gf-coupon-generator-v1.0.0.zip` file
4. Click "Install Now"
5. Click "Activate Plugin"

### Method 2: Manual Installation
1. Extract the zip file
2. Upload the `gf-coupon-generator` folder to `/wp-content/plugins/`
3. Go to WordPress Admin → Plugins
4. Find "GravityForms Coupon Generator" and click "Activate"

## Requirements

The built plugin requires:
- WordPress 5.0+
- GravityForms plugin
- GravityForms Coupons addon
- PHP 7.4+

## Troubleshooting

### Build Fails
- Ensure you're in the plugin root directory
- Check that all required files exist (`make check`)
- Verify you have write permissions in the directory

### Zip Command Not Found
- On macOS: Install via Homebrew: `brew install zip`
- On Ubuntu/Debian: `sudo apt-get install zip`
- On CentOS/RHEL: `sudo yum install zip`

### Make Command Not Found
- On macOS: Install Xcode command line tools: `xcode-select --install`
- On Ubuntu/Debian: `sudo apt-get install build-essential`
- On Windows: Use WSL or install Make for Windows

## Version Management

The build system automatically detects the version from the main plugin file header:

```php
/**
 * Plugin Name: GravityForms Coupon Generator
 * Version: 1.0.0
 */
```

The zip file will be named `gf-coupon-generator-v{VERSION}.zip` automatically. 