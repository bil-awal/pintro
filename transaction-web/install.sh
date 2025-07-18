#!/bin/bash

# Laravel Project Installer Script
# Compatible with Linux and macOS
# Author: Auto-generated for Pintro Transaction Web

echo "🚀 Starting Laravel Project Installation..."
echo "=================================================="

# Colors for better output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ️  $1${NC}"
}

# Check if composer is installed
if ! command -v composer &> /dev/null; then
    print_error "Composer is not installed. Please install Composer first."
    echo "Visit: https://getcomposer.org/download/"
    exit 1
fi

# Check if PHP is installed
if ! command -v php &> /dev/null; then
    print_error "PHP is not installed. Please install PHP first."
    exit 1
fi

# Check PHP version
PHP_VERSION=$(php -r "echo PHP_VERSION;")
print_info "PHP Version: $PHP_VERSION"

# Step 1: Install Composer dependencies
print_info "Installing Composer dependencies..."
if composer install --no-interaction --prefer-dist --optimize-autoloader; then
    print_status "Composer dependencies installed successfully"
else
    print_error "Failed to install Composer dependencies"
    exit 1
fi

# Step 2: Check for package manager and install Node dependencies
print_info "Installing Node.js dependencies..."

if command -v pnpm &> /dev/null && [ -f "pnpm-lock.yaml" ]; then
    print_info "Using pnpm for Node.js dependencies..."
    if pnpm install; then
        print_status "pnpm dependencies installed successfully"
    else
        print_error "Failed to install pnpm dependencies"
        exit 1
    fi
elif command -v npm &> /dev/null; then
    print_info "Using npm for Node.js dependencies..."
    if npm install; then
        print_status "npm dependencies installed successfully"
    else
        print_error "Failed to install npm dependencies"
        exit 1
    fi
else
    print_error "Neither npm nor pnpm is installed. Please install Node.js first."
    echo "Visit: https://nodejs.org/"
    exit 1
fi

# Step 3: Environment file setup
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        print_info "Creating .env file from .env.example..."
        cp .env.example .env
        print_status ".env file created"
    else
        print_warning ".env.example not found. Please create .env file manually."
    fi
else
    print_info ".env file already exists"
fi

# Step 4: Generate application key
print_info "Generating application key..."
if php artisan key:generate --ansi; then
    print_status "Application key generated"
else
    print_error "Failed to generate application key"
fi

# Step 5: Create storage link
print_info "Creating storage symbolic link..."
if php artisan storage:link; then
    print_status "Storage link created"
else
    print_warning "Storage link creation failed (might already exist)"
fi

# Step 6: Run database migrations
print_info "Running database migrations..."
read -p "Do you want to run database migrations? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if php artisan migrate --force; then
        print_status "Database migrations completed"
    else
        print_error "Database migrations failed"
        print_warning "Please check your database configuration in .env file"
    fi
else
    print_info "Skipping database migrations"
fi

# Step 7: Run database seeders
print_info "Running database seeders..."
read -p "Do you want to run database seeders? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if php artisan db:seed --force; then
        print_status "Database seeders completed"
    else
        print_error "Database seeders failed"
    fi
else
    print_info "Skipping database seeders"
fi

# Step 8: Build assets
print_info "Building frontend assets..."
read -p "Do you want to build frontend assets? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    if command -v pnpm &> /dev/null && [ -f "pnpm-lock.yaml" ]; then
        if pnpm run build; then
            print_status "Assets built successfully with pnpm"
        else
            print_warning "Asset building failed, but installation continues"
        fi
    elif command -v npm &> /dev/null; then
        if npm run build; then
            print_status "Assets built successfully with npm"
        else
            print_warning "Asset building failed, but installation continues"
        fi
    fi
else
    print_info "Skipping asset building"
fi

# Step 9: Clear caches
print_info "Clearing application caches..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
print_status "Caches cleared"

echo
echo "=================================================="
print_status "🎉 Installation completed successfully!"
echo "=================================================="
echo

# Step 10: Start the development server
print_info "Starting development server..."
read -p "Do you want to start the development server now? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    print_info "You can start the server later with: php artisan serve"
    echo
    print_info "Default URL: http://localhost:8000"
else
    print_status "Starting Laravel development server..."
    print_info "Server will be available at: http://localhost:8000"
    print_info "Press Ctrl+C to stop the server"
    echo
    
    # Open browser (optional)
    read -p "Do you want to open the app in browser? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        # Try to open browser
        if command -v open &> /dev/null; then
            # macOS
            open http://localhost:8000 &
        elif command -v xdg-open &> /dev/null; then
            # Linux
            xdg-open http://localhost:8000 &
        else
            print_info "Please open http://localhost:8000 in your browser"
        fi
    fi
    
    # Start server
    php artisan serve
fi

echo
print_status "Thank you for using the Laravel installer! 🚀"
