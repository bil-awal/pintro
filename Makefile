.PHONY: help dev prod build start stop restart logs health swagger-gen clean setup-ssl backup

# ==============================================
# Pintro Project Management Makefile
# ==============================================
# Production-ready deployment with Swagger UI

# Default target
help:
	@echo "🚀 Pintro Project Management Commands"
	@echo "======================================"
	@echo ""
	@echo "📋 Available Commands:"
	@echo ""
	@echo "  🔧 Development:"
	@echo "    dev              - Start development environment"
	@echo "    dev-build        - Build and start development environment"
	@echo "    dev-logs         - View development logs"
	@echo ""
	@echo "  🚀 Production:"
	@echo "    prod             - Deploy to production"
	@echo "    prod-build       - Build production images"
	@echo "    prod-start       - Start production services"
	@echo "    prod-stop        - Stop production services"
	@echo "    prod-restart     - Restart production services"
	@echo "    prod-logs        - View production logs"
	@echo ""
	@echo "  📚 Swagger Documentation:"
	@echo "    swagger-gen      - Generate Swagger documentation"
	@echo "    swagger-update   - Update Swagger docs and restart"
	@echo ""
	@echo "  🛠️  Maintenance:"
	@echo "    health           - Check service health"
	@echo "    backup           - Backup database"
	@echo "    setup-ssl        - Setup SSL certificates"
	@echo "    clean            - Clean up containers and images"
	@echo ""
	@echo "  ℹ️  Information:"
	@echo "    status           - Show service status"
	@echo "    urls             - Show service URLs"
	@echo "    help             - Show this help message"
	@echo ""

# ==============================================
# Development Commands
# ==============================================

# Start development environment
dev:
	@echo "🔧 Starting development environment..."
	docker-compose up -d
	@echo "✅ Development environment started!"
	@$(MAKE) urls

# Build and start development
dev-build:
	@echo "🔨 Building and starting development environment..."
	docker-compose up -d --build
	@echo "✅ Development environment built and started!"
	@$(MAKE) urls

# Development logs
dev-logs:
	@echo "📋 Development logs:"
	docker-compose logs -f

# ==============================================
# Production Commands  
# ==============================================

# Deploy to production
prod:
	@echo "🚀 Deploying to production..."
	@if [ -f scripts/deploy-production.sh ]; then \
		chmod +x scripts/deploy-production.sh && \
		./scripts/deploy-production.sh; \
	else \
		echo "❌ Production deployment script not found!"; \
		exit 1; \
	fi

# Build production images
prod-build:
	@echo "🔨 Building production images..."
	docker-compose -f docker-compose.prod.yml build --no-cache
	@echo "✅ Production images built successfully!"

# Start production services
prod-start:
	@echo "🚀 Starting production services..."
	docker-compose -f docker-compose.prod.yml up -d
	@echo "✅ Production services started!"
	@$(MAKE) urls

# Stop production services
prod-stop:
	@echo "🛑 Stopping production services..."
	docker-compose -f docker-compose.prod.yml down
	@echo "✅ Production services stopped!"

# Restart production services
prod-restart:
	@echo "🔄 Restarting production services..."
	docker-compose -f docker-compose.prod.yml restart
	@echo "✅ Production services restarted!"

# Production logs
prod-logs:
	@echo "📋 Production logs:"
	docker-compose -f docker-compose.prod.yml logs -f

# ==============================================
# Swagger Documentation
# ==============================================

# Generate Swagger documentation
swagger-gen:
	@echo "📚 Generating Swagger documentation..."
	@if [ -d transaction-service ]; then \
		cd transaction-service && \
		if [ -f scripts/generate-swagger.sh ]; then \
			chmod +x scripts/generate-swagger.sh && \
			./scripts/generate-swagger.sh; \
		else \
			echo "📖 Generating docs manually..."; \
			if command -v swag >/dev/null 2>&1; then \
				swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal; \
			else \
				echo "⚠️  Installing Swagger CLI..."; \
				go install github.com/swaggo/swag/cmd/swag@latest && \
				swag init -g cmd/server/main.go -o docs --parseDependency --parseInternal; \
			fi; \
		fi; \
	else \
		echo "❌ transaction-service directory not found!"; \
		exit 1; \
	fi
	@echo "✅ Swagger documentation generated!"

