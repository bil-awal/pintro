# 🔧 Build Fix Instructions

If you're experiencing build issues like missing go.sum entries for Swagger packages, follow these steps:

## Quick Fix (Recommended)

### For Linux/macOS:
```bash
# Run the fix script
chmod +x scripts/fix-dependencies.sh
./scripts/fix-dependencies.sh
```

### For Windows:
```cmd
# Run the fix script
scripts\fix-dependencies.bat
```

### Using Makefile:
```bash
# Fix dependencies and build
make fix-deps
make build

# Or build Docker (automatically fixes dependencies)
make docker-build
```

## Manual Fix Steps

If the automated scripts don't work, follow these manual steps:

### 1. Clean existing dependencies
```bash
go clean -cache
go clean -modcache
rm -f go.sum
```

### 2. Re-download dependencies
```bash
go mod download
go mod tidy
go mod verify
```

### 3. Install Swagger CLI
```bash
go install github.com/swaggo/swag/cmd/swag@latest
```

### 4. Generate Swagger docs
```bash
swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal
```

### 5. Build the application
```bash
go build -o bin/transaction-service ./cmd/server
```

## Common Issues & Solutions

### Issue: Missing go.sum entries for Swagger packages
**Solution**: Run `make fix-deps` or the manual steps above.

### Issue: Swagger CLI not found
**Solution**: Install Swagger CLI with `go install github.com/swaggo/swag/cmd/swag@latest`

### Issue: Docker build fails with dependency errors
**Solution**: 
1. Run `make fix-deps` first
2. Then `make docker-build`

### Issue: Import cycle errors
**Solution**: Ensure proper package structure and avoid circular imports.

### Issue: Permission denied on scripts
**Solution** (Linux/macOS): 
```bash
chmod +x scripts/*.sh
```

## Verification

After fixing dependencies, verify everything works:

```bash
# Test build
make build

# Test run
make run

# Test Docker build
make docker-build

# Access Swagger UI (after running)
# http://localhost:8080/swagger/index.html
```

## Environment Requirements

Ensure you have the required versions:
- Go 1.19 or higher
- Docker (if using containerization)
- Git (for dependency management)

## Need Help?

If issues persist:
1. Check the main [README.md](README.md) for complete setup instructions
2. Ensure all environment requirements are met
3. Try a complete clean and rebuild:
   ```bash
   make clean
   make fix-deps
   make build
   ```

## Success Indicators

You'll know the fix worked when:
- ✅ `go build ./cmd/server` succeeds
- ✅ `make docker-build` completes without errors
- ✅ Swagger UI is accessible at http://localhost:8080/swagger/index.html
- ✅ All API endpoints are documented in Swagger
