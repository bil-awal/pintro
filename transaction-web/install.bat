@echo off
setlocal enabledelayedexpansion

REM Laravel Project Installer Script
REM Compatible with Windows
REM Author: Auto-generated for Pintro Transaction Web

echo.
echo =================================================
echo 🚀 Starting Laravel Project Installation...
echo =================================================
echo.

REM Check if composer is installed
where composer >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ Composer is not installed. Please install Composer first.
    echo Visit: https://getcomposer.org/download/
    pause
    exit /b 1
)

REM Check if PHP is installed
where php >nul 2>nul
if %errorlevel% neq 0 (
    echo ❌ PHP is not installed. Please install PHP first.
    pause
    exit /b 1
)

REM Get PHP version
for /f "tokens=*" %%i in ('php -r "echo PHP_VERSION;"') do set PHP_VERSION=%%i
echo ℹ️  PHP Version: %PHP_VERSION%
echo.

REM Step 1: Install Composer dependencies
echo ℹ️  Installing Composer dependencies...
composer install --no-interaction --prefer-dist --optimize-autoloader
if %errorlevel% neq 0 (
    echo ❌ Failed to install Composer dependencies
    pause
    exit /b 1
)
echo ✅ Composer dependencies installed successfully
echo.

REM Step 2: Install Node dependencies
echo ℹ️  Installing Node.js dependencies...

REM Check for pnpm first, then npm
where pnpm >nul 2>nul
if %errorlevel% equ 0 (
    if exist "pnpm-lock.yaml" (
        echo ℹ️  Using pnpm for Node.js dependencies...
        pnpm install
        if !errorlevel! neq 0 (
            echo ❌ Failed to install pnpm dependencies
            pause
            exit /b 1
        )
        echo ✅ pnpm dependencies installed successfully
    ) else (
        goto use_npm
    )
) else (
    :use_npm
    where npm >nul 2>nul
    if !errorlevel! equ 0 (
        echo ℹ️  Using npm for Node.js dependencies...
        npm install
        if !errorlevel! neq 0 (
            echo ❌ Failed to install npm dependencies
            pause
            exit /b 1
        )
        echo ✅ npm dependencies installed successfully
    ) else (
        echo ❌ Neither npm nor pnpm is installed. Please install Node.js first.
        echo Visit: https://nodejs.org/
        pause
        exit /b 1
    )
)
echo.

REM Step 3: Environment file setup
if not exist ".env" (
    if exist ".env.example" (
        echo ℹ️  Creating .env file from .env.example...
        copy ".env.example" ".env" >nul
        echo ✅ .env file created
    ) else (
        echo ⚠️  .env.example not found. Please create .env file manually.
    )
) else (
    echo ℹ️  .env file already exists
)
echo.

REM Step 4: Generate application key
echo ℹ️  Generating application key...
php artisan key:generate --ansi
if %errorlevel% neq 0 (
    echo ❌ Failed to generate application key
) else (
    echo ✅ Application key generated
)
echo.

REM Step 5: Create storage link
echo ℹ️  Creating storage symbolic link...
php artisan storage:link
if %errorlevel% neq 0 (
    echo ⚠️  Storage link creation failed (might already exist)
) else (
    echo ✅ Storage link created
)
echo.

REM Step 6: Run database migrations
echo ℹ️  Running database migrations...
set /p migrate_choice="Do you want to run database migrations? (y/N): "
if /i "!migrate_choice!"=="y" (
    php artisan migrate --force
    if !errorlevel! neq 0 (
        echo ❌ Database migrations failed
        echo ⚠️  Please check your database configuration in .env file
    ) else (
        echo ✅ Database migrations completed
    )
) else (
    echo ℹ️  Skipping database migrations
)
echo.

REM Step 7: Run database seeders
echo ℹ️  Running database seeders...
set /p seed_choice="Do you want to run database seeders? (y/N): "
if /i "!seed_choice!"=="y" (
    php artisan db:seed --force
    if !errorlevel! neq 0 (
        echo ❌ Database seeders failed
    ) else (
        echo ✅ Database seeders completed
    )
) else (
    echo ℹ️  Skipping database seeders
)
echo.

REM Step 8: Build assets
echo ℹ️  Building frontend assets...
set /p build_choice="Do you want to build frontend assets? (y/N): "
if /i "!build_choice!"=="y" (
    where pnpm >nul 2>nul
    if !errorlevel! equ 0 (
        if exist "pnpm-lock.yaml" (
            pnpm run build
            if !errorlevel! neq 0 (
                echo ⚠️  Asset building failed, but installation continues
            ) else (
                echo ✅ Assets built successfully with pnpm
            )
        ) else (
            goto build_npm
        )
    ) else (
        :build_npm
        where npm >nul 2>nul
        if !errorlevel! equ 0 (
            npm run build
            if !errorlevel! neq 0 (
                echo ⚠️  Asset building failed, but installation continues
            ) else (
                echo ✅ Assets built successfully with npm
            )
        )
    )
) else (
    echo ℹ️  Skipping asset building
)
echo.

REM Step 9: Clear caches
echo ℹ️  Clearing application caches...
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
echo ✅ Caches cleared
echo.

echo =================================================
echo ✅ 🎉 Installation completed successfully!
echo =================================================
echo.

REM Step 10: Start the development server
echo ℹ️  Starting development server...
set /p server_choice="Do you want to start the development server now? (Y/n): "
if /i "!server_choice!"=="n" (
    echo ℹ️  You can start the server later with: php artisan serve
    echo.
    echo ℹ️  Default URL: http://localhost:8000
    pause
    exit /b 0
) else (
    echo ✅ Starting Laravel development server...
    echo ℹ️  Server will be available at: http://localhost:8000
    echo ℹ️  Press Ctrl+C to stop the server
    echo.
    
    REM Open browser (optional)
    set /p browser_choice="Do you want to open the app in browser? (Y/n): "
    if /i not "!browser_choice!"=="n" (
        start http://localhost:8000
    )
    
    REM Start server
    php artisan serve
)

echo.
echo ✅ Thank you for using the Laravel installer! 🚀
pause
