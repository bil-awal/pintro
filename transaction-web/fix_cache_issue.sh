#!/bin/bash

# Navigate to the Laravel project directory
cd "/Users/bilawalrizky/Documents/2025 - Project/Pintro/transaction-web"

echo "🧹 Clearing Laravel caches..."

# Clear view cache
echo "Clearing view cache..."
php artisan view:clear

# Clear config cache  
echo "Clearing config cache..."
php artisan config:clear

# Clear route cache
echo "Clearing route cache..."
php artisan route:clear

# Clear application cache
echo "Clearing application cache..."
php artisan cache:clear

# Clear compiled classes
echo "Clearing compiled classes..."
php artisan clear-compiled

# Optimize for production (optional)
echo "Optimizing application..."
php artisan optimize:clear

echo "✅ All caches cleared successfully!"
echo ""
echo "🚀 Now try accessing your admin dashboard again."
echo "The 'Cannot redeclare class' error should be resolved."

# Optional: Show current PHP version and Laravel version
echo ""
echo "📋 System Info:"
echo "PHP Version: $(php --version | head -n 1)"
echo "Laravel Version: $(php artisan --version)"
