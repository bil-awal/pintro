package http

import (
	"context"
	"net/http"

	"github.com/labstack/echo/v4"
	"github.com/labstack/echo/v4/middleware"
	echoSwagger "github.com/swaggo/echo-swagger"
	"go-transaction-service/internal/delivery/http/handlers"
	custommiddleware "go-transaction-service/internal/delivery/http/middleware"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/pkg/utils"
)

// Router handles HTTP routing and middleware setup
type Router struct {
	echo               *echo.Echo
	authHandler        *handlers.AuthHandler
	transactionHandler *handlers.TransactionHandler
	authMiddleware     *custommiddleware.AuthMiddleware
}

// NewRouter creates a new HTTP router instance
func NewRouter(
	authHandler *handlers.AuthHandler,
	transactionHandler *handlers.TransactionHandler,
	authMiddleware *custommiddleware.AuthMiddleware,
) *Router {
	e := echo.New()

	return &Router{
		echo:               e,
		authHandler:        authHandler,
		transactionHandler: transactionHandler,
		authMiddleware:     authMiddleware,
	}
}

// SetupRoutes configures all API routes and middleware
// @title Pintro Transaction Service API
// @version 1.0
// @description RESTful API for transaction service with balance management, payments, and authentication
// @termsOfService http://swagger.io/terms/
// @contact.name API Support
// @contact.email support@pintro.com
// @license.name MIT
// @license.url https://opensource.org/licenses/MIT
// @host localhost:8080
// @BasePath /api/v1
// @securityDefinitions.apikey BearerAuth
// @in header
// @name Authorization
// @description Type "Bearer" followed by a space and JWT token.
func (r *Router) SetupRoutes() {
	// Global middleware
	r.echo.Use(middleware.Logger())
	r.echo.Use(middleware.Recover())
	r.echo.Use(custommiddleware.CORSConfig())
	r.echo.Use(middleware.RequestID())

	// Security headers
	r.echo.Use(middleware.SecureWithConfig(middleware.SecureConfig{
		XSSProtection:         "1; mode=block",
		ContentTypeNosniff:    "nosniff",
		XFrameOptions:         "DENY",
		HSTSMaxAge:            3600,
		ContentSecurityPolicy: "default-src 'self'",
	}))

	// Request logging middleware
	r.echo.Use(middleware.LoggerWithConfig(middleware.LoggerConfig{
		Format: "${time_rfc3339} ${method} ${uri} ${status} ${latency_human} ${remote_ip} ${user_agent}\n",
	}))

	// Rate limiting middleware
	r.echo.Use(middleware.RateLimiter(middleware.NewRateLimiterMemoryStore(20)))

	// Swagger documentation endpoint
	r.echo.GET("/swagger/*", echoSwagger.WrapHandler)

	// Health check endpoint
	r.echo.GET("/health", r.healthCheck)
	r.echo.GET("/", r.rootHandler)

	// API routes
	api := r.echo.Group("/api/v1")

	// Public authentication routes
	r.setupAuthRoutes(api)

	// Protected routes
	protected := api.Group("")
	protected.Use(r.authMiddleware.Authenticate)

	// User management routes
	r.setupUserRoutes(protected)

	// Transaction routes
	r.setupTransactionRoutes(protected)

	// Webhook routes (public - for payment gateway callbacks)
	r.setupWebhookRoutes(api)

	// 404 handler for undefined routes
	r.echo.RouteNotFound("/*", func(c echo.Context) error {
		return utils.ErrorResponse(c, http.StatusNotFound, "Route not found")
	})
}

// setupAuthRoutes configures authentication-related routes
func (r *Router) setupAuthRoutes(api *echo.Group) {
	auth := api.Group("/auth")
	
	auth.POST("/register", r.authHandler.Register)
	auth.POST("/login", r.authHandler.Login)
	
	// Protected auth routes
	authProtected := auth.Group("")
	authProtected.Use(r.authMiddleware.Authenticate)
	authProtected.POST("/refresh", r.authHandler.RefreshToken)
}

// setupUserRoutes configures user-related routes
func (r *Router) setupUserRoutes(protected *echo.Group) {
	user := protected.Group("/user")
	
	user.GET("/profile", r.authHandler.GetProfile)
	user.GET("/balance", r.transactionHandler.GetBalance)
}

// setupTransactionRoutes configures transaction-related routes
func (r *Router) setupTransactionRoutes(protected *echo.Group) {
	transactions := protected.Group("/transactions")
	
	transactions.POST("/topup", r.transactionHandler.Topup)
	transactions.POST("/pay", r.transactionHandler.Pay)
	transactions.POST("/transfer", r.transactionHandler.Transfer)
	transactions.GET("", r.transactionHandler.GetTransactions)
}

// setupWebhookRoutes configures webhook routes for external services
func (r *Router) setupWebhookRoutes(api *echo.Group) {
	webhook := api.Group("/webhook")
	
	webhook.POST("/payment/callback", r.transactionHandler.HandleCallback)
}

// Start starts the HTTP server
func (r *Router) Start(address string) error {
	return r.echo.Start(address)
}

// Shutdown gracefully shuts down the HTTP server
func (r *Router) Shutdown(ctx context.Context) error {
	return r.echo.Shutdown(ctx)
}

// healthCheck handles health check requests
// @Summary Health check
// @Description Check if the service is running and healthy
// @Tags System
// @Accept json
// @Produce json
// @Success 200 {object} entities.APIResponse{data=entities.HealthResponse} "Service is healthy"
// @Router /health [get]
func (r *Router) healthCheck(c echo.Context) error {
	healthData := entities.HealthResponse{
		Service: "go-transaction-service",
		Status:  "healthy",
		Version: "1.0.0",
	}

	return utils.SuccessResponse(c, http.StatusOK, "Service is healthy", healthData)
}

// rootHandler handles root endpoint requests
// @Summary API Information
// @Description Get basic API information and available endpoints
// @Tags System
// @Accept json
// @Produce json
// @Success 200 {object} entities.APIResponse "API information"
// @Router / [get]
func (r *Router) rootHandler(c echo.Context) error {
	apiInfo := map[string]interface{}{
		"service":     "Pintro Transaction Service",
		"version":     "1.0.0",
		"description": "RESTful API for transaction service with balance management, payments, and authentication",
		"endpoints": map[string]string{
			"health":       "/health",
			"swagger":      "/swagger/index.html",
			"api_base":     "/api/v1",
			"auth":         "/api/v1/auth",
			"transactions": "/api/v1/transactions",
			"user":         "/api/v1/user",
			"webhooks":     "/api/v1/webhook",
		},
		"documentation": map[string]string{
			"swagger_ui": "/swagger/index.html",
			"openapi":    "/swagger/doc.json",
		},
	}

	return utils.SuccessResponse(c, http.StatusOK, "Pintro Transaction Service API", apiInfo)
}
