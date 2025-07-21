#!/bin/bash

# Auto-generate Swagger documentation script
# This script generates Swagger docs and updates the documentation

set -e

echo "🔄 Generating Swagger Documentation..."

# Check if swag command exists
if ! command -v swag &> /dev/null; then
    echo "⚠️  Swagger CLI not found. Installing..."
    go install github.com/swaggo/swag/cmd/swag@latest
    echo "✅ Swagger CLI installed successfully"
fi

# Generate Swagger documentation
echo "📚 Generating API documentation..."
swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal

# Format Swagger documentation
echo "🎨 Formatting Swagger annotations..."
swag fmt -g cmd/server/main.go

echo "✅ Swagger documentation generated successfully!"
echo ""
echo "📖 Documentation available at:"
echo "   - Swagger UI: http://localhost:8080/swagger/index.html"
echo "   - OpenAPI JSON: http://localhost:8080/swagger/doc.json"
echo "   - Redoc: http://localhost:8080/redoc"
echo ""
echo "💡 To regenerate documentation, run: make swagger-gen"
