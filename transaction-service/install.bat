@echo off
setlocal enabledelayedexpansion

REM ========================================================
REM Go Transaction Service Installer Script
REM Compatible with Windows
REM Author: Auto-generated for Pintro Go Transaction Service
REM Version: 2.0
REM ========================================================

echo.
echo ███████╗ ██╗███╗   ██╗████████╗██████╗  ██████╗ 
echo ██╔════╝ ██║████╗  ██║╚══██╔══╝██╔══██╗██╔═══██╗
echo █████╗   ██║██╔██╗ ██║   ██║   ██████╔╝██║   ██║
echo ██╔══╝   ██║██║╚██╗██║   ██║   ██╔══██╗██║   ██║
echo ██║      ██║██║ ╚████║   ██║   ██║  ██║╚██████╔╝
echo ╚═╝      ╚═╝╚═╝  ╚═══╝   ╚═╝   ╚═╝  ╚═╝ ╚═════╝ 
echo.
echo =======================================================
echo 🚀 Go Transaction Service Installer v2.0
echo 🏗️  Clean Architecture • 🔐 Secure • 🚄 Fast
echo =======================================================
echo.

REM Function to print colored messages
call :print_success "Starting installation process..."
echo.

REM Step 0: Pre-installation checks
call :print_info "Performing pre-installation checks..."

REM Check if we're in the correct directory
if not exist "go.mod" (
    call :print_error "go.mod not found. Please run this script from the project root directory."
    pause
    exit /b 1
)

if not exist "cmd\server\main.go" (
    call :print_error "main.go not found in cmd/server/. Please ensure you're in the correct project directory."
    pause
    exit /b 1
)

call :print_success "✅ Project structure validated"

REM Check system requirements
call :print_info "Checking system requirements..."

REM Check Go installation and version
where go >nul 2>nul
if %errorlevel% neq 0 (
    call :print_error "Go is not installed or not in PATH."
    echo.
    echo 📥 Please install Go from: https://golang.org/dl/
    echo 💡 Recommended version: Go 1.19 or higher
    pause
    exit /b 1
)

REM Get and validate Go version
for /f "tokens=3" %%i in ('go version 2^>nul') do set GO_VERSION=%%i
echo ℹ️  Detected Go Version: %GO_VERSION%

REM Extract version number for comparison
for /f "tokens=1 delims=." %%a in ("%GO_VERSION:go=%") do set GO_MAJOR=%%a
for /f "tokens=2 delims=." %%a in ("%GO_VERSION:go=%") do set GO_MINOR=%%a

if %GO_MAJOR% LSS 1 (
    call :print_error "Go version 1.19 or higher is required. Current version: %GO_VERSION%"
    pause
    exit /b 1
)
if %GO_MAJOR% EQU 1 if %GO_MINOR% LSS 19 (
    call :print_error "Go version 1.19 or higher is required. Current version: %GO_VERSION%"
    pause
    exit /b 1
)

call :print_success "✅ Go version compatible: %GO_VERSION%"

REM Check Docker availability
where docker >nul 2>nul
if %errorlevel% neq 0 (
    call :print_warning "Docker is not installed. Database setup will be manual."
    set DOCKER_AVAILABLE=false
) else (
    docker --version >nul 2>nul
    if !errorlevel! neq 0 (
        call :print_warning "Docker is installed but not running."
        set DOCKER_AVAILABLE=false
    ) else (
        call :print_success "✅ Docker is available"
        set DOCKER_AVAILABLE=true
    )
)

REM Check for Docker Compose
set DOCKER_COMPOSE_AVAILABLE=false
set DOCKER_COMPOSE_CMD=

if "%DOCKER_AVAILABLE%"=="true" (
    where docker-compose >nul 2>nul
    if !errorlevel! equ 0 (
        set DOCKER_COMPOSE_AVAILABLE=true
        set DOCKER_COMPOSE_CMD=docker-compose
        call :print_success "✅ Docker Compose (legacy) available"
    ) else (
        docker compose version >nul 2>nul
        if !errorlevel! equ 0 (
            set DOCKER_COMPOSE_AVAILABLE=true
            set DOCKER_COMPOSE_CMD=docker compose
            call :print_success "✅ Docker Compose (plugin) available"
        ) else (
            call :print_warning "Docker Compose is not available"
        )
    )
)

REM Check for Make (optional)
where make >nul 2>nul
if %errorlevel% equ 0 (
    call :print_success "✅ Make is available"
    set MAKE_AVAILABLE=true
) else (
    call :print_info "ℹ️  Make not found - will use direct Go commands"
    set MAKE_AVAILABLE=false
)

