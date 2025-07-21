.PHONY: build run test clean docker-build docker-run docker-compose-up docker-compose-down migrate-up migrate-down fix-deps swagger-gen swagger-fmt help

# Variables
APP_NAME=go-transaction-service
DOCKER_IMAGE=go-transaction-service:latest
DOCKER_COMPOSE_FILE=docker-compose.yml

# Fix dependencies and resolve build issues
fix-deps:
	@echo "🔧 Fixing dependencies and build issues..."
	@go clean -cache
	@rm -f go.sum
	@go mod download
	@go mod tidy
	@go mod verify
	@echo "✅ Dependencies fixed successfully!"

# Build the application
build: fix-deps
	@echo "Building $(APP_NAME)..."
	@mkdir -p bin
	@go build -o bin/$(APP_NAME) ./cmd/server

# Run the application
run:
	@echo "Running $(APP_NAME)..."
	@go run ./cmd/server

# Run tests
test:
	@echo "Running tests..."
	@go test -v ./tests/...

# Run tests with coverage
test-coverage:
	@echo "Running tests with coverage..."
	@go test -v -cover ./tests/...

# Clean build artifacts
clean:
	@echo "Cleaning build artifacts..."
	@rm -rf bin/
	@go clean

# Generate mocks
generate-mocks:
	@echo "Generating mocks..."
	@go generate ./...

# Format code
format:
	@echo "Formatting code..."
	@go fmt ./...

# Lint code
lint:
	@echo "Linting code..."
	@golangci-lint run

# Tidy dependencies
tidy:
	@echo "Tidying dependencies..."
	@go mod tidy

# Download dependencies
deps:
	@echo "Downloading dependencies..."
	@go mod download

# Build Docker image
docker-build: fix-deps swagger-gen
	@echo "Building Docker image..."
	@docker build -t $(DOCKER_IMAGE) .

# Run Docker container
docker-run:
	@echo "Running Docker container..."
	@docker run -p 8080:8080 --env-file .env $(DOCKER_IMAGE)

# Start services with Docker Compose
docker-compose-up:
	@echo "Starting services with Docker Compose..."
	@docker-compose -f $(DOCKER_COMPOSE_FILE) up -d

# Stop services with Docker Compose
docker-compose-down:
	@echo "Stopping services with Docker Compose..."
	@docker-compose -f $(DOCKER_COMPOSE_FILE) down

# View logs
docker-logs:
	@echo "Viewing logs..."
	@docker-compose -f $(DOCKER_COMPOSE_FILE) logs -f

# Install migrate tool
install-migrate:
	@echo "Installing migrate tool..."
	@go install -tags 'postgres' github.com/golang-migrate/migrate/v4/cmd/migrate@latest

# Run database migrations up
migrate-up:
	@echo "Running database migrations up..."
	@migrate -path migrations -database "postgres://postgres:password@localhost:5432/transaction_db?sslmode=disable" up

# Run database migrations down
migrate-down:
	@echo "Running database migrations down..."
	@migrate -path migrations -database "postgres://postgres:password@localhost:5432/transaction_db?sslmode=disable" down

# Create a new migration
create-migration:
	@echo "Creating new migration..."
	@migrate create -ext sql -dir migrations -seq $(name)

# Setup development environment
setup-dev:
	@echo "Setting up development environment..."
	@cp .env.example .env
	@echo "Please update .env file with your configuration"

# Run all checks (format, lint, test)
check: format lint test

# Install Swagger
install-swagger:
	@echo "Installing Swagger tools..."
	@go install github.com/swaggo/swag/cmd/swag@latest

# Generate Swagger documentation
swagger-gen:
	@echo "Generating Swagger documentation..."
	@swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal

# Format Swagger documentation
swagger-fmt:
	@echo "Formatting Swagger documentation..."
	@swag fmt -g cmd/server/main.go

# Install development dependencies
install-dev-deps:
	@echo "Installing development dependencies..."
	@go install github.com/golangci/golangci-lint/cmd/golangci-lint@latest
	@go install github.com/golang/mock/mockgen@latest
	@go install github.com/swaggo/swag/cmd/swag@latest

# Help
help:
	@echo "Available commands:"
	@echo "  fix-deps           - Fix dependencies and resolve build issues"
	@echo "  build              - Build the application"
	@echo "  run                - Run the application"
	@echo "  test               - Run tests"
	@echo "  test-coverage      - Run tests with coverage"
	@echo "  clean              - Clean build artifacts"
	@echo "  generate-mocks     - Generate mocks"
	@echo "  format             - Format code"
	@echo "  lint               - Lint code"
	@echo "  tidy               - Tidy dependencies"
	@echo "  deps               - Download dependencies"
	@echo "  docker-build       - Build Docker image"
	@echo "  docker-run         - Run Docker container"
	@echo "  docker-compose-up  - Start services with Docker Compose"
	@echo "  docker-compose-down - Stop services with Docker Compose"
	@echo "  docker-logs        - View logs"
	@echo "  install-migrate    - Install migrate tool"
	@echo "  migrate-up         - Run database migrations up"
	@echo "  migrate-down       - Run database migrations down"
	@echo "  create-migration   - Create a new migration"
	@echo "  setup-dev          - Setup development environment"
	@echo "  check              - Run all checks (format, lint, test)"
	@echo "  install-swagger    - Install Swagger tools"
	@echo "  swagger-gen        - Generate Swagger documentation"
	@echo "  swagger-fmt        - Format Swagger documentation"
	@echo "  install-dev-deps   - Install development dependencies"
	@echo "  help               - Show this help message"
