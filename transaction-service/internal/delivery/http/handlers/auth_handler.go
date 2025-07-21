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

// AuthHandler handles authentication-related HTTP requests
type AuthHandler struct {
	authUseCase usecase.AuthUseCase
	validator   *validator.Validate
	logger      *zap.Logger
}

// NewAuthHandler creates a new authentication handler
func NewAuthHandler(authUseCase usecase.AuthUseCase, validator *validator.Validate, logger *zap.Logger) *AuthHandler {
	return &AuthHandler{
		authUseCase: authUseCase,
		validator:   validator,
		logger:      logger,
	}
}

// Register handles user registration
// @Summary Register new user
// @Description Register a new user account with email, password, and personal information
// @Tags Authentication
// @Accept json
// @Produce json
// @Param request body entities.RegisterRequest true "User registration request"
// @Success 201 {object} entities.APIResponse{data=entities.UserProfile} "User registered successfully"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid input format"
// @Failure 422 {object} entities.APIResponse{data=[]entities.ValidationError} "Validation failed"
// @Failure 409 {object} entities.APIResponse{error=entities.ErrorInfo} "Conflict - email already exists"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /auth/register [post]
func (h *AuthHandler) Register(c echo.Context) error {
	var req entities.RegisterRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind registration request", 
			zap.Error(err),
			zap.String("remote_addr", c.RealIP()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Registration validation failed", 
			zap.Error(err),
			zap.String("email", req.Email))
		return utils.ValidationErrorResponse(c, err)
	}

	user, err := h.authUseCase.Register(c.Request().Context(), req)
	if err != nil {
		h.logger.Error("Registration failed", 
			zap.Error(err),
			zap.String("email", req.Email))
		return utils.HandleError(c, err)
	}

	h.logger.Info("User registered successfully", 
		zap.String("user_id", user.ID.String()),
		zap.String("email", user.Email))

	return utils.SuccessResponse(c, http.StatusCreated, "User registered successfully", user.ToProfile())
}

// Login handles user authentication
// @Summary User login
// @Description Authenticate user with email and password, returns JWT token
// @Tags Authentication
// @Accept json
// @Produce json
// @Param request body entities.LoginRequest true "User login credentials"
// @Success 200 {object} entities.APIResponse{data=entities.LoginResponse} "Login successful"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid input format"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid credentials"
// @Failure 422 {object} entities.APIResponse{data=[]entities.ValidationError} "Validation failed"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /auth/login [post]
func (h *AuthHandler) Login(c echo.Context) error {
	var req entities.LoginRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind login request", 
			zap.Error(err),
			zap.String("remote_addr", c.RealIP()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Login validation failed", 
			zap.Error(err),
			zap.String("email", req.Email))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.authUseCase.Login(c.Request().Context(), req)
	if err != nil {
		h.logger.Error("Login failed", 
			zap.Error(err),
			zap.String("email", req.Email),
			zap.String("remote_addr", c.RealIP()))
		return utils.HandleError(c, err)
	}

	h.logger.Info("User logged in successfully", 
		zap.String("user_id", response.User.ID.String()),
		zap.String("email", response.User.Email))

	return utils.SuccessResponse(c, http.StatusOK, "Login successful", response)
}

// GetProfile retrieves authenticated user profile
// @Summary Get user profile
// @Description Get the profile information of the authenticated user
// @Tags User
// @Accept json
// @Produce json
// @Security BearerAuth
// @Success 200 {object} entities.APIResponse{data=entities.UserProfile} "Profile retrieved successfully"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 404 {object} entities.APIResponse{error=entities.ErrorInfo} "User not found"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /user/profile [get]
func (h *AuthHandler) GetProfile(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	user, err := h.authUseCase.GetUserByID(c.Request().Context(), userID)
	if err != nil {
		h.logger.Error("Failed to get user profile", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Debug("User profile retrieved", 
		zap.String("user_id", userID.String()))

	return utils.SuccessResponse(c, http.StatusOK, "Profile retrieved successfully", user.ToProfile())
}

// RefreshToken refreshes the JWT token for authenticated user
// @Summary Refresh JWT token
// @Description Refresh the JWT token for the authenticated user
// @Tags Authentication
// @Accept json
// @Produce json
// @Security BearerAuth
// @Success 200 {object} entities.APIResponse{data=entities.LoginResponse} "Token refreshed successfully"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or expired token"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /auth/refresh [post]
func (h *AuthHandler) RefreshToken(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for token refresh", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	user, err := h.authUseCase.GetUserByID(c.Request().Context(), userID)
	if err != nil {
		h.logger.Error("Failed to get user for token refresh", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.HandleError(c, err)
	}

	// Generate new token
	response, err := h.authUseCase.GenerateTokenResponse(user)
	if err != nil {
		h.logger.Error("Failed to generate refresh token", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Info("Token refreshed successfully", 
		zap.String("user_id", userID.String()))

	return utils.SuccessResponse(c, http.StatusOK, "Token refreshed successfully", response)
}
