# Pintro Financial Transaction System

A comprehensive backend financial transaction system built with Go and Laravel, featuring secure payment processing, user authentication, and real-time transaction management.

## 🏗️ Architecture Overview

This system implements a **microservices architecture** with clear separation of concerns:

- **Go Transaction Service** (`/transaction-service`) - High-performance transaction processing and payment gateway integration
- **Laravel Web Interface** (`/transaction-web`) - User-friendly administrative interface and API gateway

```
┌─────────────────┐    HTTP/REST    ┌──────────────────┐    Gateway    ┌─────────────┐
│  Laravel Web    │ ◄─────────────► │ Go Transaction   │ ◄───────────► │  Midtrans   │
│  Interface      │                 │ Service          │               │  Payment    │
│                 │                 │                  │               │  Gateway    │
└─────────────────┘                 └──────────────────┘               └─────────────┘
         │                                   │
         ▼                                   ▼
┌─────────────────┐                 ┌──────────────────┐
│ MySQL/PostgreSQL│                 │ MySQL/PostgreSQL │
│ Database        │                 │ Database         │
└─────────────────┘                 └──────────────────┘
```

## 🚀 Features

### Core Functionality
- ✅ **User Authentication** - JWT-based secure authentication
- ✅ **Balance Management** - Real-time balance tracking and updates
- ✅ **Top-up System** - Midtrans payment gateway integration
- ✅ **Transaction Processing** - Secure inter-user transfers
- ✅ **Transaction History** - Comprehensive audit trail
- ✅ **Payment Callbacks** - Webhook handling for payment confirmations

### Technical Features
- 🏛️ **Clean Architecture** - Layered architecture with dependency injection
- 🔒 **Security First** - Input validation, rate limiting, and encryption
- 🚄 **High Performance** - Optimized database queries and connection pooling
- 📊 **Monitoring Ready** - Structured logging and metrics collection
- 🧪 **Comprehensive Testing** - Unit and integration tests
- 🐳 **Containerized** - Docker support for easy deployment

## 📁 Project Structure

```
Pintro/
├── README.md                 # This file
├── transaction-service/      # Go Backend Service
│   ├── cmd/
│   ├── internal/
│   ├── pkg/
│   ├── migrations/
│   ├── tests/
│   ├── docker-compose.yml
│   ├── Dockerfile
│   └── README.md
└── transaction-web/          # Laravel Web Interface
    ├── app/
    ├── database/
    ├── resources/
    ├── routes/
    ├── tests/
    ├── docker-compose.yml
    ├── Dockerfile
    └── README.md
```

## 🛠️ Technology Stack

### Go Transaction Service
- **Framework**: Echo (high-performance HTTP router)
- **Authentication**: JWT with RS256 signing
- **Database**: PostgreSQL with GORM
- **Payment**: Midtrans Snap API
- **Testing**: Testify for unit/integration tests
- **Monitoring**: Prometheus metrics + Zap logging

### Laravel Web Interface
- **Framework**: Laravel 10.x
- **Admin Panel**: Filament 3.x
- **Authentication**: Laravel Sanctum + JWT
- **Database**: PostgreSQL/MySQL
- **Frontend**: Blade templates + Alpine.js
- **Testing**: PHPUnit + Feature tests

## 🚀 Quick Start

### Prerequisites
- Go 1.19+
- PHP 8.4+
- Node.js 18+
- Docker & Docker Compose
- PostgreSQL 15+

### 1. Clone Repository
```bash
git clone https://github.com/bil-awal/pintro.git
cd Pintro
```

### 2. Start Go Transaction Service
```bash
cd transaction-service
cp .env.example .env
# Configure your environment variables
go mod download
go run cmd/main.go
```
The Go service will be available at `http://localhost:8080`

### 3. Start Laravel Web Interface
```bash
cd transaction-web
cp .env.example .env
composer install
npm install && npm run build
php artisan key:generate
php artisan migrate --seed
php artisan serve
```
The Laravel app will be available at `http://localhost:8000`

