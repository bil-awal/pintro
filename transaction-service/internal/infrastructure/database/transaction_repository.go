package database

import (
	"context"
	"database/sql"
	"encoding/json"

	"github.com/google/uuid"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/domain/repositories"
	"go-transaction-service/pkg/errors"
)

type postgresTransactionRepository struct {
	db *sql.DB
}

func NewPostgresTransactionRepository(db *sql.DB) repositories.TransactionRepository {
	return &postgresTransactionRepository{db: db}
}

func (r *postgresTransactionRepository) Create(ctx context.Context, transaction *entities.Transaction) error {
	metadataJSON, err := json.Marshal(transaction.Metadata)
	if err != nil {
		return customerrors.NewInternalError("Failed to marshal metadata", err)
	}

	query := `
		INSERT INTO transactions (id, user_id, type, amount, status, reference, payment_gateway_id, description, metadata, processed_at, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10, $11, $12)
	`
	
	_, err = r.db.ExecContext(ctx, query,
		transaction.ID,
		transaction.UserID,
		transaction.Type,
		transaction.Amount,
		transaction.Status,
		transaction.Reference,
		transaction.PaymentGatewayID,
		transaction.Description,
		metadataJSON,
		transaction.ProcessedAt,
		transaction.CreatedAt,
		transaction.UpdatedAt,
	)
	
	if err != nil {
		return customerrors.NewInternalError("Failed to create transaction", err)
	}
	
	return nil
}

func (r *postgresTransactionRepository) GetByID(ctx context.Context, id uuid.UUID) (*entities.Transaction, error) {
	transaction := &entities.Transaction{}
	var metadataJSON []byte
	
	query := `
		SELECT id, user_id, type, amount, status, reference, payment_gateway_id, description, metadata, processed_at, created_at, updated_at
		FROM transactions
		WHERE id = $1
	`
	
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&transaction.ID,
		&transaction.UserID,
		&transaction.Type,
		&transaction.Amount,
		&transaction.Status,
		&transaction.Reference,
		&transaction.PaymentGatewayID,
		&transaction.Description,
		&metadataJSON,
		&transaction.ProcessedAt,
		&transaction.CreatedAt,
		&transaction.UpdatedAt,
	)
	
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, customerrors.NewNotFoundError("Transaction not found")
		}
		return nil, customerrors.NewInternalError("Failed to get transaction", err)
	}
	
	// Unmarshal metadata
	if err := json.Unmarshal(metadataJSON, &transaction.Metadata); err != nil {
		return nil, customerrors.NewInternalError("Failed to unmarshal metadata", err)
	}
	
	return transaction, nil
}

func (r *postgresTransactionRepository) GetByReference(ctx context.Context, reference string) (*entities.Transaction, error) {
	transaction := &entities.Transaction{}
	var metadataJSON []byte
	
	query := `
		SELECT id, user_id, type, amount, status, reference, payment_gateway_id, description, metadata, processed_at, created_at, updated_at
		FROM transactions
		WHERE reference = $1
	`
	
	err := r.db.QueryRowContext(ctx, query, reference).Scan(
		&transaction.ID,
		&transaction.UserID,
		&transaction.Type,
		&transaction.Amount,
		&transaction.Status,
		&transaction.Reference,
		&transaction.PaymentGatewayID,
		&transaction.Description,
		&metadataJSON,
		&transaction.ProcessedAt,
		&transaction.CreatedAt,
		&transaction.UpdatedAt,
	)
	
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, customerrors.NewNotFoundError("Transaction not found")
		}
		return nil, customerrors.NewInternalError("Failed to get transaction", err)
	}
	
	// Unmarshal metadata
	if err := json.Unmarshal(metadataJSON, &transaction.Metadata); err != nil {
		return nil, customerrors.NewInternalError("Failed to unmarshal metadata", err)
	}
	
	return transaction, nil
}

