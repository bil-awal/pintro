# 🔧 Manual Fix Commands - Laravel Filament CSP Error

## Jalankan command ini satu per satu di terminal:

### 1. Navigate ke project directory
```bash
cd "/Users/bilawalrizky/Documents/2025 - Project/Pintro/transaction-web"
```

### 2. Clear semua cache Laravel
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan event:clear
```

### 3. Publish Livewire assets
```bash
php artisan livewire:publish --assets --force
```

### 4. Publish Filament assets  
```bash
php artisan filament:assets --force
```

### 5. Install dan build NPM assets
```bash
npm install
npm run build
```

### 6. Create storage link
```bash
php artisan storage:link
```

### 7. Optimize for development
```bash
php artisan config:cache
```

### 8. Restart development server
```bash
php artisan serve --host=0.0.0.0 --port=8000
```

## Jika masih error, coba ini:

### 9. Reset composer autoload
```bash
composer dump-autoload
```

### 10. Reset npm cache
```bash
npm cache clean --force
rm -rf node_modules
npm install
npm run build
```

### 11. Check file permissions (if on Linux/Mac)
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
chmod -R 755 public
```

## Verifikasi setelah fix:

1. Buka: http://localhost:8000/admin
2. Check browser console (F12) - should have no CSP errors
3. Check network tab - all CSS/JS files should load successfully

## Jika masih gagal:

Cek file-file ini sudah ada:
- public/vendor/livewire/livewire.js
- public/css/filament/forms/forms.css  
- public/css/filament/support/support.css
- public/css/filament/app.css

Jika tidak ada, ulangi step 3-4.
