package entities

import (
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
	"golang.org/x/crypto/bcrypt"
)

// User represents user entity in the system
// @Description User account information
type User struct {
	ID        uuid.UUID       `json:"id" db:"id" example:"550e8400-e29b-41d4-a716-446655440000"`                      // User unique identifier
	Email     string          `json:"email" db:"email" example:"john.doe@example.com"`                                // User email address
	Password  string          `json:"-" db:"password"`                                                                // User password (not exposed in JSON)
	FirstName string          `json:"first_name" db:"first_name" example:"John"`                                      // User first name
	LastName  string          `json:"last_name" db:"last_name" example:"Doe"`                                         // User last name
	Phone     string          `json:"phone" db:"phone" example:"+1234567890"`                                         // User phone number
	Balance   decimal.Decimal `json:"balance" db:"balance" example:"1000.50" swaggertype:"string"`                   // User wallet balance
	Status    UserStatus      `json:"status" db:"status" example:"active"`                                            // User account status
	CreatedAt time.Time       `json:"created_at" db:"created_at" example:"2024-01-01T00:00:00Z"`                     // Account creation timestamp
	UpdatedAt time.Time       `json:"updated_at" db:"updated_at" example:"2024-01-01T00:00:00Z"`                     // Last update timestamp
}

// UserStatus represents possible user account statuses
// @Description User account status enumeration
type UserStatus string

const (
	UserStatusActive   UserStatus = "active"   // User account is active
	UserStatusInactive UserStatus = "inactive" // User account is inactive
	UserStatusBlocked  UserStatus = "blocked"  // User account is blocked
)

// RegisterRequest represents user registration request payload
// @Description User registration request
type RegisterRequest struct {
	Email     string `json:"email" validate:"required,email" example:"john.doe@example.com"`        // User email address
	Password  string `json:"password" validate:"required,min=8" example:"password123"`              // User password (minimum 8 characters)
	FirstName string `json:"first_name" validate:"required,min=2" example:"John"`                   // User first name (minimum 2 characters)
	LastName  string `json:"last_name" validate:"required,min=2" example:"Doe"`                     // User last name (minimum 2 characters)
	Phone     string `json:"phone" validate:"required,min=10" example:"+1234567890"`                // User phone number (minimum 10 characters)
}

// LoginRequest represents user login request payload
// @Description User login request
type LoginRequest struct {
	Email    string `json:"email" validate:"required,email" example:"john.doe@example.com"` // User email address
	Password string `json:"password" validate:"required" example:"password123"`             // User password
}

// LoginResponse represents successful login response
// @Description Successful login response with JWT token
type LoginResponse struct {
	Token     string    `json:"token" example:"eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9..."`         // JWT authentication token
	User      User      `json:"user"`                                                          // User information
	ExpiresAt time.Time `json:"expires_at" example:"2024-01-02T00:00:00Z"`                    // Token expiration time
}

// JWTClaims represents JWT token claims
// @Description JWT token claims structure
type JWTClaims struct {
	UserID uuid.UUID `json:"user_id" example:"550e8400-e29b-41d4-a716-446655440000"` // User ID from token
	Email  string    `json:"email" example:"john.doe@example.com"`                    // User email from token
	Exp    int64     `json:"exp" example:"1640995200"`                                // Token expiration timestamp
	Iat    int64     `json:"iat" example:"1640908800"`                                // Token issued at timestamp
}

// UserProfile represents user profile response
// @Description User profile information
type UserProfile struct {
	ID        uuid.UUID       `json:"id" example:"550e8400-e29b-41d4-a716-446655440000"`      // User unique identifier
	Email     string          `json:"email" example:"john.doe@example.com"`                   // User email address
	FirstName string          `json:"first_name" example:"John"`                              // User first name
	LastName  string          `json:"last_name" example:"Doe"`                                // User last name
	Phone     string          `json:"phone" example:"+1234567890"`                            // User phone number
	Balance   decimal.Decimal `json:"balance" example:"1000.50" swaggertype:"string"`        // User wallet balance
	Status    UserStatus      `json:"status" example:"active"`                                // User account status
	CreatedAt time.Time       `json:"created_at" example:"2024-01-01T00:00:00Z"`             // Account creation timestamp
	UpdatedAt time.Time       `json:"updated_at" example:"2024-01-01T00:00:00Z"`             // Last update timestamp
}

// HashPassword hashes the user password using bcrypt
func (u *User) HashPassword() error {
	hashedPassword, err := bcrypt.GenerateFromPassword([]byte(u.Password), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	u.Password = string(hashedPassword)
	return nil
}

// CheckPassword verifies if the provided password matches the stored hash
func (u *User) CheckPassword(password string) bool {
	err := bcrypt.CompareHashAndPassword([]byte(u.Password), []byte(password))
	return err == nil
}

// IsActive checks if the user account is active
func (u *User) IsActive() bool {
	return u.Status == UserStatusActive
}

// ToProfile converts User to UserProfile for safe exposure
func (u *User) ToProfile() UserProfile {
	return UserProfile{
		ID:        u.ID,
		Email:     u.Email,
		FirstName: u.FirstName,
		LastName:  u.LastName,
		Phone:     u.Phone,
		Balance:   u.Balance,
		Status:    u.Status,
		CreatedAt: u.CreatedAt,
		UpdatedAt: u.UpdatedAt,
	}
}

// NewUser creates a new user entity from registration request
func NewUser(req RegisterRequest) (*User, error) {
	user := &User{
		ID:        uuid.New(),
		Email:     req.Email,
		Password:  req.Password,
		FirstName: req.FirstName,
		LastName:  req.LastName,
		Phone:     req.Phone,
		Balance:   decimal.Zero,
		Status:    UserStatusActive,
		CreatedAt: time.Now(),
		UpdatedAt: time.Now(),
	}

	if err := user.HashPassword(); err != nil {
		return nil, err
	}

	return user, nil
}
