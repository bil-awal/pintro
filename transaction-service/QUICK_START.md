# 🚀 Quick Start Guide

Get the Pintro Transaction Service running with Swagger documentation in just a few steps!

## ⚡ Super Quick Start (3 Steps)

### 1. Fix Dependencies & Build
```bash
# Fix any dependency issues
make fix-deps

# Generate Swagger docs and build
make swagger-gen
make build
```

### 2. Setup Database
```bash
# Start PostgreSQL with Docker
docker-compose up -d postgres

# Run migrations
make migrate-up
```

### 3. Run the Service
```bash
# Start the application
make run

# Or with Docker
make docker-compose-up
```

**🎉 Done!** Access your API:
- **Application**: http://localhost:8080
- **Swagger UI**: http://localhost:8080/swagger/index.html
- **Health Check**: http://localhost:8080/health

## 🐛 If Something Goes Wrong

### Build Issues?
```bash
# Run the fix script
./scripts/fix-dependencies.sh    # Linux/macOS
scripts\fix-dependencies.bat     # Windows
```

### Docker Issues?
```bash
# Clean and rebuild
make clean
make fix-deps
make docker-build
```

### Missing Dependencies?
```bash
# Install everything needed
make install-dev-deps
make fix-deps
```

## 🧪 Quick API Test

### 1. Register a User
```bash
curl -X POST http://localhost:8080/api/v1/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123",
    "first_name": "John",
    "last_name": "Doe",
    "phone": "+1234567890"
  }'
```

### 2. Login
```bash
curl -X POST http://localhost:8080/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "password123"
  }'
```

### 3. Check Balance (use token from login)
```bash
curl -X GET http://localhost:8080/api/v1/user/balance \
  -H "Authorization: Bearer YOUR_JWT_TOKEN_HERE"
```

## 📚 Explore with Swagger

1. Go to http://localhost:8080/swagger/index.html
2. Click **"Authorize"** and enter your JWT token
3. Try out the endpoints directly in your browser!

## 🆘 Need Help?

- **Build Problems**: See [BUILD_FIX.md](BUILD_FIX.md)
- **Full Setup**: See [README.md](README.md)
- **All Changes**: See [SWAGGER_SUMMARY.md](SWAGGER_SUMMARY.md)

---

**🎯 That's it!** You now have a fully functional transaction service with interactive API documentation!