echo.
call :print_info "System check completed. Starting installation..."
echo.

REM Step 1: Create necessary directories
call :print_info "Creating project directories..."
if not exist "bin" mkdir bin
if not exist "logs" mkdir logs
if not exist "tmp" mkdir tmp
call :print_success "✅ Directories created"

REM Step 2: Environment setup
call :print_info "Setting up environment configuration..."
if not exist ".env" (
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
        call :print_success "✅ Environment file created from template"
        call :print_warning "⚠️  Please review and update .env file with your settings:"
        echo     • Database credentials
        echo     • JWT secret key
        echo     • Midtrans API keys
        echo.
    ) else (
        call :print_error ".env.example not found. Cannot create environment file."
        echo Please create .env file manually with required configuration.
        pause
        exit /b 1
    )
) else (
    call :print_info "ℹ️  Environment file (.env) already exists"
)

REM Step 3: Download and verify dependencies
call :print_info "Downloading Go dependencies..."
go mod download
if %errorlevel% neq 0 (
    call :print_error "Failed to download Go dependencies"
    echo.
    echo 🔧 Troubleshooting:
    echo   • Check your internet connection
    echo   • Verify go.mod file integrity
    echo   • Try: go clean -modcache
    pause
    exit /b 1
)
call :print_success "✅ Dependencies downloaded successfully"

REM Step 4: Verify and tidy modules
call :print_info "Verifying Go modules..."
go mod verify
if %errorlevel% neq 0 (
    call :print_warning "⚠️  Module verification failed, attempting to fix..."
    go mod tidy
    if !errorlevel! neq 0 (
        call :print_error "Failed to tidy Go modules"
        pause
        exit /b 1
    )
) else (
    call :print_success "✅ Modules verified successfully"
)

go mod tidy
if %errorlevel% equ 0 (
    call :print_success "✅ Dependencies tidied"
) else (
    call :print_warning "⚠️  Failed to tidy modules, but continuing..."
)

REM Step 5: Install development tools
call :print_info "Installing development tools..."

REM Install migrate tool
where migrate >nul 2>nul
if %errorlevel% equ 0 (
    call :print_info "ℹ️  Migration tool already installed"
) else (
    call :print_info "Installing golang-migrate..."
    go install -tags "postgres" github.com/golang-migrate/migrate/v4/cmd/migrate@latest
    if !errorlevel! neq 0 (
        call :print_warning "⚠️  Failed to install migration tool"
        echo You can install it manually later with:
        echo go install -tags "postgres" github.com/golang-migrate/migrate/v4/cmd/migrate@latest
    ) else (
        call :print_success "✅ Migration tool installed"
    )
)

REM Install air for live reloading (optional)
where air >nul 2>nul
if %errorlevel% neq 0 (
    set /p air_choice="Install Air for live reloading? (recommended for development) (Y/n): "
    if /i not "!air_choice!"=="n" (
        call :print_info "Installing Air..."
        go install github.com/cosmtrek/air@latest
        if !errorlevel! neq 0 (
            call :print_warning "⚠️  Failed to install Air"
        ) else (
            call :print_success "✅ Air installed for live reloading"
        )
    )
) else (
    call :print_info "ℹ️  Air already installed"
)

echo.

REM Step 6: Database setup
call :print_info "=== Database Setup ==="
echo.

if "%DOCKER_AVAILABLE%"=="true" if "%DOCKER_COMPOSE_AVAILABLE%"=="true" (
    echo 🐳 Docker setup options:
    echo   1. Start PostgreSQL with Docker Compose (recommended)
    echo   2. Use external PostgreSQL database
    echo   3. Skip database setup
    echo.
    set /p db_choice="Choose an option (1-3) [1]: "
    if "!db_choice!"=="" set db_choice=1
    
    if "!db_choice!"=="1" (
        call :print_info "Starting PostgreSQL with Docker Compose..."
        %DOCKER_COMPOSE_CMD% up -d postgres
        if !errorlevel! neq 0 (
            call :print_error "Failed to start PostgreSQL"
            echo.
            echo 🔧 Troubleshooting:
            echo   • Check if port 5432 is available
            echo   • Verify docker-compose.yml exists
            echo   • Try: docker-compose down then retry
            pause
            exit /b 1
        )
        call :print_success "✅ PostgreSQL started successfully"
        
        REM Wait for PostgreSQL to be ready
        call :print_info "Waiting for PostgreSQL to be ready..."
        call :wait_for_postgres
        call :print_success "✅ PostgreSQL is ready"
        
        set DATABASE_READY=true
    ) else if "!db_choice!"=="2" (
        call :print_info "Using external PostgreSQL database"
        call :print_warning "⚠️  Please ensure PostgreSQL is running and accessible"
        set DATABASE_READY=true
    ) else (
        call :print_warning "⚠️  Database setup skipped"
        set DATABASE_READY=false
    )
) else (
    call :print_warning "⚠️  Docker not available. Please ensure PostgreSQL is running manually."
    echo.
    echo 📖 Manual PostgreSQL setup:
    echo   1. Install PostgreSQL 15+
    echo   2. Create database: transaction_db
    echo   3. Update .env file with database credentials
    echo.
    set /p manual_db="Is PostgreSQL ready and configured? (y/N): "
    if /i "!manual_db!"=="y" (
        set DATABASE_READY=true
    ) else (
        set DATABASE_READY=false
    )
)

