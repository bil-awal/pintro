package entities

// APIResponse represents the standard API response format
// @Description Standard API response wrapper
type APIResponse struct {
	Success bool        `json:"success" example:"true"`                    // Indicates if the request was successful
	Message string      `json:"message" example:"Operation successful"`    // Human-readable message
	Data    interface{} `json:"data,omitempty"`                           // Response data (varies by endpoint)
	Error   *ErrorInfo  `json:"error,omitempty"`                          // Error information (only present on failure)
}

// ErrorInfo represents error information in API response
// @Description Error details in API response
type ErrorInfo struct {
	Code    int    `json:"code" example:"400"`                     // HTTP status code
	Message string `json:"message" example:"Validation failed"`   // Error message
	Details string `json:"details,omitempty" example:"Email is required"` // Additional error details
}

// ValidationError represents validation error response
// @Description Field validation error
type ValidationError struct {
	Field   string `json:"field" example:"email"`                          // Field name that failed validation
	Message string `json:"message" example:"Email is required"`            // Validation error message
	Tag     string `json:"tag" example:"required"`                         // Validation tag that failed
	Value   string `json:"value" example:"invalid-email"`                  // Value that was submitted
}

// HealthResponse represents health check response
// @Description Service health check response
type HealthResponse struct {
	Service string `json:"service" example:"go-transaction-service"` // Service name
	Status  string `json:"status" example:"healthy"`                 // Health status
	Version string `json:"version" example:"1.0.0"`                  // Service version
}
