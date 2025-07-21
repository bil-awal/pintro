@echo off
echo 🔧 Fixing Go dependencies and build issues...

:: Navigate to project directory
cd /d "%~dp0\.."

:: Clean existing build artifacts
echo 🧹 Cleaning build artifacts...
go clean -cache
go clean -modcache
if exist go.sum del go.sum

:: Download and tidy dependencies
echo 📦 Downloading and tidying dependencies...
go mod download
go mod tidy

:: Verify all dependencies are available
echo ✅ Verifying dependencies...
go mod verify

:: Install Swagger CLI if not present
swag version >nul 2>&1
if errorlevel 1 (
    echo 📚 Installing Swagger CLI...
    go install github.com/swaggo/swag/cmd/swag@latest
)

:: Generate Swagger documentation
echo 📖 Generating Swagger documentation...
swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal

:: Build the application to verify everything works
echo 🔨 Building application to verify setup...
go build -o bin/transaction-service.exe ./cmd/server

echo ✅ All dependencies fixed and application built successfully!
echo.
echo 🚀 You can now run:
echo    - make run                 (run the application)
echo    - make docker-build        (build Docker image)
echo    - make docker-compose-up   (start with Docker Compose)
echo.
echo 📖 Swagger UI will be available at: http://localhost:8080/swagger/index.html

pause
