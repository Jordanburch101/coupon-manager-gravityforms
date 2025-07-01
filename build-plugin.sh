#!/bin/bash

# GF Coupon Generator - Build Script
# Creates a production-ready zip file with only necessary plugin files

set -e  # Exit on any error

# Script configuration
PLUGIN_NAME="gf-coupon-generator"
VERSION=$(grep "Version:" gf-coupon-generator.php | sed 's/.*Version: \([0-9.]*\).*/\1/')
BUILD_DIR="build"
DIST_DIR="$BUILD_DIR/$PLUGIN_NAME"
ZIP_FILE="$BUILD_DIR/${PLUGIN_NAME}-v${VERSION}.zip"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Function to check if we're in the right directory
check_directory() {
    if [[ ! -f "gf-coupon-generator.php" ]]; then
        print_error "This script must be run from the plugin root directory"
        exit 1
    fi
}

# Function to clean up previous builds
cleanup() {
    print_status "Cleaning up previous builds..."
    if [[ -d "$BUILD_DIR" ]]; then
        rm -rf "$BUILD_DIR"
    fi
}

# Function to create build directory structure
create_build_dir() {
    print_status "Creating build directory structure..."
    mkdir -p "$DIST_DIR"
}

# Function to copy production files
copy_files() {
    print_status "Copying production files..."
    
    # Core plugin files
    cp gf-coupon-generator.php "$DIST_DIR/"
    cp README.md "$DIST_DIR/"
    
    # Assets directory (CSS and JS)
    if [[ -d "assets" ]]; then
        cp -r assets "$DIST_DIR/"
        print_status "Copied assets directory"
    fi
    
    # Views directory (admin templates)
    if [[ -d "views" ]]; then
        cp -r views "$DIST_DIR/"
        print_status "Copied views directory"
    fi
    
    # Composer.json (for dependency management info, but not vendor)
    if [[ -f "composer.json" ]]; then
        cp composer.json "$DIST_DIR/"
        print_status "Copied composer.json"
    fi
    
    print_success "Core files copied successfully"
}

# Function to remove development files from the build
remove_dev_files() {
    print_status "Removing development files from build..."
    
    # Remove any development-specific files that might have been copied
    find "$DIST_DIR" -name "*.log" -delete 2>/dev/null || true
    find "$DIST_DIR" -name ".DS_Store" -delete 2>/dev/null || true
    find "$DIST_DIR" -name "Thumbs.db" -delete 2>/dev/null || true
    find "$DIST_DIR" -name "*.tmp" -delete 2>/dev/null || true
    
    print_status "Development files cleaned"
}

# Function to create zip file
create_zip() {
    print_status "Creating zip file..."
    
    cd "$BUILD_DIR"
    zip -r "${PLUGIN_NAME}-v${VERSION}.zip" "$PLUGIN_NAME/" >/dev/null
    cd ..
    
    print_success "Zip file created: $ZIP_FILE"
}

# Function to display build summary
show_summary() {
    print_success "Build completed successfully!"
    echo ""
    echo "📦 Plugin: $PLUGIN_NAME"
    echo "🏷️  Version: $VERSION"
    echo "📁 Build directory: $BUILD_DIR"
    echo "🗜️  Zip file: $ZIP_FILE"
    echo ""
    
    # Show file size
    if [[ -f "$ZIP_FILE" ]]; then
        SIZE=$(du -h "$ZIP_FILE" | cut -f1)
        echo "📊 Zip file size: $SIZE"
    fi
    
    echo ""
    echo "Files included in the build:"
    echo "✅ gf-coupon-generator.php (main plugin file)"
    echo "✅ README.md (documentation)"
    echo "✅ assets/ (CSS and JavaScript)"
    echo "✅ views/ (admin templates)"
    echo "✅ composer.json (dependency info)"
    echo ""
    echo "Files excluded from the build:"
    echo "❌ tests/ (unit and integration tests)"
    echo "❌ .github/ (CI/CD workflows)"
    echo "❌ vendor/ (development dependencies)"
    echo "❌ phpcs.xml.dist (code standards config)"
    echo "❌ phpunit.xml.dist (testing config)"
    echo "❌ README-TESTING.md (testing documentation)"
    echo "❌ build-plugin.sh (this build script)"
    echo ""
    echo "🚀 Ready for deployment!"
}

# Function to validate the build
validate_build() {
    print_status "Validating build..."
    
    # Check if main plugin file exists
    if [[ ! -f "$DIST_DIR/gf-coupon-generator.php" ]]; then
        print_error "Main plugin file missing from build"
        exit 1
    fi
    
    # Check if required directories exist
    if [[ ! -d "$DIST_DIR/assets" ]]; then
        print_warning "Assets directory missing from build"
    fi
    
    if [[ ! -d "$DIST_DIR/views" ]]; then
        print_warning "Views directory missing from build"
    fi
    
    # Check for any test files that shouldn't be there
    if find "$DIST_DIR" -name "*test*" -o -name "*Test*" | grep -q .; then
        print_warning "Test files found in build - these should be excluded"
    fi
    
    print_success "Build validation completed"
}

# Main execution
main() {
    echo "🔨 Building GF Coupon Generator Plugin"
    echo "======================================"
    echo ""
    
    check_directory
    cleanup
    create_build_dir
    copy_files
    remove_dev_files
    validate_build
    create_zip
    show_summary
    
    echo ""
    echo "💡 To install the plugin:"
    echo "   1. Upload $ZIP_FILE to WordPress admin"
    echo "   2. Or extract to /wp-content/plugins/ directory"
    echo "   3. Activate the plugin in WordPress admin"
}

# Run the script
main "$@" 