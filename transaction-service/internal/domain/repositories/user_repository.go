package repositories

import (
	"context"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
	"go-transaction-service/internal/domain/entities"
)

type UserRepository interface {
	Create(ctx context.Context, user *entities.User) error
	GetByID(ctx context.Context, id uuid.UUID) (*entities.User, error)
	GetByEmail(ctx context.Context, email string) (*entities.User, error)
	Update(ctx context.Context, user *entities.User) error
	UpdateBalance(ctx context.Context, userID uuid.UUID, balance decimal.Decimal) error
	AddBalance(ctx context.Context, userID uuid.UUID, amount decimal.Decimal) error
	SubtractBalance(ctx context.Context, userID uuid.UUID, amount decimal.Decimal) error
	GetBalance(ctx context.Context, userID uuid.UUID) (decimal.Decimal, error)
	CheckEmailExists(ctx context.Context, email string) (bool, error)
}
