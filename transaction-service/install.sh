#!/bin/bash

# Go Transaction Service Installer Script
# Compatible with Linux and macOS
# Author: Auto-generated for Pintro Go Transaction Service

echo "🚀 Starting Go Transaction Service Installation..."
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

# Check if Go is installed
check_go() {
    if ! command -v go &> /dev/null; then
        print_error "Go is not installed. Please install Go first."
        echo "Visit: https://golang.org/dl/"
        exit 1
    fi
    
    GO_VERSION=$(go version | cut -d' ' -f3)
    print_info "Go Version: $GO_VERSION"
}

# Check if Docker is installed
check_docker() {
    if ! command -v docker &> /dev/null; then
        print_warning "Docker is not installed. Database setup will be skipped."
        return 1
    fi
    
    if ! command -v docker-compose &> /dev/null && ! command -v docker compose &> /dev/null; then
        print_warning "Docker Compose is not installed. Database setup will be skipped."
        return 1
    fi
    
    return 0
}

# Check if PostgreSQL is running
check_postgres() {
    if command -v psql &> /dev/null; then
        if pg_isready -h localhost -p 5432 &> /dev/null; then
            print_info "PostgreSQL is already running locally"
            return 0
        fi
    fi
    return 1
}

# Step 1: Check prerequisites
print_info "Checking prerequisites..."
check_go

echo

# Step 2: Download Go dependencies
print_info "Downloading Go dependencies..."
if go mod download; then
    print_status "Go dependencies downloaded successfully"
else
    print_error "Failed to download Go dependencies"
    exit 1
fi

# Step 3: Tidy dependencies
print_info "Tidying Go modules..."
if go mod tidy; then
    print_status "Go modules tidied successfully"
else
    print_warning "Failed to tidy Go modules, but continuing..."
fi

echo

# Step 4: Environment file setup
if [ ! -f ".env" ]; then
    if [ -f ".env.example" ]; then
        print_info "Creating .env file from .env.example..."
        cp .env.example .env
        print_status ".env file created"
        print_warning "Please update .env file with your configuration before running the service"
    else
        print_warning ".env.example not found. Please create .env file manually."
    fi
else
    print_info ".env file already exists"
fi

echo

# Step 5: Install migrate tool
print_info "Installing database migration tool..."
if command -v migrate &> /dev/null; then
    print_info "Migration tool already installed"
else
    if go install -tags 'postgres' github.com/golang-migrate/migrate/v4/cmd/migrate@latest; then
        print_status "Migration tool installed successfully"
    else
        print_error "Failed to install migration tool"
        print_warning "You can install it manually: go install -tags 'postgres' github.com/golang-migrate/migrate/v4/cmd/migrate@latest"
    fi
fi

echo

# Step 6: Database setup
print_info "Setting up database..."
if check_docker; then
    read -p "Do you want to start PostgreSQL using Docker Compose? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        print_info "Starting PostgreSQL with Docker Compose..."
        if docker-compose up -d postgres; then
            print_status "PostgreSQL started successfully"
            
            # Wait for PostgreSQL to be ready
            print_info "Waiting for PostgreSQL to be ready..."
            for i in {1..30}; do
                if docker-compose exec -T postgres pg_isready -U postgres &> /dev/null; then
                    print_status "PostgreSQL is ready"
                    break
                fi
                if [ $i -eq 30 ]; then
                    print_error "PostgreSQL failed to start within 30 seconds"
                    exit 1
                fi
                sleep 1
                echo -n "."
            done
            echo
        else
            print_error "Failed to start PostgreSQL"
            exit 1
        fi
    else
        print_info "Skipping Docker database setup"
    fi
elif check_postgres; then
    print_status "PostgreSQL is already running"
else
    print_warning "No database found. Please ensure PostgreSQL is running before starting the service."
fi

echo

# Step 7: Run database migrations
read -p "Do you want to run database migrations? (Y/n): " -n 1 -r
echo
if [[ ! $REPLY =~ ^[Nn]$ ]]; then
    print_info "Running database migrations..."
    
    # Load database configuration from .env
    if [ -f ".env" ]; then
        export $(grep -v '^#' .env | xargs)
    fi
    
    DB_URL="postgres://${DB_USERNAME:-postgres}:${DB_PASSWORD:-password}@${DB_HOST:-localhost}:${DB_PORT:-5432}/${DB_DATABASE:-transaction_db}?sslmode=${DB_SSL_MODE:-disable}"
    
    if command -v migrate &> /dev/null; then
        if migrate -path migrations -database "$DB_URL" up; then
            print_status "Database migrations completed successfully"
        else
            print_error "Database migrations failed"
            print_warning "Please check your database configuration in .env file"
        fi
    else
        print_error "Migration tool not found. Please install it manually and run:"
        echo "migrate -path migrations -database \"$DB_URL\" up"
    fi
else
    print_info "Skipping database migrations"
fi

echo

# Step 8: Build the application
print_info "Building the application..."
if make build; then
    print_status "Application built successfully"
else
    print_error "Failed to build application"
    exit 1
fi

echo

# Step 9: Run tests (optional)
read -p "Do you want to run tests? (y/N): " -n 1 -r
echo
if [[ $REPLY =~ ^[Yy]$ ]]; then
    print_info "Running tests..."
    if make test; then
        print_status "All tests passed"
    else
        print_warning "Some tests failed, but installation continues"
    fi
fi

echo

echo "=================================================="
print_status "🎉 Installation completed successfully!"
echo "=================================================="
echo

# Step 10: Start the service
print_info "Starting the service..."
read -p "Do you want to start the Go service now? (Y/n): " -n 1 -r
echo
if [[ $REPLY =~ ^[Nn]$ ]]; then
    print_info "You can start the service later with: make run"
    echo
    print_info "Or run directly: go run cmd/server/main.go"
    print_info "Default URL: http://localhost:8080"
    print_info "Health check: http://localhost:8080/health"
else
    print_status "Starting Go Transaction Service..."
    print_info "Service will be available at: http://localhost:8080"
    print_info "Health check: http://localhost:8080/health"
    print_info "API Documentation: http://localhost:8080/api/v1"
    print_info "Press Ctrl+C to stop the service"
    echo
    
    # Open browser (optional)
    read -p "Do you want to open the health check in browser? (Y/n): " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Nn]$ ]]; then
        # Try to open browser
        if command -v open &> /dev/null; then
            # macOS
            open http://localhost:8080/health &
        elif command -v xdg-open &> /dev/null; then
            # Linux
            xdg-open http://localhost:8080/health &
        else
            print_info "Please open http://localhost:8080/health in your browser"
        fi
    fi
    
    # Start service
    make run
fi

echo
print_status "Thank you for using the Go Transaction Service installer! 🚀"

echo
print_info "Quick commands:"
echo "  make run          - Run the service"
echo "  make test         - Run tests"
echo "  make build        - Build the application"
echo "  make docker-build - Build Docker image"
echo "  make help         - Show all available commands"
