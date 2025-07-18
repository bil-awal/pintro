# PintroPay - Transaction Management System

A complete financial transaction management system built with **Laravel Filament** (frontend) and **Go Echo** (backend service) with Midtrans payment gateway integration.

## Project Structure

```
Pintro/
├── transaction-service/     # Go Echo backend service
└── transaction-web/         # Laravel Filament frontend
```

## Features

### User Interface (Laravel Filament)
- ✅ **User Authentication** - Login/Register via Go service JWT
- ✅ **Dashboard** - Balance overview, transaction stats, quick actions
- ✅ **Top-up Balance** - Multiple payment methods via Midtrans
- ✅ **Make Payments** - Send money to merchants or users
- ✅ **Transaction History** - View, filter, export transactions
- ✅ **Admin Panel** - Manage users and transactions (separate panel)

### Backend Service (Go Echo)
- ✅ **JWT Authentication** - Secure user authentication
- ✅ **Transaction Processing** - Handle payments and transfers
- ✅ **Midtrans Integration** - Payment gateway integration
- ✅ **Balance Management** - Real-time balance updates
- ✅ **API Endpoints** - RESTful API for all operations

## Technology Stack

### Frontend (Laravel)
- **Laravel 12** - PHP Framework
- **Filament v3.3** - Admin Panel Framework
- **Livewire 3.5** - Dynamic UI Components
- **TailwindCSS** - Utility-first CSS
- **Alpine.js** - Lightweight JavaScript

### Backend (Go)
- **Go 1.19+** - Programming Language
- **Echo Framework** - Web Framework
- **PostgreSQL** - Database
- **JWT** - Authentication
- **Midtrans** - Payment Gateway

## Installation

### Prerequisites
- **Go 1.19+**
- **PHP 8.2+**
- **Composer**
- **Node.js & npm/pnpm**
- **PostgreSQL**

### 1. Backend Setup (Go Service)

```bash
# Navigate to Go service directory
cd transaction-service

# Install dependencies
go mod tidy

# Copy environment file
cp .env.example .env

# Configure database and Midtrans settings in .env
# DB_HOST=localhost
# DB_PORT=5432
# DB_NAME=transaction_service
# DB_USER=postgres
# DB_PASSWORD=your_password
# MIDTRANS_SERVER_KEY=your-midtrans-server-key
# JWT_SECRET=your-jwt-secret

# Run database migrations
go run cmd/migrate/main.go

# Start the server
go run cmd/server/main.go
```

The Go service will run on `http://localhost:8080`

### 2. Frontend Setup (Laravel)

```bash
# Navigate to Laravel directory
cd transaction-web

# Install PHP dependencies
composer install

# Install Node.js dependencies
pnpm install

# Copy environment file
cp .env.example .env

# Configure environment variables
# APP_NAME="PintroPay"
# DB_CONNECTION=pgsql
# DB_HOST=127.0.0.1
# DB_PORT=5432
# DB_DATABASE=transaction_web
# DB_USERNAME=postgres
# DB_PASSWORD=your_password
# 
# GO_TRANSACTION_SERVICE_URL=http://localhost:8080
# GO_TRANSACTION_API_KEY="your-secret-api-key"
# 
# MIDTRANS_SERVER_KEY="your-midtrans-server-key"
# MIDTRANS_CLIENT_KEY="your-midtrans-client-key"
# MIDTRANS_ENVIRONMENT=sandbox

# Generate application key
php artisan key:generate

# Run database migrations
php artisan migrate

# Seed the database (optional)
php artisan db:seed

# Build frontend assets
pnpm run build

# Start the Laravel development server
php artisan serve
```

The Laravel application will run on `http://localhost:8000`

## Usage

### User Panel (Customer Interface)
1. Navigate to `http://localhost:8000/user`
2. Register a new account or login
3. Use the dashboard to:
   - View your current balance
   - Top-up your balance using various payment methods
   - Make payments to merchants or other users
   - View transaction history

### Admin Panel (Administrative Interface)
1. Navigate to `http://localhost:8000/admin`
2. Login with admin credentials
3. Manage:
   - Users and accounts
   - Transaction monitoring
   - Payment callbacks
   - System statistics

## API Documentation

The Go service provides a RESTful API with the following endpoints:

### Authentication
- `POST /api/v1/register` - User registration
- `POST /api/v1/login` - User login
- `POST /api/v1/logout` - User logout
- `GET /api/v1/verify-token` - Verify JWT token

