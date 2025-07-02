#!/bin/bash

# Script to create a new release
# Usage: ./create-release.sh [patch|minor|major]

set -e

# Default to patch if no argument provided
VERSION_TYPE=${1:-patch}

# Validate version type
if [[ ! "$VERSION_TYPE" =~ ^(patch|minor|major)$ ]]; then
    echo "Error: Version type must be 'patch', 'minor', or 'major'"
    echo "Usage: $0 [patch|minor|major]"
    exit 1
fi

echo "Creating a new $VERSION_TYPE release..."

# Get the latest tag
LATEST_TAG=$(git tag --sort=-version:refname | head -n 1)
echo "Latest tag: $LATEST_TAG"

if [ -z "$LATEST_TAG" ]; then
    # No tags exist, start with v0.0.1
    NEW_VERSION="v0.0.1"
    NEW_VERSION_NUM="0.0.1"
else
    # Remove 'v' prefix and split version
    VERSION_NUM=${LATEST_TAG#v}
    IFS='.' read -r MAJOR MINOR PATCH <<< "$VERSION_NUM"
    
    # Increment based on input
    case "$VERSION_TYPE" in
        "major")
            MAJOR=$((MAJOR + 1))
            MINOR=0
            PATCH=0
            ;;
        "minor")
            MINOR=$((MINOR + 1))
            PATCH=0
            ;;
        "patch")
            PATCH=$((PATCH + 1))
            ;;
    esac
    
    NEW_VERSION="v${MAJOR}.${MINOR}.${PATCH}"
    NEW_VERSION_NUM="${MAJOR}.${MINOR}.${PATCH}"
fi

echo "New version will be: $NEW_VERSION"

# Confirm with user
read -p "Continue with creating release $NEW_VERSION? (y/N) " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Yy]$ ]]; then
    echo "Release cancelled."
    exit 1
fi

# Make sure we're on main branch and up to date
echo "Checking out main branch..."
git checkout main
git pull origin main

# Update version in plugin files
echo "Updating version references in plugin files..."

# Update plugin header version
if [ -f "gf-coupon-generator.php" ]; then
    # Get current version from plugin file
    CURRENT_VERSION=$(grep "Version:" gf-coupon-generator.php | sed 's/.*Version: \([0-9.]*\).*/\1/')
    echo "  Updating plugin header: $CURRENT_VERSION → $NEW_VERSION_NUM"
    sed -i '' "s/Version: $CURRENT_VERSION/Version: $NEW_VERSION_NUM/" gf-coupon-generator.php
fi

# Update readme.txt stable tag
if [ -f "readme.txt" ]; then
    # Get current stable tag from readme
    CURRENT_STABLE=$(grep "Stable tag:" readme.txt | sed 's/.*Stable tag: \([0-9.]*\).*/\1/')
    echo "  Updating readme.txt stable tag: $CURRENT_STABLE → $NEW_VERSION_NUM"
    sed -i '' "s/Stable tag: $CURRENT_STABLE/Stable tag: $NEW_VERSION_NUM/" readme.txt
fi

# Check if any files were modified
if [ -n "$(git status --porcelain)" ]; then
    echo "Committing version updates..."
    git add gf-coupon-generator.php readme.txt
    git commit -m "Update version to $NEW_VERSION_NUM"
    git push origin main
fi

# Create and push the new tag
echo "Creating tag $NEW_VERSION..."
git tag "$NEW_VERSION"
git push origin "$NEW_VERSION"

echo "✅ Release $NEW_VERSION created successfully!"
echo "   Plugin version updated to: $NEW_VERSION_NUM"
echo "   Files updated: gf-coupon-generator.php, readme.txt"
echo ""
echo "The GitHub Actions workflow will now build and publish the release."
echo "Check the Actions tab in GitHub to monitor progress." 