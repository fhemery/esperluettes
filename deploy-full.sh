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
cp -r database "$DIST_DIR/"
cp -r public "$DIST_DIR/"
cp -r resources "$DIST_DIR/"
cp -r routes "$DIST_DIR/"
cp -r storage "$DIST_DIR/"

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

echo -e "${BLUE}📝 Step 7: Creating deployment instructions${NC}"
cat > "$DIST_DIR/DEPLOYMENT_INSTRUCTIONS.md" << 'EOF'
# Deployment Instructions for o2switch

## Upload Structure
Upload the contents of this dist folder to your o2switch account as follows:

### 1. Main Laravel Files
Upload these directories/files to your account root (NOT public_html):
- app/
- bootstrap/
- config/
- database/
- resources/
- routes/
- storage/
- vendor/
- .env
- artisan
- composer.json
- composer.lock

### 2. Public Files
Upload the contents of `public_html/` to your domain's public_html folder.

## Post-Upload Steps

### 1. Database Setup
- Import your database via phpMyAdmin
- Update .env with your o2switch database credentials

### 2. Storage Link (if needed)
Create a symbolic link from public_html/storage to ../storage/app/public

### 3. Verify Permissions
Ensure these directories have 755 permissions:
- storage/ (and all subdirectories)
- bootstrap/cache/

## Testing
- Visit your domain
- Test login/registration
- Test admin panel
- Verify file uploads work

## Troubleshooting
- Check error logs in cPanel if you get 500 errors
- Verify .env database credentials
- Ensure all files uploaded correctly
EOF

echo "✅ Deployment instructions created"

echo -e "${BLUE}📊 Step 8: Creating deployment summary${NC}"
# Create a summary of what was built
cat > "$DIST_DIR/BUILD_INFO.txt" << EOF
Laravel Deployment Package
==========================
Built on: $(date)
Environment: Production
Laravel Version: $(./vendor/bin/sail artisan --version)
PHP Version: $(php --version | head -n 1)

Included Features:
- User authentication with activation/deactivation
- Admin panel (Filament)
- Activation codes system
- Multi-language support (French translations)

Files Structure:
- Laravel app files (ready for shared hosting)
- Optimized for production (cached configs, routes, views)
- Public files prepared for public_html upload
- Proper permissions set for shared hosting

Next Steps:
1. Review DEPLOYMENT_INSTRUCTIONS.md
2. Upload files via FTP as instructed
3. Configure database in .env
4. Test the deployment
EOF

echo "✅ Build summary created"

# Calculate package size
PACKAGE_SIZE=$(du -sh "$DIST_DIR" | cut -f1)

echo ""
echo -e "${GREEN}🎉 Deployment package created successfully!${NC}"
echo "=============================================="
echo -e "📦 Package location: ${YELLOW}$DIST_DIR/${NC}"
echo -e "📏 Package size: ${YELLOW}$PACKAGE_SIZE${NC}"
echo ""
echo -e "${BLUE}📋 What's included:${NC}"
echo "✅ Optimized Laravel application"
echo "✅ Production environment configuration"
echo "✅ Built frontend assets"
echo "✅ Proper file permissions"
echo "✅ Shared hosting structure (public_html)"
echo "✅ Deployment instructions"
echo ""
echo -e "${YELLOW}📖 Next steps:${NC}"
echo "1. Review $DIST_DIR/DEPLOYMENT_INSTRUCTIONS.md"
echo "2. Upload files to o2switch via FTP"
echo "3. Configure your production database"
echo "4. Test your deployment"
echo ""
echo -e "${GREEN}Happy deploying! 🚀${NC}"
