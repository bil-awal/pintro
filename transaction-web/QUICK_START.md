# Quick Start Guide - PintroPay

This guide will help you get the PintroPay transaction system up and running quickly.

## Prerequisites Check

Before starting, ensure you have:
- ✅ Go 1.19+ (`go version`)
- ✅ PHP 8.2+ (`php --version`)
- ✅ Composer (`composer --version`)
- ✅ Node.js & pnpm (`node --version`, `pnpm --version`)
- ✅ PostgreSQL (`psql --version`)

## Quick Setup (Development)

### 1. Database Setup

```bash
# Create databases
createdb transaction_service
createdb transaction_web

# Or using psql
psql -U postgres
CREATE DATABASE transaction_service;
CREATE DATABASE transaction_web;
\q
```

### 2. Go Service (Backend)

```bash
cd transaction-service

# Install dependencies
go mod tidy

# Setup environment
cp .env.example .env

# Edit .env file with your database credentials
# Minimum required:
# DB_HOST=localhost
# DB_NAME=transaction_service
# DB_USER=postgres
# DB_PASSWORD=your_password
# JWT_SECRET=your-super-secret-key

# Run migrations
go run cmd/migrate/main.go

# Start the service
go run cmd/server/main.go
```

Service should start on: **http://localhost:8080**

### 3. Laravel Web (Frontend)

```bash
cd transaction-web

# Install dependencies
composer install
pnpm install

# Setup environment
cp .env.example .env

# Generate app key
php artisan key:generate

# Configure .env file
# Update database credentials:
# DB_DATABASE=transaction_web
# DB_USERNAME=postgres
# DB_PASSWORD=your_password

# Configure Go service connection:
# GO_TRANSACTION_SERVICE_URL=http://localhost:8080

# Run migrations
php artisan migrate

# Build assets
pnpm run build

# Start Laravel server
php artisan serve
```

Laravel should start on: **http://localhost:8000**

## Access Points

### User Panel (Customer Interface)
- **URL**: http://localhost:8000/user
- **Features**: Dashboard, Top-up, Payments, Transaction History
- **Registration**: Available at /user/register

### Admin Panel (Administrative Interface)  
- **URL**: http://localhost:8000/admin
- **Features**: User management, Transaction monitoring
- **Login**: Use admin credentials (create via seeder)

### API Documentation
- **Base URL**: http://localhost:8080/api/v1
- **Health Check**: http://localhost:8080/health

## Test the System

### 1. Register a User
1. Go to http://localhost:8000/user/register
2. Fill in the registration form
3. You'll be automatically logged in

### 2. Check Balance
- View your current balance on the dashboard (should be 0)

### 3. Simulate Top-up (Without Midtrans)
Since Midtrans requires actual keys, you can test the flow:
1. Click "Top-up Balance" 
2. Select amount and payment method
3. The request will be sent to Go service

### 4. Make a Test Payment
1. Go to "Make Payment"
2. Enter amount, description, and recipient
3. Complete the payment (will deduct from balance)

## Common Issues & Solutions

### Go Service Won't Start
```bash
# Check if port 8080 is available
lsof -i :8080

# Check database connection
psql -U postgres -d transaction_service -c "SELECT 1;"
```

### Laravel Issues
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Check database connection
php artisan tinker
DB::connection()->getPdo();
```

### Migration Errors
```bash
# Go service migrations
cd transaction-service
go run cmd/migrate/main.go --reset

# Laravel migrations
cd transaction-web
php artisan migrate:fresh
```

## Development Workflow

### Making Changes

1. **Backend Changes (Go)**:
   ```bash
   cd transaction-service
   # Make your changes
   go run cmd/server/main.go  # Auto-restart needed
   ```

2. **Frontend Changes (Laravel)**:
   ```bash
   cd transaction-web
   # Make your changes
   pnpm run dev  # For live asset compilation
   # Laravel auto-reloads on file changes
   ```

### Testing

```bash
# Test Go service
cd transaction-service
go test ./...

# Test Laravel
cd transaction-web
php artisan test
```

## Midtrans Integration (Optional)

To enable real payments:

1. Sign up at https://midtrans.com
2. Get your sandbox keys
3. Update `.env` files:

**Go Service (.env)**:
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-your_server_key
MIDTRANS_CLIENT_KEY=SB-Mid-client-your_client_key
```

**Laravel (.env)**:
```env
MIDTRANS_SERVER_KEY=SB-Mid-server-your_server_key
MIDTRANS_CLIENT_KEY=SB-Mid-client-your_client_key
MIDTRANS_ENVIRONMENT=sandbox
```

## Next Steps

Once you have the basic system running:

1. **Explore the API**: Use tools like Postman to test the Go service endpoints
2. **Customize the UI**: Modify Filament components in `app/Filament/User/`
3. **Add Features**: Implement additional transaction types or payment methods
4. **Testing**: Write comprehensive tests for your business logic
5. **Deploy**: Use Docker or traditional deployment methods

## Getting Help

- **Laravel/Filament Issues**: Check Laravel and Filament documentation
- **Go Issues**: Review Echo framework docs
- **Database Issues**: Check PostgreSQL configuration
- **Integration Issues**: Verify API communication between services

Happy coding! 🚀
