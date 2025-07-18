package utils

import (
	"net/http"
	"strings"

	"github.com/go-playground/validator/v10"
	"github.com/google/uuid"
	"github.com/labstack/echo/v4"
	"go-transaction-service/pkg/errors"
)

// Response represents the standard API response format
type Response struct {
	Success bool        `json:"success"`
	Message string      `json:"message"`
	Data    interface{} `json:"data,omitempty"`
	Error   *ErrorInfo  `json:"error,omitempty"`
}

// ErrorInfo represents error information in API response
type ErrorInfo struct {
	Code    int    `json:"code"`
	Message string `json:"message"`
	Details string `json:"details,omitempty"`
}

// ValidationError represents validation error response
type ValidationError struct {
	Field   string `json:"field"`
	Message string `json:"message"`
	Tag     string `json:"tag"`
	Value   string `json:"value"`
}

// SuccessResponse returns a success response
func SuccessResponse(c echo.Context, code int, message string, data interface{}) error {
	return c.JSON(code, Response{
		Success: true,
		Message: message,
		Data:    data,
	})
}

// ErrorResponse returns an error response
func ErrorResponse(c echo.Context, code int, message string) error {
	return c.JSON(code, Response{
		Success: false,
		Message: message,
		Error: &ErrorInfo{
			Code:    code,
			Message: message,
		},
	})
}

// ValidationErrorResponse returns validation error response
func ValidationErrorResponse(c echo.Context, err error) error {
	var validationErrors []ValidationError
	
	if validationErr, ok := err.(validator.ValidationErrors); ok {
		for _, fieldError := range validationErr {
			validationErrors = append(validationErrors, ValidationError{
				Field:   strings.ToLower(fieldError.Field()),
				Message: getValidationErrorMessage(fieldError),
				Tag:     fieldError.Tag(),
				Value:   fieldError.Value().(string),
			})
		}
	}

	return c.JSON(http.StatusUnprocessableEntity, Response{
		Success: false,
		Message: "Validation failed",
		Error: &ErrorInfo{
			Code:    http.StatusUnprocessableEntity,
			Message: "Validation failed",
		},
		Data: validationErrors,
	})
}

// HandleError handles custom errors and returns appropriate response
func HandleError(c echo.Context, err error) error {
	if customErr, ok := err.(*customerrors.CustomError); ok {
		return c.JSON(customErr.Code, Response{
			Success: false,
			Message: customErr.Message,
			Error: &ErrorInfo{
				Code:    customErr.Code,
				Message: customErr.Message,
			},
		})
	}

	// Default to internal server error
	return c.JSON(http.StatusInternalServerError, Response{
		Success: false,
		Message: "Internal server error",
		Error: &ErrorInfo{
			Code:    http.StatusInternalServerError,
			Message: "Internal server error",
		},
	})
}

// GetUserIDFromContext retrieves user ID from Echo context
func GetUserIDFromContext(c echo.Context) (uuid.UUID, error) {
	userID := c.Get("user_id")
	if userID == nil {
		return uuid.Nil, customerrors.NewUnauthorizedError("User not authenticated")
	}

	if id, ok := userID.(uuid.UUID); ok {
		return id, nil
	}

	return uuid.Nil, customerrors.NewUnauthorizedError("Invalid user ID in context")
}

// GetUserEmailFromContext retrieves user email from Echo context
func GetUserEmailFromContext(c echo.Context) (string, error) {
	userEmail := c.Get("user_email")
	if userEmail == nil {
		return "", customerrors.NewUnauthorizedError("User not authenticated")
	}

	if email, ok := userEmail.(string); ok {
		return email, nil
	}

	return "", customerrors.NewUnauthorizedError("Invalid user email in context")
}

// getValidationErrorMessage returns user-friendly validation error messages
func getValidationErrorMessage(err validator.FieldError) string {
	switch err.Tag() {
	case "required":
		return "This field is required"
	case "email":
		return "Please enter a valid email address"
	case "min":
		return "This field must be at least " + err.Param() + " characters long"
	case "max":
		return "This field must be at most " + err.Param() + " characters long"
	case "gt":
		return "This field must be greater than " + err.Param()
	case "gte":
		return "This field must be greater than or equal to " + err.Param()
	case "lt":
		return "This field must be less than " + err.Param()
	case "lte":
		return "This field must be less than or equal to " + err.Param()
	case "uuid":
		return "Please enter a valid UUID"
	default:
		return "Invalid value for this field"
	}
}
