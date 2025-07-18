package database

import (
	"database/sql"
	"fmt"
	"time"

	_ "github.com/lib/pq"
	"go-transaction-service/internal/config"
	"go.uber.org/zap"
)

type Database struct {
	DB     *sql.DB
	config *config.Config
	logger *zap.Logger
}

func NewDatabase(config *config.Config, logger *zap.Logger) (*Database, error) {
	dsn := fmt.Sprintf(
		"host=%s port=%s user=%s password=%s dbname=%s sslmode=%s",
		config.Database.Host,
		config.Database.Port,
		config.Database.Username,
		config.Database.Password,
		config.Database.Database,
		config.Database.SSLMode,
	)

	db, err := sql.Open("postgres", dsn)
	if err != nil {
		return nil, fmt.Errorf("failed to open database: %w", err)
	}

	// Configure connection pool
	db.SetMaxOpenConns(25)
	db.SetMaxIdleConns(10)
	db.SetConnMaxLifetime(5 * time.Minute)
	db.SetConnMaxIdleTime(5 * time.Minute)

	// Test connection
	if err := db.Ping(); err != nil {
		return nil, fmt.Errorf("failed to ping database: %w", err)
	}

	logger.Info("Database connection established", 
		zap.String("host", config.Database.Host),
		zap.String("port", config.Database.Port),
		zap.String("database", config.Database.Database))

	return &Database{
		DB:     db,
		config: config,
		logger: logger,
	}, nil
}

func (d *Database) Close() error {
	if d.DB != nil {
		return d.DB.Close()
	}
	return nil
}

func (d *Database) Health() error {
	return d.DB.Ping()
}
