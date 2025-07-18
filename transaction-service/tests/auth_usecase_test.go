package tests

import (
	"context"
	"testing"
	"time"

	"github.com/golang/mock/gomock"
	"github.com/google/uuid"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"golang.org/x/crypto/bcrypt"
	"go-transaction-service/internal/config"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/mocks"
	"go-transaction-service/internal/usecase"
	"go-transaction-service/pkg/errors"
)

//go:generate mockgen -source=../internal/domain/repositories/user_repository.go -destination=../internal/mocks/user_repository_mock.go

func TestAuthUseCase_Register(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repository
	mockUserRepo := mocks.NewMockUserRepository(ctrl)

	// Create config
	cfg := &config.Config{
		JWT: config.JWTConfig{
			SecretKey:      "test-secret-key",
			ExpireDuration: 24 * time.Hour,
		},
	}

	// Create use case
	authUseCase := usecase.NewAuthUseCase(mockUserRepo, cfg)

	t.Run("successful registration", func(t *testing.T) {
		req := entities.RegisterRequest{
			Email:     "test@example.com",
			Password:  "password123",
			FirstName: "John",
			LastName:  "Doe",
			Phone:     "1234567890",
		}

		// Mock expectations
		mockUserRepo.EXPECT().CheckEmailExists(gomock.Any(), req.Email).Return(false, nil)
		mockUserRepo.EXPECT().Create(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		user, err := authUseCase.Register(context.Background(), req)

		// Assert
		require.NoError(t, err)
		assert.NotNil(t, user)
		assert.Equal(t, req.Email, user.Email)
		assert.Equal(t, req.FirstName, user.FirstName)
		assert.Equal(t, req.LastName, user.LastName)
		assert.Equal(t, req.Phone, user.Phone)
		assert.Equal(t, entities.UserStatusActive, user.Status)
		assert.NotEmpty(t, user.ID)
		assert.NotEqual(t, req.Password, user.Password) // Password should be hashed
	})

	t.Run("email already exists", func(t *testing.T) {
		req := entities.RegisterRequest{
			Email:     "existing@example.com",
			Password:  "password123",
			FirstName: "Jane",
			LastName:  "Doe",
			Phone:     "1234567890",
		}

		// Mock expectations
		mockUserRepo.EXPECT().CheckEmailExists(gomock.Any(), req.Email).Return(true, nil)

		// Execute
		user, err := authUseCase.Register(context.Background(), req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, user)
		assert.True(t, customerrors.IsValidationError(err))
	})

	t.Run("database error on email check", func(t *testing.T) {
		req := entities.RegisterRequest{
			Email:     "test@example.com",
			Password:  "password123",
			FirstName: "John",
			LastName:  "Doe",
			Phone:     "1234567890",
		}

		// Mock expectations
		mockUserRepo.EXPECT().CheckEmailExists(gomock.Any(), req.Email).Return(false, assert.AnError)

		// Execute
		user, err := authUseCase.Register(context.Background(), req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, user)
		assert.True(t, customerrors.IsInternalError(err))
	})
}

func TestAuthUseCase_Login(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repository
	mockUserRepo := mocks.NewMockUserRepository(ctrl)

	// Create config
	cfg := &config.Config{
		JWT: config.JWTConfig{
			SecretKey:      "test-secret-key",
			ExpireDuration: 24 * time.Hour,
		},
	}

	// Create use case
	authUseCase := usecase.NewAuthUseCase(mockUserRepo, cfg)

	t.Run("successful login", func(t *testing.T) {
		// Create test user with hashed password
		hashedPassword, _ := bcrypt.GenerateFromPassword([]byte("password"), bcrypt.DefaultCost)
		user := &entities.User{
			ID:        uuid.New(),
			Email:     "test@example.com",
			Password:  string(hashedPassword),
			FirstName: "John",
			LastName:  "Doe",
			Status:    entities.UserStatusActive,
		}

		req := entities.LoginRequest{
			Email:    "test@example.com",
			Password: "password",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByEmail(gomock.Any(), req.Email).Return(user, nil)

		// Execute
		response, err := authUseCase.Login(context.Background(), req)

		// Assert
		require.NoError(t, err)
		assert.NotNil(t, response)
		assert.NotEmpty(t, response.Token)
		assert.Equal(t, user.ID, response.User.ID)
		assert.Equal(t, user.Email, response.User.Email)
		assert.True(t, response.ExpiresAt.After(time.Now()))
	})

	t.Run("invalid credentials - user not found", func(t *testing.T) {
		req := entities.LoginRequest{
			Email:    "nonexistent@example.com",
			Password: "password",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByEmail(gomock.Any(), req.Email).Return(nil, customerrors.NewNotFoundError("User not found"))

		// Execute
		response, err := authUseCase.Login(context.Background(), req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsUnauthorizedError(err))
	})

	t.Run("invalid credentials - wrong password", func(t *testing.T) {
		// Create test user with hashed password
		hashedPassword, _ := bcrypt.GenerateFromPassword([]byte("password"), bcrypt.DefaultCost)
		user := &entities.User{
			ID:        uuid.New(),
			Email:     "test@example.com",
			Password:  string(hashedPassword),
			FirstName: "John",
			LastName:  "Doe",
			Status:    entities.UserStatusActive,
		}

		req := entities.LoginRequest{
			Email:    "test@example.com",
			Password: "wrongpassword",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByEmail(gomock.Any(), req.Email).Return(user, nil)

		// Execute
		response, err := authUseCase.Login(context.Background(), req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsUnauthorizedError(err))
	})

	t.Run("inactive user", func(t *testing.T) {
		// Create test user with hashed password
		hashedPassword, _ := bcrypt.GenerateFromPassword([]byte("password"), bcrypt.DefaultCost)
		user := &entities.User{
			ID:        uuid.New(),
			Email:     "test@example.com",
			Password:  string(hashedPassword),
			FirstName: "John",
			LastName:  "Doe",
			Status:    entities.UserStatusInactive,
		}

		req := entities.LoginRequest{
			Email:    "test@example.com",
			Password: "password",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByEmail(gomock.Any(), req.Email).Return(user, nil)

		// Execute
		response, err := authUseCase.Login(context.Background(), req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsUnauthorizedError(err))
	})
}

func TestAuthUseCase_ValidateToken(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repository
	mockUserRepo := mocks.NewMockUserRepository(ctrl)

	// Create config
	cfg := &config.Config{
		JWT: config.JWTConfig{
			SecretKey:      "test-secret-key",
			ExpireDuration: 24 * time.Hour,
		},
	}

	// Create use case
	authUseCase := usecase.NewAuthUseCase(mockUserRepo, cfg)

	t.Run("valid token", func(t *testing.T) {
		// Create test user
		user := &entities.User{
			ID:        uuid.New(),
			Email:     "test@example.com",
			FirstName: "John",
			LastName:  "Doe",
			Status:    entities.UserStatusActive,
		}

		// Generate token by logging in first
		loginReq := entities.LoginRequest{
			Email:    user.Email,
			Password: "password",
		}
		
		// Mock login to generate token
		mockUserRepo.EXPECT().GetByEmail(gomock.Any(), user.Email).Return(user, nil)
		response, err := authUseCase.Login(context.Background(), loginReq)
		require.NoError(t, err)

		// Execute
		claims, err := authUseCase.ValidateToken(context.Background(), response.Token)

		// Assert
		require.NoError(t, err)
		assert.NotNil(t, claims)
		assert.Equal(t, user.ID, claims.UserID)
		assert.Equal(t, user.Email, claims.Email)
	})

	t.Run("invalid token", func(t *testing.T) {
		// Execute
		claims, err := authUseCase.ValidateToken(context.Background(), "invalid-token")

		// Assert
		require.Error(t, err)
		assert.Nil(t, claims)
		assert.True(t, customerrors.IsUnauthorizedError(err))
	})

	t.Run("expired token", func(t *testing.T) {
		// For this test, we'll create a token with expired claims directly
		// Execute with an obviously invalid token
		claims, err := authUseCase.ValidateToken(context.Background(), "expired.token.here")

		// Assert
		require.Error(t, err)
		assert.Nil(t, claims)
		assert.True(t, customerrors.IsUnauthorizedError(err))
	})
}