echo.

REM Step 7: Run database migrations
if "%DATABASE_READY%"=="true" (
    set /p migrate_choice="Run database migrations? (Y/n): "
    if /i not "!migrate_choice!"=="n" (
        call :print_info "Running database migrations..."
        
        REM Load database configuration from .env
        call :load_env_vars
        
        REM Set defaults if not provided
        if not defined DB_USERNAME set DB_USERNAME=postgres
        if not defined DB_PASSWORD set DB_PASSWORD=password
        if not defined DB_HOST set DB_HOST=localhost
        if not defined DB_PORT set DB_PORT=5432
        if not defined DB_DATABASE set DB_DATABASE=transaction_db
        if not defined DB_SSL_MODE set DB_SSL_MODE=disable
        
        set "DB_URL=postgres://!DB_USERNAME!:!DB_PASSWORD!@!DB_HOST!:!DB_PORT!/!DB_DATABASE!?sslmode=!DB_SSL_MODE!"
        
        where migrate >nul 2>nul
        if !errorlevel! equ 0 (
            migrate -path migrations -database "!DB_URL!" up
            if !errorlevel! neq 0 (
                call :print_error "Database migrations failed"
                echo.
                echo 🔧 Troubleshooting:
                echo   • Check database connectivity
                echo   • Verify credentials in .env file
                echo   • Ensure database 'transaction_db' exists
                echo   • Check migration files in migrations/ directory
                echo.
                set /p continue_choice="Continue installation anyway? (y/N): "
                if /i not "!continue_choice!"=="y" (
                    pause
                    exit /b 1
                )
            ) else (
                call :print_success "✅ Database migrations completed successfully"
            )
        ) else (
            call :print_warning "⚠️  Migration tool not found"
            echo You can run migrations manually later with:
            echo migrate -path migrations -database "!DB_URL!" up
        )
    ) else (
        call :print_info "ℹ️  Database migrations skipped"
    )
) else (
    call :print_warning "⚠️  Database not ready, skipping migrations"
)

echo.

REM Step 8: Build the application
call :print_info "=== Building Application ==="
echo.

if "%MAKE_AVAILABLE%"=="true" (
    call :print_info "Building with Make..."
    make build
    if !errorlevel! neq 0 (
        call :print_warning "⚠️  Make build failed, trying direct build..."
        goto :direct_build
    ) else (
        call :print_success "✅ Application built successfully with Make"
        goto :build_complete
    )
) else (
    :direct_build
    call :print_info "Building with Go build..."
    go build -ldflags="-s -w" -o bin/go-transaction-service.exe ./cmd/server
    if !errorlevel! neq 0 (
        call :print_error "Failed to build application"
        echo.
        echo 🔧 Troubleshooting:
        echo   • Check for compilation errors above
        echo   • Verify all dependencies are available
        echo   • Try: go clean -cache
        pause
        exit /b 1
    )
    call :print_success "✅ Application built successfully"
)

:build_complete

REM Step 9: Run tests (optional)
echo.
set /p test_choice="Run application tests? (recommended) (Y/n): "
if /i not "!test_choice!"=="n" (
    call :print_info "Running tests..."
    if "%MAKE_AVAILABLE%"=="true" (
        make test
        set test_result=!errorlevel!
    ) else (
        go test -v ./tests/...
        set test_result=!errorlevel!
    )
    
    if !test_result! neq 0 (
        call :print_warning "⚠️  Some tests failed"
        echo This might indicate configuration issues, but installation can continue.
        set /p continue_choice="Continue anyway? (Y/n): "
        if /i "!continue_choice!"=="n" (
            pause
            exit /b 1
        )
    ) else (
        call :print_success "✅ All tests passed"
    )
) else (
    call :print_info "ℹ️  Tests skipped"
)

