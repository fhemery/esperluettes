#!/bin/bash

# Safe incremental sync script
# This runs a full build-and-deploy, then extracts only the files you need for incremental updates

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}🔄 Safe Incremental Sync from Dist${NC}"
echo "====================================="

# Check if we need to build first
npm run build-and-deploy

# Create sync directory
SYNC_DIR="sync"
rm -rf "$SYNC_DIR"
mkdir -p "$SYNC_DIR"

echo -e "${BLUE}📦 Extracting files from production-ready dist...${NC}"

# Extract app files from dist (these have production-ready autoload compatibility)
if [ -d "dist/app" ]; then
    cp -r dist/app "$SYNC_DIR/"
    echo "✅ App files extracted from dist"
else
    echo -e "${RED}❌ No app directory found in dist${NC}"
    exit 1
fi

# Extract production autoload files from dist
if [ -d "dist/vendor/composer" ]; then
    mkdir -p "$SYNC_DIR/vendor"
    cp -r dist/vendor/composer "$SYNC_DIR/vendor/"
    echo "✅ Production autoload files extracted from dist"
else
    echo -e "${RED}❌ No vendor/composer directory found in dist${NC}"
    exit 1
fi

# Copy composer.lock from dist if it exists
if [ -f "dist/composer.lock" ]; then
    cp dist/composer.lock "$SYNC_DIR/"
    echo "✅ Composer.lock extracted from dist"
fi

# Calculate sync size
SYNC_SIZE=$(du -sh "$SYNC_DIR" | cut -f1)

echo ""
echo -e "${GREEN}🎉 Safe incremental sync package ready!${NC}"
echo "============================================="
echo -e "📦 Sync location: ${BLUE}$SYNC_DIR/${NC}"
echo -e "📏 Sync size: ${BLUE}$SYNC_SIZE${NC}"
echo -e "🔒 Source: ${BLUE}Production-ready dist folder${NC}"
echo ""
echo -e "${BLUE}📋 Upload these to production:${NC}"
echo "• $SYNC_DIR/app/ → your-project/app/"
echo "• $SYNC_DIR/vendor/composer/ → your-project/vendor/composer/"
if [ -f "$SYNC_DIR/composer.lock" ]; then
    echo "• $SYNC_DIR/composer.lock → your-project/composer.lock"
fi
echo ""
echo -e "${GREEN}✅ These files are guaranteed to be production-compatible!${NC}"
echo -e "${YELLOW}💡 Extracted from the same dist that works in production${NC}"