### User Operations
- `GET /api/v1/profile` - Get user profile
- `PUT /api/v1/profile` - Update user profile
- `GET /api/v1/balance` - Get user balance

### Transactions
- `POST /api/v1/topup` - Create top-up transaction
- `POST /api/v1/pay` - Create payment transaction
- `GET /api/v1/transactions` - Get transaction history
- `GET /api/v1/transactions/{id}` - Get specific transaction

### Admin Operations
- `GET /api/v1/admin/transactions` - Get all transactions
- `POST /api/v1/admin/transactions/{id}/approve` - Approve transaction
- `POST /api/v1/admin/transactions/{id}/reject` - Reject transaction

## Configuration

### Environment Variables

#### Laravel (.env)
```env
# Application
APP_NAME="PintroPay"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=transaction_web
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Go Service Integration
GO_TRANSACTION_SERVICE_URL=http://localhost:8080
GO_TRANSACTION_API_KEY="your-secret-api-key"
GO_TRANSACTION_TIMEOUT=30

# Midtrans Payment Gateway
MIDTRANS_SERVER_KEY="your-midtrans-server-key"
MIDTRANS_CLIENT_KEY="your-midtrans-client-key"
MIDTRANS_ENVIRONMENT=sandbox
MIDTRANS_IS_PRODUCTION=false
```

#### Go Service (.env)
```env
# Server Configuration
PORT=8080
ENV=development

# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=transaction_service
DB_USER=postgres
DB_PASSWORD=your_password

# JWT Configuration
JWT_SECRET=your-super-secret-jwt-key
JWT_EXPIRE=24h

# Midtrans Configuration
MIDTRANS_SERVER_KEY=your-midtrans-server-key
MIDTRANS_CLIENT_KEY=your-midtrans-client-key
MIDTRANS_ENVIRONMENT=sandbox

# API Configuration
API_KEY=your-secret-api-key
```

## Testing

### Backend Testing (Go)
```bash
cd transaction-service
go test ./...
```

### Frontend Testing (Laravel)
```bash
cd transaction-web
php artisan test
```

## Deployment

### Production Considerations

1. **Security**
   - Use strong JWT secrets
   - Enable HTTPS in production
   - Configure proper CORS settings
   - Use production Midtrans keys

2. **Database**
   - Use PostgreSQL with proper backup strategy
   - Configure connection pooling
   - Set up read replicas for scaling

3. **Monitoring**
   - Implement logging (structured logging recommended)
   - Set up application monitoring
   - Configure alerts for transaction failures

### Docker Deployment

Both services include Dockerfile for containerized deployment:

```bash
# Build and run Go service
cd transaction-service
docker build -t pintro-go-service .
docker run -p 8080:8080 pintro-go-service

# Build and run Laravel application
cd transaction-web
docker build -t pintro-web-app .
docker run -p 8000:8000 pintro-web-app
```

## Architecture

### System Architecture

```
┌─────────────────┐    HTTP/JSON    ┌─────────────────┐
│  Laravel Web    │◄───────────────►│   Go Service    │
│  (Frontend)     │                 │   (Backend)     │
└─────────────────┘                 └─────────────────┘
         │                                   │
         │                                   │
         ▼                                   ▼
┌─────────────────┐                 ┌─────────────────┐
│   PostgreSQL    │                 │   PostgreSQL    │
│   (Laravel)     │                 │   (Go Service)  │
└─────────────────┘                 └─────────────────┘
                                             │
                                             │
                                             ▼
                                    ┌─────────────────┐
                                    │    Midtrans     │
                                    │ Payment Gateway │
                                    └─────────────────┘
```

### Authentication Flow

1. User submits login credentials via Laravel Filament
2. Laravel sends credentials to Go service
3. Go service validates and returns JWT token
4. Laravel stores token in session
5. Subsequent API calls include JWT token for authentication

### Transaction Flow

1. User initiates transaction via Filament UI
2. Laravel validates input and sends to Go service
3. Go service processes transaction logic
4. For payments, Go service integrates with Midtrans
5. Transaction status updates are synced back to Laravel
6. User receives real-time updates via Livewire

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Support

For technical support or questions:
- Email: recruitment@pintro.dev
- Create an issue in the repository

---

**Note**: This is a take-home test project demonstrating backend development skills with Go and Laravel integration.
