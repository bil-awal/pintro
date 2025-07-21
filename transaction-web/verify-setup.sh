#!/bin/bash

echo "🔍 Verifikasi Laravel Filament Setup..."

# Check if server is running
echo "1. Checking if server is running on port 8000..."
if lsof -i :8000 > /dev/null 2>&1; then
    echo "✅ Server is running on port 8000"
else
    echo "❌ Server is NOT running on port 8000"
    echo "💡 Run: php artisan serve --host=0.0.0.0 --port=8000"
fi

# Check if assets exist
echo -e "\n2. Checking Livewire assets..."
if [ -f "public/vendor/livewire/livewire.js" ]; then
    echo "✅ Livewire.js exists"
else
    echo "❌ Livewire.js missing"
    echo "💡 Run: php artisan livewire:publish --assets"
fi

# Check if Filament CSS exists
echo -e "\n3. Checking Filament assets..."
if [ -d "public/css/filament" ]; then
    echo "✅ Filament CSS directory exists"
else
    echo "❌ Filament CSS directory missing"
    echo "💡 Run: php artisan filament:assets"
fi

# Check environment
echo -e "\n4. Checking environment configuration..."
APP_ENV=$(grep "^APP_ENV=" .env | cut -d '=' -f2)
APP_URL=$(grep "^APP_URL=" .env | cut -d '=' -f2)
echo "APP_ENV: $APP_ENV"
echo "APP_URL: $APP_URL"

if [ "$APP_ENV" = "local" ] && [ "$APP_URL" = "http://localhost:8000" ]; then
    echo "✅ Environment configuration correct"
else
    echo "❌ Environment configuration needs fixing"
fi

# Test URL accessibility
echo -e "\n5. Testing URL accessibility..."
if curl -s -o /dev/null -w "%{http_code}" http://localhost:8000 | grep -q "200\|302"; then
    echo "✅ Application accessible at http://localhost:8000"
else
    echo "❌ Application not accessible"
    echo "💡 Make sure server is running and port is not blocked"
fi

echo -e "\n🎯 Verification complete!"
