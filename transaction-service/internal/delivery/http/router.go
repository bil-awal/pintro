package http

import (
	"context"
	"net/http"

	"github.com/labstack/echo/v4"
	"github.com/labstack/echo/v4/middleware"
	"go-transaction-service/internal/delivery/http/handlers"
	custommiddleware "go-transaction-service/internal/delivery/http/middleware"
	"go-transaction-service/pkg/utils"
)

type Router struct {
	echo               *echo.Echo
	authHandler        *handlers.AuthHandler
	transactionHandler *handlers.TransactionHandler
	authMiddleware     *custommiddleware.AuthMiddleware
}

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

func (r *Router) SetupRoutes() {
	// Middleware
	r.echo.Use(middleware.Logger())
	r.echo.Use(middleware.Recover())
	r.echo.Use(custommiddleware.CORSConfig())
	r.echo.Use(middleware.RequestID())

	// Custom middleware for request/response logging
	r.echo.Use(middleware.LoggerWithConfig(middleware.LoggerConfig{
		Format: "${time_rfc3339} ${method} ${uri} ${status} ${latency_human}\n",
	}))

	// Health check endpoint
	r.echo.GET("/health", r.healthCheck)

	// API routes
	api := r.echo.Group("/api/v1")

	// Auth routes (public)
	auth := api.Group("/auth")
	auth.POST("/register", r.authHandler.Register)
	auth.POST("/login", r.authHandler.Login)

	// Protected routes
	protected := api.Group("")
	protected.Use(r.authMiddleware.Authenticate)

	// User routes
	user := protected.Group("/user")
	user.GET("/profile", r.authHandler.GetProfile)
	user.GET("/balance", r.transactionHandler.GetBalance)

	// Transaction routes
	transactions := protected.Group("/transactions")
	transactions.POST("/topup", r.transactionHandler.Topup)
	transactions.POST("/pay", r.transactionHandler.Pay)
	transactions.GET("", r.transactionHandler.GetTransactions)

	// Webhook routes (public - for payment gateway callbacks)
	webhook := api.Group("/webhook")
	webhook.POST("/payment/callback", r.transactionHandler.HandleCallback)

	// 404 handler
	r.echo.RouteNotFound("/*", func(c echo.Context) error {
		return utils.ErrorResponse(c, http.StatusNotFound, "Route not found")
	})
}

func (r *Router) Start(address string) error {
	return r.echo.Start(address)
}

func (r *Router) Shutdown(ctx context.Context) error {
	return r.echo.Shutdown(ctx)
}

func (r *Router) healthCheck(c echo.Context) error {
	return utils.SuccessResponse(c, http.StatusOK, "Service is healthy", map[string]interface{}{
		"service": "go-transaction-service",
		"status":  "healthy",
	})
}
