package handlers

import (
	"net/http"

	"github.com/go-playground/validator/v10"
	"github.com/labstack/echo/v4"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/usecase"
	"go-transaction-service/pkg/utils"
	"go.uber.org/zap"
)

type AuthHandler struct {
	authUseCase usecase.AuthUseCase
	validator   *validator.Validate
	logger      *zap.Logger
}

func NewAuthHandler(authUseCase usecase.AuthUseCase, validator *validator.Validate, logger *zap.Logger) *AuthHandler {
	return &AuthHandler{
		authUseCase: authUseCase,
		validator:   validator,
		logger:      logger,
	}
}

func (h *AuthHandler) Register(c echo.Context) error {
	var req entities.RegisterRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind request", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Validation failed", zap.Error(err))
		return utils.ValidationErrorResponse(c, err)
	}

	user, err := h.authUseCase.Register(c.Request().Context(), req)
	if err != nil {
		h.logger.Error("Registration failed", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusCreated, "User registered successfully", user)
}

func (h *AuthHandler) Login(c echo.Context) error {
	var req entities.LoginRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind request", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Validation failed", zap.Error(err))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.authUseCase.Login(c.Request().Context(), req)
	if err != nil {
		h.logger.Error("Login failed", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusOK, "Login successful", response)
}

func (h *AuthHandler) GetProfile(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	user, err := h.authUseCase.GetUserByID(c.Request().Context(), userID)
	if err != nil {
		h.logger.Error("Failed to get user profile", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusOK, "Profile retrieved successfully", user)
}
