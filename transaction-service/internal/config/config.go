package config

import (
	"os"
	"strconv"
	"time"

	"github.com/joho/godotenv"
)

type Config struct {
	App      AppConfig
	Database DatabaseConfig
	JWT      JWTConfig
	Midtrans MidtransConfig
}

type AppConfig struct {
	Name        string
	Version     string
	Host        string
	Port        string
	Environment string
	Debug       bool
}

type DatabaseConfig struct {
	Host     string
	Port     string
	Username string
	Password string
	Database string
	SSLMode  string
}

type JWTConfig struct {
	SecretKey      string
	ExpireDuration time.Duration
}

type MidtransConfig struct {
	ServerKey   string
	Environment string
	ClientKey   string
}

func LoadConfig() (*Config, error) {
	// Load environment variables from .env file
	godotenv.Load()

	// Parse debug mode
	debug, _ := strconv.ParseBool(getEnv("DEBUG", "false"))

	// Parse JWT expiry duration
	jwtExpiry, _ := strconv.Atoi(getEnv("JWT_EXPIRE_HOURS", "24"))

	config := &Config{
		App: AppConfig{
			Name:        getEnv("APP_NAME", "Go Transaction Service"),
			Version:     getEnv("APP_VERSION", "1.0.0"),
			Host:        getEnv("APP_HOST", "localhost"),
			Port:        getEnv("APP_PORT", "8080"),
			Environment: getEnv("APP_ENV", "development"),
			Debug:       debug,
		},
		Database: DatabaseConfig{
			Host:     getEnv("DB_HOST", "localhost"),
			Port:     getEnv("DB_PORT", "5432"),
			Username: getEnv("DB_USERNAME", "postgres"),
			Password: getEnv("DB_PASSWORD", ""),
			Database: getEnv("DB_DATABASE", "transaction_db"),
			SSLMode:  getEnv("DB_SSL_MODE", "disable"),
		},
		JWT: JWTConfig{
			SecretKey:      getEnv("JWT_SECRET_KEY", "8c100781e252cc0a9c588ea6bcbd60d750b13b42957276415895b028d24427e3"),
			ExpireDuration: time.Duration(jwtExpiry) * time.Hour,
		},
		Midtrans: MidtransConfig{
			ServerKey:   getEnv("MIDTRANS_SERVER_KEY", ""),
			Environment: getEnv("MIDTRANS_ENV", "sandbox"),
			ClientKey:   getEnv("MIDTRANS_CLIENT_KEY", ""),
		},
	}

	return config, nil
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}