### 4. Using Docker (Recommended)
```bash
# Start both services with Docker Compose
docker-compose up -d
```

## 🔌 API Endpoints

### Authentication
```
POST   /api/v1/auth/register     - User registration
POST   /api/v1/auth/login        - User login
POST   /api/v1/auth/logout       - User logout
POST   /api/v1/auth/refresh      - Refresh JWT token
```

### User Management
```
GET    /api/v1/user/profile      - Get user profile
PUT    /api/v1/user/profile      - Update user profile
GET    /api/v1/user/balance      - Get account balance
```

### Transactions
```
POST   /api/v1/transactions/topup      - Create top-up transaction
POST   /api/v1/transactions/transfer   - Transfer between users
GET    /api/v1/transactions            - Get transaction history
GET    /api/v1/transactions/:id        - Get specific transaction
```

### Payment Gateway
```
POST   /api/v1/payments/callback       - Midtrans webhook callback
GET    /api/v1/payments/status/:id     - Check payment status
```

### Health & Monitoring
```
GET    /health                   - Service health check
GET    /metrics                  - Prometheus metrics
GET    /api/v1/ping             - API connectivity test
```

## 🔒 Security Features

- **JWT Authentication** with secure RS256 signing
- **Rate Limiting** to prevent abuse
- **Input Validation** with comprehensive sanitization
- **SQL Injection Protection** via parameterized queries
- **XSS Protection** with output encoding
- **CSRF Protection** for web forms
- **Secure Headers** (HSTS, CSP, etc.)
- **Payment Data Encryption** for sensitive information

## 🧪 Testing

### Go Service Tests
```bash
cd transaction-service
go test ./... -v
go test ./... -cover
```

### Laravel Tests
```bash
cd transaction-web
php artisan test
php artisan test --coverage
```

## 📊 Monitoring & Logging

### Metrics Available
- Transaction processing metrics
- Payment gateway response times
- Database query performance
- Authentication success/failure rates
- API endpoint response times

### Log Levels
- **INFO**: General application flow
- **WARN**: Potentially harmful situations
- **ERROR**: Error events that might still allow the application to continue
- **FATAL**: Very severe error events that will presumably lead the application to abort

## 🚢 Deployment

### Production Deployment
```bash
# Build production images
docker build -t pintro/transaction-service ./transaction-service
docker build -t pintro/transaction-web ./transaction-web

# Deploy with Docker Compose
docker-compose -f docker-compose.prod.yml up -d
```

### Environment Variables
Ensure the following environment variables are configured:

#### Go Service
```
DB_HOST=localhost
DB_PORT=5432
DB_USER=postgres
DB_PASSWORD=password
DB_NAME=transaction_db
JWT_SECRET=your-jwt-secret
MIDTRANS_SERVER_KEY=your-midtrans-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key
```

#### Laravel Service
```
APP_ENV=production
APP_DEBUG=false
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=transaction_web_db
DB_USERNAME=postgres
DB_PASSWORD=password
GO_SERVICE_URL=http://localhost:8080
```

## 📈 Performance Considerations

- **Database Connection Pooling** for optimal resource usage
- **Prepared Statements** for frequently executed queries
- **Response Caching** for read-heavy operations
- **Asynchronous Processing** for non-critical operations
- **Database Indexing** for fast query execution

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📝 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Email: bilawalfr@gmail.com
- Create an issue in this repository

---

## 📚 Additional Documentation

- [Go Transaction Service Documentation](./transaction-service/README.md)
- [Laravel Web Interface Documentation](./transaction-web/README.md)
- [API Documentation](./docs/api.md)
- [Deployment Guide](./docs/deployment.md)

---

<div align="center">

**Happy Coding! 🚀**

Made by Bil Awal

[⬆ Back to Top](#pintro-financial-transaction-system)

</div>