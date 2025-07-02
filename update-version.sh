#!/bin/bash

# Script to update version numbers in plugin files
# Usage: ./update-version.sh <new_version>

set -e

# Check if version argument is provided
if [ -z "$1" ]; then
    echo "Usage: $0 <version>"
    echo "Example: $0 1.2.3"
    exit 1
fi

NEW_VERSION="$1"

# Validate version format (basic check)
if [[ ! "$NEW_VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Error: Version must be in format X.Y.Z (e.g., 1.2.3)"
    exit 1
fi

echo "Updating version to: $NEW_VERSION"

# Update plugin header version
if [ -f "gf-coupon-generator.php" ]; then
    CURRENT_VERSION=$(grep "Version:" gf-coupon-generator.php | sed 's/.*Version: \([0-9.]*\).*/\1/')
    echo "  Updating plugin header: $CURRENT_VERSION → $NEW_VERSION"
    sed -i '' "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION/" gf-coupon-generator.php
else
    echo "  Warning: gf-coupon-generator.php not found"
fi

# Update readme.txt stable tag
if [ -f "readme.txt" ]; then
    CURRENT_STABLE=$(grep "Stable tag:" readme.txt | sed 's/.*Stable tag: \([0-9.]*\).*/\1/')
    echo "  Updating readme.txt stable tag: $CURRENT_STABLE → $NEW_VERSION"
    sed -i '' "s/Stable tag: $CURRENT_STABLE/Stable tag: $NEW_VERSION/" readme.txt
else
    echo "  Warning: readme.txt not found"
fi

echo "✅ Version updated successfully!"
echo ""
echo "Files updated:"
echo "  - gf-coupon-generator.php (plugin header)"
echo "  - readme.txt (stable tag)"
echo ""
echo "Don't forget to commit these changes:"
echo "  git add gf-coupon-generator.php readme.txt"
echo "  git commit -m 'Update version to $NEW_VERSION'" 