echo.

REM Step 10: Create startup scripts
call :print_info "Creating startup scripts..."

REM Create run.bat for easy startup
echo @echo off > run.bat
echo echo Starting Go Transaction Service... >> run.bat
echo echo Service will be available at: http://localhost:8080 >> run.bat
echo echo Health check: http://localhost:8080/health >> run.bat
echo echo Press Ctrl+C to stop the service >> run.bat
echo echo. >> run.bat
if "%MAKE_AVAILABLE%"=="true" (
    echo make run >> run.bat
) else (
    echo go run cmd/server/main.go >> run.bat
)

call :print_success "✅ Startup script created: run.bat"

echo.

REM Installation complete message
call :print_success "================================================="
call :print_success "🎉 INSTALLATION COMPLETED SUCCESSFULLY! 🎉"
call :print_success "================================================="
echo.

echo 📋 Installation Summary:
echo   ✅ Go dependencies installed
echo   ✅ Environment configured
echo   ✅ Application built
if "%DATABASE_READY%"=="true" (
    echo   ✅ Database ready
) else (
    echo   ⚠️  Database setup pending
)
echo   ✅ Startup scripts created
echo.

echo 🚀 Quick Start Commands:
echo   • Start service:           run.bat
echo   • Start with Make:         make run
echo   • Build application:       make build
echo   • Run tests:              make test
echo   • Start with Docker:       docker-compose up -d
echo   • View help:              make help
echo.

echo 🌐 Service URLs:
echo   • Application:            http://localhost:8080
echo   • Health Check:           http://localhost:8080/health
echo   • API Base:               http://localhost:8080/api/v1
echo.

echo 📚 Next Steps:
echo   1. Review and update .env file with your configuration
if "%DATABASE_READY%"=="false" (
    echo   2. Set up PostgreSQL database
    echo   3. Run database migrations
)
echo   2. Start the service with: run.bat
echo   3. Test API endpoints using the documentation
echo   4. Review logs in logs/ directory
echo.

REM Start the service
set /p start_choice="Start the Go Transaction Service now? (Y/n): "
if /i not "!start_choice!"=="n" (
    echo.
    call :print_success "🚀 Starting Go Transaction Service..."
    echo.
    echo ℹ️  Service starting at: http://localhost:8080
    echo ℹ️  Health check: http://localhost:8080/health
    echo ℹ️  API documentation: http://localhost:8080/api/v1
    echo ℹ️  Press Ctrl+C to stop the service
    echo.
    
    REM Optional browser launch
    set /p browser_choice="Open health check in browser? (Y/n): "
    if /i not "!browser_choice!"=="n" (
        timeout /t 2 /nobreak >nul
        start http://localhost:8080/health
    )
    
    echo 🎬 Starting service...
    echo.
    
    REM Start the service
    if "%MAKE_AVAILABLE%"=="true" (
        make run
    ) else (
        go run cmd/server/main.go
    )
) else (
    echo.
    call :print_info "You can start the service later with any of these commands:"
    echo   • run.bat
    echo   • make run
    echo   • go run cmd/server/main.go
    echo.
)

echo.
call :print_success "✅ Thank you for using Go Transaction Service! 🙏"
echo.
echo 📞 Support:
echo   • Documentation: README.md
echo   • Email: recruitment@pintro.dev
echo   • GitHub Issues: Create an issue for bugs/features
echo.

pause
goto :eof

REM ========================================================
REM Helper Functions
REM ========================================================

:print_success
echo [32m%~1[0m
goto :eof

:print_error
echo [31m%~1[0m
goto :eof

:print_warning
echo [33m%~1[0m
goto :eof

:print_info
echo [36m%~1[0m
goto :eof

:wait_for_postgres
set /a count=0
:wait_loop
set /a count+=1
if %count% GTR 30 (
    call :print_warning "⚠️  PostgreSQL is taking longer than expected to start"
    goto :eof
)
timeout /t 2 /nobreak >nul 2>nul
ping -n 1 localhost >nul 2>nul
if %errorlevel% equ 0 (
    call :print_info "Checking PostgreSQL connection attempt %count%/30..."
)
goto :wait_loop

:load_env_vars
if exist ".env" (
    for /f "usebackq tokens=1,2 delims==" %%a in (".env") do (
        if not "%%a"=="" if not "%%a:~0,1%"=="#" (
            set "%%a=%%b"
        )
    )
)
goto :eof
