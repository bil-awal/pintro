package usecase

import (
	"context"
	"errors"
	"time"

	"github.com/golang-jwt/jwt/v5"
	"github.com/google/uuid"
	"go-transaction-service/internal/config"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/domain/repositories"
	"go-transaction-service/pkg/errors"
)

type AuthUseCase interface {
	Register(ctx context.Context, req entities.RegisterRequest) (*entities.User, error)
	Login(ctx context.Context, req entities.LoginRequest) (*entities.LoginResponse, error)
	ValidateToken(ctx context.Context, tokenString string) (*entities.JWTClaims, error)
	GetUserByID(ctx context.Context, userID uuid.UUID) (*entities.User, error)
}

type authUseCase struct {
	userRepo repositories.UserRepository
	config   *config.Config
}

func NewAuthUseCase(userRepo repositories.UserRepository, config *config.Config) AuthUseCase {
	return &authUseCase{
		userRepo: userRepo,
		config:   config,
	}
}

func (a *authUseCase) Register(ctx context.Context, req entities.RegisterRequest) (*entities.User, error) {
	// Check if email already exists
	exists, err := a.userRepo.CheckEmailExists(ctx, req.Email)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to check email existence", err)
	}
	if exists {
		return nil, customerrors.NewValidationError("Email already exists")
	}

	// Create new user
	user, err := entities.NewUser(req)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to create user", err)
	}

	// Save user to database
	if err := a.userRepo.Create(ctx, user); err != nil {
		return nil, customerrors.NewInternalError("Failed to save user", err)
	}

	return user, nil
}

func (a *authUseCase) Login(ctx context.Context, req entities.LoginRequest) (*entities.LoginResponse, error) {
	// Get user by email
	user, err := a.userRepo.GetByEmail(ctx, req.Email)
	if err != nil {
		return nil, customerrors.NewUnauthorizedError("Invalid credentials")
	}

	// Check if user is active
	if !user.IsActive() {
		return nil, customerrors.NewUnauthorizedError("User account is inactive")
	}

	// Verify password
	if !user.CheckPassword(req.Password) {
		return nil, customerrors.NewUnauthorizedError("Invalid credentials")
	}

	// Generate JWT token
	token, expiresAt, err := a.generateJWT(user)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to generate token", err)
	}

	return &entities.LoginResponse{
		Token:     token,
		User:      *user,
		ExpiresAt: expiresAt,
	}, nil
}

func (a *authUseCase) ValidateToken(ctx context.Context, tokenString string) (*entities.JWTClaims, error) {
	token, err := jwt.Parse(tokenString, func(token *jwt.Token) (interface{}, error) {
		if _, ok := token.Method.(*jwt.SigningMethodHMAC); !ok {
			return nil, errors.New("invalid signing method")
		}
		return []byte(a.config.JWT.SecretKey), nil
	})

	if err != nil {
		return nil, customerrors.NewUnauthorizedError("Invalid token")
	}

	if claims, ok := token.Claims.(jwt.MapClaims); ok && token.Valid {
		userIDStr, ok := claims["user_id"].(string)
		if !ok {
			return nil, customerrors.NewUnauthorizedError("Invalid token claims")
		}

		userID, err := uuid.Parse(userIDStr)
		if err != nil {
			return nil, customerrors.NewUnauthorizedError("Invalid user ID in token")
		}

		email, ok := claims["email"].(string)
		if !ok {
			return nil, customerrors.NewUnauthorizedError("Invalid token claims")
		}

		exp, ok := claims["exp"].(float64)
		if !ok {
			return nil, customerrors.NewUnauthorizedError("Invalid token claims")
		}

		iat, ok := claims["iat"].(float64)
		if !ok {
			return nil, customerrors.NewUnauthorizedError("Invalid token claims")
		}

		return &entities.JWTClaims{
			UserID: userID,
			Email:  email,
			Exp:    int64(exp),
			Iat:    int64(iat),
		}, nil
	}

	return nil, customerrors.NewUnauthorizedError("Invalid token")
}

func (a *authUseCase) GetUserByID(ctx context.Context, userID uuid.UUID) (*entities.User, error) {
	user, err := a.userRepo.GetByID(ctx, userID)
	if err != nil {
		return nil, customerrors.NewNotFoundError("User not found")
	}

	return user, nil
}

func (a *authUseCase) generateJWT(user *entities.User) (string, time.Time, error) {
	expiresAt := time.Now().Add(a.config.JWT.ExpireDuration)
	
	claims := jwt.MapClaims{
		"user_id": user.ID.String(),
		"email":   user.Email,
		"exp":     expiresAt.Unix(),
		"iat":     time.Now().Unix(),
	}

	token := jwt.NewWithClaims(jwt.SigningMethodHS256, claims)
	tokenString, err := token.SignedString([]byte(a.config.JWT.SecretKey))
	if err != nil {
		return "", time.Time{}, err
	}

	return tokenString, expiresAt, nil
}
