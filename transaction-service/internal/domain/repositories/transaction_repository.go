package repositories

import (
	"context"

	"github.com/google/uuid"
	"go-transaction-service/internal/domain/entities"
)

type TransactionRepository interface {
	Create(ctx context.Context, transaction *entities.Transaction) error
	GetByID(ctx context.Context, id uuid.UUID) (*entities.Transaction, error)
	GetByReference(ctx context.Context, reference string) (*entities.Transaction, error)
	GetByUserID(ctx context.Context, userID uuid.UUID, limit, offset int) ([]*entities.Transaction, error)
	UpdateStatus(ctx context.Context, id uuid.UUID, status entities.TransactionStatus) error
	Update(ctx context.Context, transaction *entities.Transaction) error
	GetPendingTransactions(ctx context.Context) ([]*entities.Transaction, error)
	CountByUserID(ctx context.Context, userID uuid.UUID) (int, error)
}
