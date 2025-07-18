package entities

import (
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
	"golang.org/x/crypto/bcrypt"
)

type User struct {
	ID        uuid.UUID       `json:"id" db:"id"`
	Email     string          `json:"email" db:"email"`
	Password  string          `json:"-" db:"password"`
	FirstName string          `json:"first_name" db:"first_name"`
	LastName  string          `json:"last_name" db:"last_name"`
	Phone     string          `json:"phone" db:"phone"`
	Balance   decimal.Decimal `json:"balance" db:"balance"`
	Status    UserStatus      `json:"status" db:"status"`
	CreatedAt time.Time       `json:"created_at" db:"created_at"`
	UpdatedAt time.Time       `json:"updated_at" db:"updated_at"`
}

type UserStatus string

const (
	UserStatusActive   UserStatus = "active"
	UserStatusInactive UserStatus = "inactive"
	UserStatusBlocked  UserStatus = "blocked"
)

type RegisterRequest struct {
	Email     string `json:"email" validate:"required,email"`
	Password  string `json:"password" validate:"required,min=8"`
	FirstName string `json:"first_name" validate:"required,min=2"`
	LastName  string `json:"last_name" validate:"required,min=2"`
	Phone     string `json:"phone" validate:"required,min=10"`
}

type LoginRequest struct {
	Email    string `json:"email" validate:"required,email"`
	Password string `json:"password" validate:"required"`
}

type LoginResponse struct {
	Token     string    `json:"token"`
	User      User      `json:"user"`
	ExpiresAt time.Time `json:"expires_at"`
}

type JWTClaims struct {
	UserID uuid.UUID `json:"user_id"`
	Email  string    `json:"email"`
	Exp    int64     `json:"exp"`
	Iat    int64     `json:"iat"`
}

func (u *User) HashPassword() error {
	hashedPassword, err := bcrypt.GenerateFromPassword([]byte(u.Password), bcrypt.DefaultCost)
	if err != nil {
		return err
	}
	u.Password = string(hashedPassword)
	return nil
}

func (u *User) CheckPassword(password string) bool {
	err := bcrypt.CompareHashAndPassword([]byte(u.Password), []byte(password))
	return err == nil
}

func (u *User) IsActive() bool {
	return u.Status == UserStatusActive
}

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
