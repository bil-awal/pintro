#!/bin/bash
echo "🔍 Checking asset files..."

cd "/Users/bilawalrizky/Documents/2025 - Project/Pintro/transaction-web"

echo "Checking Livewire assets:"
ls -la public/vendor/livewire/ 2>/dev/null || echo "❌ Livewire assets missing"

echo -e "\nChecking Filament CSS:"
ls -la public/css/filament/ 2>/dev/null || echo "❌ Filament CSS missing"

echo -e "\nChecking build assets:"
ls -la public/build/ 2>/dev/null || echo "❌ Build assets missing"

echo -e "\nIf any files are missing, run:"
echo "php artisan livewire:publish --assets --force"
echo "php artisan filament:assets --force"
echo "npm run build"
