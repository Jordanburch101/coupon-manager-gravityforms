#!/bin/bash

# Build script for creating a release-ready plugin package
# This mirrors the GitHub Actions build process

set -e

echo "🔨 Building Coupon Import Plugin Release Package..."

# Clean up any existing build
rm -rf build/

# Create build directory
mkdir -p build/coupon-import

echo "📁 Copying production files..."

# Copy production files
cp -r assets build/coupon-import/
cp -r views build/coupon-import/
cp gf-coupon-generator.php build/coupon-import/
cp README.md build/coupon-import/

# Copy vendor if it exists (production dependencies only)
if [ -d "vendor" ]; then
    echo "📦 Including vendor dependencies..."
    cp -r vendor build/coupon-import/
fi

echo "🗜️  Creating plugin zip..."

# Create the plugin zip
cd build
zip -r coupon-import-plugin.zip coupon-import/

echo "✅ Plugin package created: build/coupon-import-plugin.zip"
echo ""
echo "📋 Package contents:"
unzip -l coupon-import-plugin.zip

echo ""
echo "🚀 To test the package:"
echo "1. Extract the zip file"
echo "2. Upload to a WordPress site via Admin → Plugins → Add New → Upload Plugin"
echo "3. Or copy the 'coupon-import' folder to wp-content/plugins/" 