# Update Swagger and restart services
swagger-update: swagger-gen
	@echo "🔄 Updating Swagger documentation and restarting services..."
	@if docker-compose ps | grep -q "go-transaction-service"; then \
		docker-compose restart go-transaction-service; \
		echo "✅ Development service restarted with updated docs!"; \
	fi
	@if docker-compose -f docker-compose.prod.yml ps | grep -q "go-transaction-service"; then \
		docker-compose -f docker-compose.prod.yml restart go-transaction-service; \
		echo "✅ Production service restarted with updated docs!"; \
	fi

# ==============================================
# Maintenance Commands
# ==============================================

# Check service health
health:
	@echo "🏥 Checking service health..."
	@echo ""
	@echo "📊 Service Status:"
	@if docker-compose ps >/dev/null 2>&1; then \
		echo "🔧 Development Environment:"; \
		docker-compose ps; \
		echo ""; \
	fi
	@if docker-compose -f docker-compose.prod.yml ps >/dev/null 2>&1; then \
		echo "🚀 Production Environment:"; \
		docker-compose -f docker-compose.prod.yml ps; \
		echo ""; \
	fi
	@echo "🌐 Health Check Endpoints:"
	@if curl -f -s http://localhost/health >/dev/null 2>&1; then \
		echo "✅ API Health: http://localhost/health - OK"; \
	else \
		echo "❌ API Health: http://localhost/health - Failed"; \
	fi
	@if curl -f -s http://localhost:8080/health >/dev/null 2>&1; then \
		echo "✅ Direct API: http://localhost:8080/health - OK"; \
	else \
		echo "❌ Direct API: http://localhost:8080/health - Failed"; \
	fi
	@if curl -f -s http://localhost/swagger/index.html >/dev/null 2>&1; then \
		echo "✅ Swagger UI: http://localhost/swagger/index.html - OK"; \
	else \
		echo "❌ Swagger UI: http://localhost/swagger/index.html - Failed"; \
	fi

# Backup database
backup:
	@echo "💾 Creating database backup..."
	@mkdir -p backups
	@if docker-compose ps postgres | grep -q "Up"; then \
		docker-compose exec postgres pg_dump -U postgres transaction_db > backups/db_$$(date +%Y%m%d_%H%M%S).sql; \
		echo "✅ Development database backed up to backups/db_$$(date +%Y%m%d_%H%M%S).sql"; \
	elif docker-compose -f docker-compose.prod.yml ps postgres | grep -q "Up"; then \
		docker-compose -f docker-compose.prod.yml exec postgres pg_dump -U postgres transaction_db > backups/db_$$(date +%Y%m%d_%H%M%S).sql; \
		echo "✅ Production database backed up to backups/db_$$(date +%Y%m%d_%H%M%S).sql"; \
	else \
		echo "❌ No PostgreSQL service running!"; \
		exit 1; \
	fi

# Setup SSL certificates
setup-ssl:
	@echo "🔒 Setting up SSL certificates..."
	@mkdir -p ssl
	@if [ ! -f ssl/cert.pem ] || [ ! -f ssl/key.pem ]; then \
		echo "🔑 Generating self-signed certificate..."; \
		openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
			-keyout ssl/key.pem \
			-out ssl/cert.pem \
			-subj "/C=US/ST=State/L=City/O=Organization/CN=localhost"; \
		echo "✅ Self-signed certificate generated!"; \
		echo "⚠️  For production, use proper SSL certificates (Let's Encrypt recommended)"; \
	else \
		echo "✅ SSL certificates already exist!"; \
	fi

