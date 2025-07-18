package middleware

import (
	"net/http"
	"strings"

	"github.com/labstack/echo/v4"
	"go-transaction-service/internal/usecase"
	"go-transaction-service/pkg/utils"
	"go.uber.org/zap"
)

type AuthMiddleware struct {
	authUseCase usecase.AuthUseCase
	logger      *zap.Logger
}

func NewAuthMiddleware(authUseCase usecase.AuthUseCase, logger *zap.Logger) *AuthMiddleware {
	return &AuthMiddleware{
		authUseCase: authUseCase,
		logger:      logger,
	}
}

func (m *AuthMiddleware) Authenticate(next echo.HandlerFunc) echo.HandlerFunc {
	return func(c echo.Context) error {
		authHeader := c.Request().Header.Get("Authorization")
		if authHeader == "" {
			return utils.ErrorResponse(c, http.StatusUnauthorized, "Missing authorization header")
		}

		// Extract token from "Bearer <token>"
		tokenParts := strings.Split(authHeader, " ")
		if len(tokenParts) != 2 || tokenParts[0] != "Bearer" {
			return utils.ErrorResponse(c, http.StatusUnauthorized, "Invalid authorization header format")
		}

		token := tokenParts[1]

		// Validate token
		claims, err := m.authUseCase.ValidateToken(c.Request().Context(), token)
		if err != nil {
			m.logger.Error("Token validation failed", zap.Error(err))
			return utils.HandleError(c, err)
		}

		// Set user info in context
		c.Set("user_id", claims.UserID)
		c.Set("user_email", claims.Email)
		c.Set("claims", claims)

		return next(c)
	}
}

func (m *AuthMiddleware) OptionalAuthenticate(next echo.HandlerFunc) echo.HandlerFunc {
	return func(c echo.Context) error {
		authHeader := c.Request().Header.Get("Authorization")
		if authHeader != "" {
			// Extract token from "Bearer <token>"
			tokenParts := strings.Split(authHeader, " ")
			if len(tokenParts) == 2 && tokenParts[0] == "Bearer" {
				token := tokenParts[1]

				// Validate token
				claims, err := m.authUseCase.ValidateToken(c.Request().Context(), token)
				if err == nil {
					// Set user info in context if token is valid
					c.Set("user_id", claims.UserID)
					c.Set("user_email", claims.Email)
					c.Set("claims", claims)
				}
			}
		}

		return next(c)
	}
}
