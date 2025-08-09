#!/bin/bash

# Laravel Deployment Script for o2switch
# This script prepares the Laravel application for production deployment

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Configuration
DIST_DIR="dist"
ENV_FILE=".env.test"

echo -e "${BLUE}🚀 Starting Laravel Deployment Build Process${NC}"
echo "=============================================="

# Check if we're in a Laravel project
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Not in a Laravel project directory${NC}"
    exit 1
fi

# Check if .env.production exists
if [ ! -f "$ENV_FILE" ]; then
    echo -e "${YELLOW}⚠️  Warning: $ENV_FILE not found. Please create it before deployment.${NC}"
    echo -e "${YELLOW}   You can copy .env.example and modify it for production.${NC}"
    exit 1
fi

echo -e "${BLUE}📦 Step 1: Cleaning previous build${NC}"
if [ -d "$DIST_DIR" ]; then
    rm -rf "$DIST_DIR"
    echo "✅ Removed existing dist directory"
fi

echo -e "${BLUE}🔧 Step 2: Installing/updating dependencies${NC}"
# Install PHP dependencies (production only)
./vendor/bin/sail composer install --optimize-autoloader --no-interaction
echo "✅ Composer dependencies installed"

# Install frontend dependencies (build should be done separately before running this script)
npm ci
echo "✅ Frontend dependencies installed"
echo "ℹ️  Note: Make sure to run 'npm run build' before running this deployment script"

echo -e "${BLUE}🧹 Step 3: Clearing Laravel caches${NC}"
./vendor/bin/sail artisan config:clear
./vendor/bin/sail artisan cache:clear
./vendor/bin/sail artisan route:clear
./vendor/bin/sail artisan view:clear
echo "✅ Laravel caches cleared"

echo -e "${BLUE}⚡ Step 4: Optimizing Laravel for production${NC}"
# Copy production environment
cp "$ENV_FILE" .env.temp

# Generate optimized files
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
./vendor/bin/sail artisan optimize

# Restore original .env
if [ -f ".env.backup" ]; then
    mv .env.backup .env
else
    rm .env.temp
fi

echo "✅ Laravel optimized for production"

echo -e "${BLUE}📁 Step 5: Creating distribution package${NC}"
mkdir -p "$DIST_DIR"

# Copy essential Laravel files and directories
echo "Copying Laravel core files..."
cp -r app "$DIST_DIR/"
cp -r bootstrap "$DIST_DIR/"
cp -r config "$DIST_DIR/"
cp -r public "$DIST_DIR/"
cp -r resources "$DIST_DIR/"
cp -r routes "$DIST_DIR/"
cp -r storage "$DIST_DIR/"
rm -rf "$DIST_DIR/storage/app/public/*"

# Copy root files
cp artisan "$DIST_DIR/"
cp composer.json "$DIST_DIR/"
cp composer.lock "$DIST_DIR/"
cp "$ENV_FILE" "$DIST_DIR/.env"


# Cleaning up the dev deps
./vendor/bin/sail composer install --optimize-autoloader --no-dev --no-interaction --working-dir=/var/www/html/dist

echo "✅ Core Laravel files copied"

# Create public_html directory structure for shared hosting
echo "Creating public_html structure..."
mkdir -p "$DIST_DIR/public_html"

# Copy all public files including hidden files like .htaccess
cp -r public/* "$DIST_DIR/public_html/" 2>/dev/null || true

# Ensure .htaccess is copied (explicit check)
if [ -f "public/.htaccess" ]; then
    cp public/.htaccess "$DIST_DIR/public_html/"
    echo "✅ .htaccess file copied"
else
    echo "⚠️  Warning: .htaccess file not found in public directory"
fi

# Update index.php paths for shared hosting
sed -i 's|__DIR__.\x27/../vendor/autoload.php\x27|__DIR__.\x27/../vendor/autoload.php\x27|g' "$DIST_DIR/public_html/index.php"
sed -i 's|__DIR__.\x27/../bootstrap/app.php\x27|__DIR__.\x27/../bootstrap/app.php\x27|g' "$DIST_DIR/public_html/index.php"

echo "✅ Public files prepared for shared hosting"

echo -e "${BLUE}🔒 Step 6: Setting proper permissions${NC}"
# Set proper permissions for shared hosting
find "$DIST_DIR/storage" -type f -exec chmod 644 {} \;
find "$DIST_DIR/storage" -type d -exec chmod 755 {} \;
find "$DIST_DIR/bootstrap/cache" -type f -exec chmod 644 {} \;
find "$DIST_DIR/bootstrap/cache" -type d -exec chmod 755 {} \;
chmod -R 755 "$DIST_DIR/storage"
chmod -R 755 "$DIST_DIR/bootstrap/cache"

echo "✅ Permissions set"

echo -e "${BLUE}📝 Step 7: Creating zip file${NC}"
cd $DIST_DIR && zip -qr esperluettes.zip * && cd -

# Calculate package size
PACKAGE_SIZE=$(du -sh "$DIST_DIR/esperluettes.zip" | cut -f1)

echo ""
echo -e "${GREEN}🎉 Deployment package created successfully!${NC}"
echo "=============================================="
echo -e "📦 Package location: ${YELLOW}$DIST_DIR/${NC}"
echo -e "📏 Package size: ${YELLOW}$PACKAGE_SIZE${NC}"
echo ""
echo -e "${YELLOW}📖 Next steps:${NC}"
echo "1. Push the zip file to the FTP"
echo "2. Launch migrations if needed: ./vendor/bin/sail artisan migrate --env=<environment>"
# We are not touching public_html and storage repository because :
# - storage is the repository of the application
# - public_html has a link to storage that should not be messed up with
echo "3. Launch rm -rf app bootstrap config database public resources routes vendor && unzip -o esperluettes.zip"
echo ""
echo -e "${GREEN}Happy deploying! 🚀${NC}"
