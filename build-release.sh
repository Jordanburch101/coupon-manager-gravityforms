#!/bin/bash

# Build script for creating a release-ready plugin package
# This mirrors the GitHub Actions build process

set -e

echo "🔨 Building Coupon Manager for GravityForms Release Package..."

# Clean up any existing build
rm -rf build/

# Create build directory
mkdir -p build/coupon-manager

echo "📁 Copying production files..."

# Copy production files
cp -r assets build/coupon-manager/
cp -r views build/coupon-manager/
cp gf-coupon-generator.php build/coupon-manager/
cp README.md build/coupon-manager/
cp readme.txt build/coupon-manager/

# Note: composer.json and vendor directory are excluded as they contain only development dependencies
# The plugin has no runtime Composer dependencies and is self-contained

echo "🗜️  Creating plugin zip..."

# Create the plugin zip
cd build
zip -r coupon-manager-plugin.zip coupon-manager/

echo "✅ Plugin package created: build/coupon-manager-plugin.zip"
echo ""
echo "📋 Package contents:"
unzip -l coupon-manager-plugin.zip

echo ""
echo "🚀 To test the package:"
echo "1. Extract the zip file"
echo "2. Upload to a WordPress site via Admin → Plugins → Add New → Upload Plugin"
echo "3. Or copy the 'coupon-manager' folder to wp-content/plugins/" 