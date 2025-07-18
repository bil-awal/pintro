# Pintro Transaction Service

A comprehensive, production-ready financial transaction system built with Go using Echo framework, implementing clean architecture principles with PostgreSQL and Midtrans payment gateway integration.

[![Go Version](https://img.shields.io/badge/Go-1.19+-00ADD8?style=flat&logo=go)](https://golang.org/)
[![Echo Framework](https://img.shields.io/badge/Echo-v4.11+-00ADD8?style=flat&logo=go)](https://echo.labstack.com/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15+-336791?style=flat&logo=postgresql)](https://postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Ready-2496ED?style=flat&logo=docker)](https://docker.com/)

## 🚀 Features

### Core Functionality
- **User Management**: Secure registration, login, and JWT authentication
- **Balance Management**: Real-time balance tracking and fund management
- **Transaction Processing**: Top-up balance and peer-to-peer payments
- **Payment Gateway Integration**: Midtrans integration for secure payments
- **Transaction History**: Complete transaction history with pagination and filtering
- **Webhook Support**: Real-time payment gateway callback handling

### Security & Quality
- **JWT Authentication**: Secure token-based authentication with configurable expiration
- **Input Validation**: Comprehensive request validation using go-playground/validator
- **Password Security**: bcrypt hashing with salt
- **SQL Injection Protection**: Parameterized queries and proper escaping
- **CORS Configuration**: Cross-origin request handling
- **Rate Limiting Ready**: Prepared for production rate limiting

### Architecture & Development
- **Clean Architecture**: Modular, testable, and maintainable code structure
- **Database Migrations**: Automated schema management with rollback support
- **Comprehensive Testing**: Unit tests for critical business logic with mocking
- **Docker Support**: Full containerization with Docker Compose
- **Structured Logging**: Zap logger with contextual logging
- **Health Checks**: Application and database health monitoring
- **API Documentation**: RESTful API with comprehensive endpoint documentation

## 🏗️ Architecture

This project follows **Clean Architecture** principles with clear separation of concerns:

```
go-transaction-service/
├── cmd/server/              # 🚀 Application entry point
├── internal/
│   ├── config/              # ⚙️  Configuration management
│   ├── domain/              # 🏢 Business entities and interfaces
│   │   ├── entities/        # 📋 Domain models (User, Transaction)
│   │   └── repositories/    # 🗄️  Repository interfaces
│   ├── usecase/             # 💼 Business logic layer
│   ├── delivery/            # 🌐 Presentation layer
│   │   └── http/            # 🔗 HTTP handlers and middleware
│   │       ├── handlers/    # 🎯 Request handlers
│   │       ├── middleware/  # 🛡️  Authentication, CORS, logging
│   │       └── router.go    # 🗺️  Route definitions
│   └── infrastructure/      # 🔧 External dependencies
│       ├── database/        # 🗃️  Database implementations
│       └── external/        # 🌍 External service integrations
├── pkg/                     # 📦 Shared packages
│   ├── errors/              # ❌ Custom error types
│   └── utils/               # 🛠️  Utility functions
├── migrations/              # 📊 Database migrations
├── tests/                   # 🧪 Unit tests
├── bin/                     # 📱 Compiled binaries
└── docs/                    # 📚 Documentation
```

### Architecture Layers

1. **Domain Layer** (`internal/domain/`): Core business entities and repository interfaces
2. **Use Case Layer** (`internal/usecase/`): Business logic and application services
3. **Delivery Layer** (`internal/delivery/`): HTTP handlers, middleware, and routing
4. **Infrastructure Layer** (`internal/infrastructure/`): Database, external services, and technical implementations

## 🛠️ Technology Stack

| Category | Technology | Version | Purpose |
|----------|------------|---------|---------|
| **Language** | Go | 1.19+ | Core application language |
| **Framework** | Echo | v4.11+ | HTTP web framework |
| **Database** | PostgreSQL | 15+ | Primary data storage |
| **Authentication** | JWT | v5.2+ | Token-based authentication |
| **Payment** | Midtrans | v1.3+ | Payment gateway integration |
| **Validation** | go-playground/validator | v10.16+ | Request validation |
| **Testing** | Testify + Gomock | Latest | Unit testing framework |
| **Logging** | Zap | v1.26+ | Structured logging |
| **Migration** | golang-migrate | Latest | Database schema management |
| **Containerization** | Docker + Compose | Latest | Application containerization |

## 🚦 Getting Started

### System Requirements

| Requirement | Minimum Version | Recommended |
|-------------|----------------|-------------|
| **Go** | 1.19 | 1.21+ |
| **PostgreSQL** | 13 | 15+ |
| **Docker** | 20.0 | Latest |
| **Docker Compose** | 2.0 | Latest |
| **RAM** | 2GB | 4GB+ |
| **Disk Space** | 1GB | 2GB+ |

### Quick Installation

#### 🖥️ Windows (Recommended)

1. **Clone the repository**
   ```cmd
   git clone https://github.com/bil-awal/pintro
   cd pintro/transaction-service
   ```

2. **Run the automated installer**
   ```cmd
   install.bat
   ```

The installer will automatically:
- ✅ Verify system requirements
- ✅ Download Go dependencies
- ✅ Set up environment configuration
- ✅ Start PostgreSQL with Docker
- ✅ Run database migrations
- ✅ Build the application
- ✅ Run tests
- ✅ Start the service

#### 🐧 Linux/macOS

1. **Clone and setup**
   ```bash
   git clone https://github.com/bil-awal/pintro
   cd pintro/transaction-service
   chmod +x install.sh
   ./install.sh
   ```

2. **Alternative: Manual setup**
   ```bash
   # Copy environment file
   cp .env.example .env
   
   # Install dependencies
   go mod download
   
   # Start database
   docker-compose up -d postgres
   
   # Run migrations
   make migrate-up
   
   # Build and run
   make build
   make run
   ```

### Environment Configuration

Update `.env` file with your specific configuration:

```env
# Application Configuration
APP_NAME=Go Transaction Service
APP_VERSION=1.0.0
APP_HOST=0.0.0.0
APP_PORT=8080
APP_ENV=development
DEBUG=true

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_USERNAME=postgres
DB_PASSWORD=your-secure-password
DB_DATABASE=transaction_db
DB_SSL_MODE=disable

# JWT Configuration
JWT_SECRET_KEY=your-super-secret-jwt-key-change-this-in-production-128-chars
JWT_EXPIRE_HOURS=24

# Midtrans Configuration (Get from https://midtrans.com/)
MIDTRANS_SERVER_KEY=your-midtrans-server-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key
MIDTRANS_ENV=sandbox
```

### Running the Application

#### Option 1: Using Docker Compose (Recommended for Production)

```bash
# Start all services (PostgreSQL + Go app)
docker-compose up -d

# Check service status
docker-compose ps

# View logs
docker-compose logs -f go-transaction-service

# Stop services
docker-compose down
```

#### Option 2: Local Development

```bash
# Start PostgreSQL only
docker-compose up -d postgres

# Run the application with live reload
air

# Or run directly
go run cmd/server/main.go
```

#### Option 3: Using Makefile Commands

```bash
# Complete development setup
make setup-dev

# Start with Docker
make docker-compose-up

# Run locally
make run

# Run with live reload (if air is installed)
air
```

## 📚 API Documentation

### Base URL & Health Check

```
Base URL: http://localhost:8080/api/v1
Health Check: http://localhost:8080/health
```

### Authentication Headers

Include JWT token in requests:
```http
Authorization: Bearer <your-jwt-token>
```

### Core Endpoints

#### Authentication

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/api/v1/auth/register` | Register new user | ❌ |
| `POST` | `/api/v1/auth/login` | User login | ❌ |
| `POST` | `/api/v1/auth/refresh` | Refresh JWT token | ✅ |

#### User Management

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `GET` | `/api/v1/user/profile` | Get user profile | ✅ |
| `PUT` | `/api/v1/user/profile` | Update user profile | ✅ |
| `GET` | `/api/v1/user/balance` | Get current balance | ✅ |

#### Transactions

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/api/v1/transactions/topup` | Top-up balance | ✅ |
| `POST` | `/api/v1/transactions/pay` | Make payment | ✅ |
| `GET` | `/api/v1/transactions` | Get transaction history | ✅ |
| `GET` | `/api/v1/transactions/{id}` | Get specific transaction | ✅ |

#### Webhooks

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| `POST` | `/api/v1/webhook/payment/callback` | Payment gateway callback | ❌ |

### Request/Response Examples

#### User Registration
```http
POST /api/v1/auth/register
Content-Type: application/json

{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!",
  "first_name": "John",
  "last_name": "Doe",
  "phone": "+1234567890"
}
```

**Response:**
```json
{
  "success": true,
  "message": "User registered successfully",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "email": "john.doe@example.com",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890",
    "balance": "0.00",
    "status": "active",
    "created_at": "2025-07-18T10:00:00Z",
    "updated_at": "2025-07-18T10:00:00Z"
  }
}
```

#### User Login
```http
POST /api/v1/auth/login
Content-Type: application/json

{
  "email": "john.doe@example.com",
  "password": "SecurePassword123!"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "user": {
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "email": "john.doe@example.com",
      "first_name": "John",
      "last_name": "Doe",
      "phone": "+1234567890",
      "balance": "100.00",
      "status": "active"
    },
    "expires_at": "2025-07-19T10:00:00Z"
  }
}
```

#### Balance Top-up
```http
POST /api/v1/transactions/topup
Authorization: Bearer <token>
Content-Type: application/json

{
  "amount": "100.00",
  "payment_method": "credit_card"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Top-up transaction created successfully",
  "data": {
    "id": "transaction-uuid",
    "status": "processing",
    "amount": "100.00",
    "payment_url": "https://app.sandbox.midtrans.com/snap/v2/vtweb/12345",
    "reference": "TXN-20250718-001",
    "created_at": "2025-07-18T10:00:00Z"
  }
}
```

#### Make Payment
```http
POST /api/v1/transactions/pay
Authorization: Bearer <token>
Content-Type: application/json

{
  "amount": "50.00",
  "description": "Payment for lunch",
  "to_user_id": "recipient-user-uuid"
}
```

**Response:**
```json
{
  "success": true,
  "message": "Payment processed successfully",
  "data": {
    "id": "payment-transaction-uuid",
    "status": "completed",
    "amount": "50.00",
    "reference": "PAY-20250718-001",
    "to_user": {
      "id": "recipient-user-uuid",
      "name": "Jane Smith"
    },
    "created_at": "2025-07-18T10:00:00Z"
  }
}
```

### Error Responses

All endpoints return consistent error responses:

```json
{
  "success": false,
  "message": "Validation error",
  "error": {
    "code": 400,
    "message": "Invalid email format",
    "details": {
      "field": "email",
      "value": "invalid-email"
    }
  }
}
```

### HTTP Status Codes

| Status Code | Description | Use Case |
|-------------|-------------|----------|
| `200 OK` | Success | Successful GET, PUT requests |
| `201 Created` | Resource created | Successful POST requests |
| `400 Bad Request` | Invalid request | Validation errors, malformed JSON |
| `401 Unauthorized` | Authentication required | Missing or invalid JWT token |
| `403 Forbidden` | Access denied | Valid token, insufficient permissions |
| `404 Not Found` | Resource not found | Non-existent user, transaction |
| `409 Conflict` | Resource conflict | Duplicate email, insufficient balance |
| `422 Unprocessable Entity` | Validation error | Business logic validation failures |
| `500 Internal Server Error` | Server error | Database errors, external service failures |

## 🧪 Testing

### Running Tests

```bash
# Run all tests
make test

# Run tests with coverage
make test-coverage

# Run specific test
go test -v ./tests/auth_usecase_test.go

# Generate HTML coverage report
go test -coverprofile=coverage.out ./tests/...
go tool cover -html=coverage.out -o coverage.html
```

### Test Structure

The project includes comprehensive testing for:

| Test Type | Location | Coverage |
|-----------|----------|----------|
| **Unit Tests** | `tests/` | Business logic, use cases |
| **Integration Tests** | `tests/integration/` | Database operations |
| **API Tests** | `tests/api/` | HTTP endpoints |
| **Mock Tests** | `internal/mocks/` | External dependencies |

### Generating Mocks

```bash
# Generate all mocks
make generate-mocks

# Install mockgen if not present
go install github.com/golang/mock/mockgen@latest
```

### Test Coverage Goals

- **Overall Coverage**: > 80%
- **Business Logic**: > 95%
- **Handlers**: > 75%
- **Repository Layer**: > 90%

## 🔧 Development

### Development Environment Setup

```bash
# Complete development setup
make setup-dev

# Install development dependencies
make install-dev-deps

# Start with live reload
air

# Or use make commands
make run-dev
```

### Code Quality Tools

```bash
# Format code
make format

# Lint code
make lint

# Run all quality checks
make check

# Security scan
make security-scan
```

### Database Management

```bash
# Install migrate tool
make install-migrate

# Create new migration
make create-migration name=add_user_roles

# Run migrations up
make migrate-up

# Run migrations down  
make migrate-down

# Reset database
make migrate-reset
```

### Docker Development

```bash
# Build Docker image
make docker-build

# Run with Docker
make docker-run

# Development with Docker Compose
docker-compose -f docker-compose.dev.yml up -d
```

### Useful Development Commands

| Command | Description |
|---------|-------------|
| `make help` | Show all available commands |
| `make clean` | Clean build artifacts |
| `make deps` | Download dependencies |
| `make build` | Build application |
| `make run` | Run application |
| `make test` | Run tests |
| `make docker-build` | Build Docker image |
| `make migrate-up` | Run database migrations |

## 🔐 Security Features

### Authentication & Authorization
- **JWT Tokens**: Secure stateless authentication
- **Password Hashing**: bcrypt with configurable cost
- **Token Expiration**: Configurable token lifetime
- **Refresh Tokens**: Token renewal without re-authentication

### Input Security
- **Request Validation**: Comprehensive input validation
- **SQL Injection Prevention**: Parameterized queries
- **XSS Protection**: Input sanitization
- **CORS Configuration**: Cross-origin request handling

### Data Protection
- **Environment Variables**: Sensitive configuration protection
- **Database Security**: Connection encryption and authentication
- **Logging Security**: No sensitive data in logs
- **Error Handling**: Secure error messages

### Production Security Checklist

- [ ] Change default JWT secret
- [ ] Use strong database passwords
- [ ] Enable SSL/TLS for PostgreSQL
- [ ] Configure production CORS settings
- [ ] Set up rate limiting
- [ ] Enable request logging
- [ ] Configure firewall rules
- [ ] Use HTTPS in production

## 📊 Monitoring & Operations

### Health Monitoring

```bash
# Application health
curl http://localhost:8080/health

# Database health
curl http://localhost:8080/health/database

# Detailed health check
curl http://localhost:8080/health/detailed
```

### Logging

The application uses structured logging with different levels:

```go
// Example log output
{
  "level": "info",
  "timestamp": "2025-07-18T10:00:00Z",
  "caller": "handlers/transaction_handler.go:45",
  "message": "Transaction processed successfully",
  "user_id": "550e8400-e29b-41d4-a716-446655440000",
  "transaction_id": "txn-12345",
  "amount": "100.00",
  "duration": "150ms"
}
```

### Performance Monitoring

- **Request/Response Timing**: HTTP middleware logging
- **Database Query Performance**: Query execution time tracking
- **Memory Usage**: Runtime memory statistics
- **Error Rate Monitoring**: Error tracking and alerting

### Production Deployment

#### Environment Variables for Production

```env
# Production Configuration
APP_ENV=production
DEBUG=false
APP_HOST=0.0.0.0
APP_PORT=8080

# Security
JWT_SECRET_KEY=your-256-bit-secret-key-for-production
JWT_EXPIRE_HOURS=1

# Database (use managed service)
DB_HOST=your-postgres-host
DB_PORT=5432
DB_SSL_MODE=require

# Monitoring
LOG_LEVEL=info
LOG_FORMAT=json
```

#### Docker Production Deployment

```bash
# Build production image
docker build -t go-transaction-service:prod -f Dockerfile.prod .

# Run with production config
docker run -d \
  --name go-transaction-service \
  -p 8080:8080 \
  --env-file .env.production \
  go-transaction-service:prod
```

#### Kubernetes Deployment

```yaml
# deployment.yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: go-transaction-service
spec:
  replicas: 3
  selector:
    matchLabels:
      app: go-transaction-service
  template:
    metadata:
      labels:
        app: go-transaction-service
    spec:
      containers:
      - name: go-transaction-service
        image: go-transaction-service:prod
        ports:
        - containerPort: 8080
        env:
        - name: APP_ENV
          value: "production"
        # Add other environment variables
```

## 🚀 Advanced Features

### Planned Enhancements

- [ ] **Redis Caching**: Session and data caching
- [ ] **Rate Limiting**: API rate limiting with Redis
- [ ] **Message Queue**: Async transaction processing
- [ ] **Microservices**: Service decomposition
- [ ] **GraphQL API**: Alternative API interface
- [ ] **Real-time Updates**: WebSocket support
- [ ] **Multi-currency**: International currency support
- [ ] **Audit Trail**: Comprehensive audit logging

### API Versioning

The API supports versioning through URL path:

```
/api/v1/...  # Current version
/api/v2/...  # Future version
```

### Internationalization

Prepared for i18n support:

```go
// Example error messages
var ErrorMessages = map[string]map[string]string{
    "en": {
        "invalid_email": "Invalid email format",
        "insufficient_balance": "Insufficient balance",
    },
    "id": {
        "invalid_email": "Format email tidak valid",
        "insufficient_balance": "Saldo tidak mencukupi",
    },
}
```

## 🤝 Contributing

We welcome contributions! Please follow these guidelines:

### Development Process

1. **Fork the repository**
2. **Create a feature branch**
   ```bash
   git checkout -b feature/amazing-feature
   ```
3. **Make your changes**
4. **Add tests for new functionality**
5. **Run quality checks**
   ```bash
   make check
   ```
6. **Commit with conventional commits**
   ```bash
   git commit -m "feat: add amazing feature"
   ```
7. **Push and create Pull Request**

### Contribution Guidelines

- **Code Style**: Follow Go best practices and use `gofmt`
- **Testing**: Maintain >80% test coverage
- **Documentation**: Update README and code comments
- **Commit Messages**: Use conventional commit format
- **Breaking Changes**: Document in PR description

### Commit Message Format

```
<type>[optional scope]: <description>

[optional body]

[optional footer(s)]
```

Types: `feat`, `fix`, `docs`, `style`, `refactor`, `test`, `chore`

Example:
```
feat(auth): add refresh token functionality

Implement JWT refresh token mechanism to allow
users to extend their session without re-login.

Closes #123
```

## 📞 Support & Community

### Getting Help

- **📖 Documentation**: Check this README and `/docs` directory
- **🐛 Bug Reports**: Create an issue on GitHub
- **💡 Feature Requests**: Open a discussion or issue
- **❓ Questions**: Use GitHub Discussions

### Contact Information

- **Email**: bilawalfr@gmail.com
- **Project Repository**: [GitHub Repository](https://github.com/bil-awal/pintro)

### Community Guidelines

- Be respectful and inclusive
- Help others learn and grow
- Share knowledge and experiences
- Provide constructive feedback

## 📄 License

This project is licensed under the MIT License. See the [LICENSE](LICENSE) file for details.

```
MIT License

Copyright (c) 2025 Bil Awal

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
```

## 🙏 Acknowledgments

Special thanks to these amazing projects and communities:

- **[Echo Framework](https://echo.labstack.com/)** - Excellent HTTP framework for Go
- **[Midtrans](https://midtrans.com/)** - Reliable payment gateway for Indonesia
- **[PostgreSQL](https://postgresql.org/)** - Robust and feature-rich database system
- **[Docker](https://docker.com/)** - Containerization platform
- **[Go Community](https://golang.org/community/)** - Amazing ecosystem and support
- **[Clean Architecture](https://blog.cleancoder.com/uncle-bob/2012/08/13/the-clean-architecture.html)** - Robert C. Martin's architectural principles

## 📈 Changelog

### Version 2.0.0 (Latest)
- ✨ Enhanced installer with better error handling
- 🔧 Improved Makefile with more commands
- 📚 Comprehensive README update
- 🐳 Better Docker Compose configuration
- 🧪 Enhanced testing framework
- 🔐 Improved security features

### Version 1.0.0
- 🎉 Initial release
- 🏗️ Clean architecture implementation
- 🔐 JWT authentication
- 💳 Midtrans payment integration
- 🗃️ PostgreSQL database
- 🐳 Docker support

---

<div align="center">

**Happy Coding! 🚀**

Made by Bil Awal

[⬆ Back to Top](#pintro-transaction-service)

</div>