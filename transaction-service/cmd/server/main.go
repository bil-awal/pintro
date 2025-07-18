package main

import (
	"context"
	"fmt"
	"log"
	"net/http"
	"os"
	"os/signal"
	"syscall"
	"time"

	"github.com/go-playground/validator/v10"
	"go-transaction-service/internal/config"
	"go-transaction-service/internal/delivery/http/handlers"
	httpdelivery "go-transaction-service/internal/delivery/http"
	"go-transaction-service/internal/delivery/http/middleware"
	"go-transaction-service/internal/infrastructure/database"
	"go-transaction-service/internal/infrastructure/external"
	"go-transaction-service/internal/usecase"
	"go.uber.org/zap"
)

func main() {
	// Load configuration
	cfg, err := config.LoadConfig()
	if err != nil {
		log.Fatal("Failed to load configuration:", err)
	}

	// Initialize logger
	logger, err := initLogger(cfg)
	if err != nil {
		log.Fatal("Failed to initialize logger:", err)
	}
	defer logger.Sync()

	logger.Info("Starting Go Transaction Service", 
		zap.String("version", cfg.App.Version),
		zap.String("environment", cfg.App.Environment))

	// Initialize database
	db, err := database.NewDatabase(cfg, logger)
	if err != nil {
		logger.Fatal("Failed to initialize database", zap.Error(err))
	}
	defer db.Close()

	// Initialize repositories
	userRepo := database.NewPostgresUserRepository(db.DB)
	transactionRepo := database.NewPostgresTransactionRepository(db.DB)

	// Initialize external services
	var paymentGateway usecase.PaymentGateway
	if cfg.App.Environment == "production" {
		paymentGateway = external.NewMidtransPaymentGateway(cfg, logger)
	} else {
		// Use mock payment gateway for development/testing
		paymentGateway = external.NewMockPaymentGateway(logger)
	}

	// Initialize use cases
	authUseCase := usecase.NewAuthUseCase(userRepo, cfg)
	transactionUseCase := usecase.NewTransactionUseCase(transactionRepo, userRepo, paymentGateway)

	// Initialize validator
	validator := validator.New()

	// Initialize handlers
	authHandler := handlers.NewAuthHandler(authUseCase, validator, logger)
	transactionHandler := handlers.NewTransactionHandler(transactionUseCase, validator, logger)

	// Initialize middleware
	authMiddleware := middleware.NewAuthMiddleware(authUseCase, logger)

	// Initialize router
	router := httpdelivery.NewRouter(authHandler, transactionHandler, authMiddleware)
	router.SetupRoutes()

	// Start server
	server := &http.Server{
		Addr:         fmt.Sprintf("%s:%s", cfg.App.Host, cfg.App.Port),
		ReadTimeout:  30 * time.Second,
		WriteTimeout: 30 * time.Second,
		IdleTimeout:  120 * time.Second,
	}

	// Start server in goroutine
	go func() {
		logger.Info("Starting HTTP server", 
			zap.String("address", server.Addr))
		
		if err := router.Start(server.Addr); err != nil && err != http.ErrServerClosed {
			logger.Fatal("Failed to start server", zap.Error(err))
		}
	}()

	// Wait for interrupt signal to gracefully shutdown the server
	quit := make(chan os.Signal, 1)
	signal.Notify(quit, syscall.SIGINT, syscall.SIGTERM)
	<-quit

	logger.Info("Shutting down server...")

	// Graceful shutdown with timeout
	ctx, cancel := context.WithTimeout(context.Background(), 30*time.Second)
	defer cancel()

	if err := router.Shutdown(ctx); err != nil {
		logger.Error("Failed to shutdown server", zap.Error(err))
	}

	logger.Info("Server shutdown complete")
}

func initLogger(cfg *config.Config) (*zap.Logger, error) {
	var logger *zap.Logger
	var err error

	if cfg.App.Environment == "production" {
		// Production logger configuration
		config := zap.NewProductionConfig()
		config.Level = zap.NewAtomicLevelAt(zap.InfoLevel)
		logger, err = config.Build()
	} else {
		// Development logger configuration
		config := zap.NewDevelopmentConfig()
		config.Level = zap.NewAtomicLevelAt(zap.DebugLevel)
		logger, err = config.Build()
	}

	if err != nil {
		return nil, fmt.Errorf("failed to initialize logger: %w", err)
	}

	return logger, nil
}
