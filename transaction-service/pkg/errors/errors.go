package customerrors

import (
	"fmt"
	"net/http"
)

// CustomError represents a custom error with error code and message
type CustomError struct {
	Code    int
	Message string
	Err     error
}

func (e *CustomError) Error() string {
	if e.Err != nil {
		return fmt.Sprintf("%s: %v", e.Message, e.Err)
	}
	return e.Message
}

func (e *CustomError) Unwrap() error {
	return e.Err
}

// Error constructors
func NewValidationError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusBadRequest,
		Message: message,
	}
}

func NewUnauthorizedError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusUnauthorized,
		Message: message,
	}
}

func NewForbiddenError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusForbidden,
		Message: message,
	}
}

func NewNotFoundError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusNotFound,
		Message: message,
	}
}

func NewConflictError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusConflict,
		Message: message,
	}
}

func NewInternalError(message string, err error) *CustomError {
	return &CustomError{
		Code:    http.StatusInternalServerError,
		Message: message,
		Err:     err,
	}
}

func NewBadRequestError(message string) *CustomError {
	return &CustomError{
		Code:    http.StatusBadRequest,
		Message: message,
	}
}

// Error type checkers
func IsValidationError(err error) bool {
	if customErr, ok := err.(*CustomError); ok {
		return customErr.Code == http.StatusBadRequest
	}
	return false
}

func IsUnauthorizedError(err error) bool {
	if customErr, ok := err.(*CustomError); ok {
		return customErr.Code == http.StatusUnauthorized
	}
	return false
}

func IsNotFoundError(err error) bool {
	if customErr, ok := err.(*CustomError); ok {
		return customErr.Code == http.StatusNotFound
	}
	return false
}

func IsInternalError(err error) bool {
	if customErr, ok := err.(*CustomError); ok {
		return customErr.Code == http.StatusInternalServerError
	}
	return false
}

func GetErrorCode(err error) int {
	if customErr, ok := err.(*CustomError); ok {
		return customErr.Code
	}
	return http.StatusInternalServerError
}