# Clean up containers and images
clean:
	@echo "🧹 Cleaning up..."
	@echo "Stopping all containers..."
	-docker-compose down 2>/dev/null
	-docker-compose -f docker-compose.prod.yml down 2>/dev/null
	@echo "Removing unused containers, networks, and images..."
	docker system prune -f
	@echo "✅ Cleanup completed!"

# ==============================================
# Information Commands
# ==============================================

# Show service status
status:
	@echo "📊 Service Status"
	@echo "=================="
	@echo ""
	@if docker-compose ps >/dev/null 2>&1; then \
		echo "🔧 Development Environment:"; \
		docker-compose ps; \
		echo ""; \
	fi
	@if docker-compose -f docker-compose.prod.yml ps >/dev/null 2>&1; then \
		echo "🚀 Production Environment:"; \
		docker-compose -f docker-compose.prod.yml ps; \
		echo ""; \
	fi

# Show service URLs
urls:
	@echo ""
	@echo "🌐 Service URLs"
	@echo "==============="
	@echo ""
	@echo "📱 Main Services:"
	@echo "  🌐 Web Application:     http://localhost"
	@echo "  🔧 API Service:         http://localhost/api/v1"
	@echo "  📚 Swagger UI:          http://localhost/swagger/index.html"
	@echo "  🏥 Health Check:        http://localhost/health"
	@echo ""
	@echo "🛠️  Development Tools:"
	@echo "  🗄️  Database Admin:      http://localhost:8081"
	@echo "  ⚡ Redis Commander:     http://localhost:8082"
	@echo "  📊 Grafana:             http://localhost:3000"
	@echo "  📈 Prometheus:          http://localhost:9090"
	@echo ""
	@echo "🔗 Direct Access:"
	@echo "  🔧 Go Service Direct:   http://localhost:8080"
	@echo "  🌐 Laravel Direct:      http://localhost:8000"
	@echo ""

# ==============================================
# Advanced Commands
# ==============================================

# Quick setup for first time
setup: setup-ssl swagger-gen
	@echo "🎯 Initial setup completed!"
	@echo ""
	@echo "Next steps:"
	@echo "1. Update .env.production with your values"
	@echo "2. Run 'make dev' for development or 'make prod' for production"
	@echo "3. Access Swagger UI at http://localhost/swagger/index.html"

# Development with logs
dev-watch: dev
	@$(MAKE) dev-logs

# Production deployment with monitoring
prod-deploy: prod
	@echo "🔍 Deployment completed! Monitoring services..."
	@sleep 10
	@$(MAKE) health

# Update everything (git pull + rebuild + restart)
update:
	@echo "🔄 Updating project..."
	git pull origin main
	@$(MAKE) swagger-gen
	@if docker-compose ps | grep -q "Up"; then \
		echo "🔧 Updating development environment..."; \
		docker-compose build --no-cache; \
		docker-compose up -d; \
	fi
	@if docker-compose -f docker-compose.prod.yml ps | grep -q "Up"; then \
		echo "🚀 Updating production environment..."; \
		docker-compose -f docker-compose.prod.yml build --no-cache; \
		docker-compose -f docker-compose.prod.yml up -d; \
	fi
	@echo "✅ Update completed!"

# Emergency stop all services
emergency-stop:
	@echo "🚨 Emergency stop - stopping all services..."
	-docker stop $$(docker ps -q) 2>/dev/null
	@echo "✅ All services stopped!"

# Complete reset (DANGER: removes all data!)
reset-all:
	@echo "⚠️  WARNING: This will remove ALL containers, volumes, and data!"
	@read -p "Are you sure? Type 'yes' to continue: " confirm; \
	if [ "$$confirm" = "yes" ]; then \
		echo "🗑️  Removing all containers and volumes..."; \
		docker-compose down -v; \
		docker-compose -f docker-compose.prod.yml down -v; \
		docker system prune -af --volumes; \
		echo "✅ Complete reset completed!"; \
	else \
		echo "❌ Reset cancelled."; \
	fi