func (r *postgresTransactionRepository) GetByUserID(ctx context.Context, userID uuid.UUID, limit, offset int) ([]*entities.Transaction, error) {
	query := `
		SELECT id, user_id, type, amount, status, reference, payment_gateway_id, description, metadata, processed_at, created_at, updated_at
		FROM transactions
		WHERE user_id = $1
		ORDER BY created_at DESC
		LIMIT $2 OFFSET $3
	`
	
	rows, err := r.db.QueryContext(ctx, query, userID, limit, offset)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to get transactions", err)
	}
	defer rows.Close()
	
	var transactions []*entities.Transaction
	for rows.Next() {
		transaction := &entities.Transaction{}
		var metadataJSON []byte
		
		err := rows.Scan(
			&transaction.ID,
			&transaction.UserID,
			&transaction.Type,
			&transaction.Amount,
			&transaction.Status,
			&transaction.Reference,
			&transaction.PaymentGatewayID,
			&transaction.Description,
			&metadataJSON,
			&transaction.ProcessedAt,
			&transaction.CreatedAt,
			&transaction.UpdatedAt,
		)
		
		if err != nil {
			return nil, customerrors.NewInternalError("Failed to scan transaction", err)
		}
		
		// Unmarshal metadata
		if err := json.Unmarshal(metadataJSON, &transaction.Metadata); err != nil {
			return nil, customerrors.NewInternalError("Failed to unmarshal metadata", err)
		}
		
		transactions = append(transactions, transaction)
	}
	
	if err := rows.Err(); err != nil {
		return nil, customerrors.NewInternalError("Failed to iterate transactions", err)
	}
	
	return transactions, nil
}

func (r *postgresTransactionRepository) UpdateStatus(ctx context.Context, id uuid.UUID, status entities.TransactionStatus) error {
	query := `
		UPDATE transactions
		SET status = $2, updated_at = NOW()
		WHERE id = $1
	`
	
	result, err := r.db.ExecContext(ctx, query, id, status)
	if err != nil {
		return customerrors.NewInternalError("Failed to update transaction status", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewNotFoundError("Transaction not found")
	}
	
	return nil
}

func (r *postgresTransactionRepository) Update(ctx context.Context, transaction *entities.Transaction) error {
	metadataJSON, err := json.Marshal(transaction.Metadata)
	if err != nil {
		return customerrors.NewInternalError("Failed to marshal metadata", err)
	}

	query := `
		UPDATE transactions
		SET type = $2, amount = $3, status = $4, reference = $5, payment_gateway_id = $6, 
		    description = $7, metadata = $8, processed_at = $9, updated_at = $10
		WHERE id = $1
	`
	
	result, err := r.db.ExecContext(ctx, query,
		transaction.ID,
		transaction.Type,
		transaction.Amount,
		transaction.Status,
		transaction.Reference,
		transaction.PaymentGatewayID,
		transaction.Description,
		metadataJSON,
		transaction.ProcessedAt,
		transaction.UpdatedAt,
	)
	
	if err != nil {
		return customerrors.NewInternalError("Failed to update transaction", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewNotFoundError("Transaction not found")
	}
	
	return nil
}

func (r *postgresTransactionRepository) GetPendingTransactions(ctx context.Context) ([]*entities.Transaction, error) {
	query := `
		SELECT id, user_id, type, amount, status, reference, payment_gateway_id, description, metadata, processed_at, created_at, updated_at
		FROM transactions
		WHERE status = $1
		ORDER BY created_at ASC
	`
	
	rows, err := r.db.QueryContext(ctx, query, entities.TransactionStatusPending)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to get pending transactions", err)
	}
	defer rows.Close()
	
	var transactions []*entities.Transaction
	for rows.Next() {
		transaction := &entities.Transaction{}
		var metadataJSON []byte
		
		err := rows.Scan(
			&transaction.ID,
			&transaction.UserID,
			&transaction.Type,
			&transaction.Amount,
			&transaction.Status,
			&transaction.Reference,
			&transaction.PaymentGatewayID,
			&transaction.Description,
			&metadataJSON,
			&transaction.ProcessedAt,
			&transaction.CreatedAt,
			&transaction.UpdatedAt,
		)
		
		if err != nil {
			return nil, customerrors.NewInternalError("Failed to scan transaction", err)
		}
		
		// Unmarshal metadata
		if err := json.Unmarshal(metadataJSON, &transaction.Metadata); err != nil {
			return nil, customerrors.NewInternalError("Failed to unmarshal metadata", err)
		}
		
		transactions = append(transactions, transaction)
	}
	
	if err := rows.Err(); err != nil {
		return nil, customerrors.NewInternalError("Failed to iterate transactions", err)
	}
	
	return transactions, nil
}

func (r *postgresTransactionRepository) CountByUserID(ctx context.Context, userID uuid.UUID) (int, error) {
	var count int
	query := `SELECT COUNT(*) FROM transactions WHERE user_id = $1`
	
	err := r.db.QueryRowContext(ctx, query, userID).Scan(&count)
	if err != nil {
		return 0, customerrors.NewInternalError("Failed to count transactions", err)
	}
	
	return count, nil
}
