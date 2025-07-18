package database

import (
	"context"
	"database/sql"

	"github.com/google/uuid"
	"github.com/lib/pq"
	"github.com/shopspring/decimal"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/domain/repositories"
	"go-transaction-service/pkg/errors"
)

type postgresUserRepository struct {
	db *sql.DB
}

func NewPostgresUserRepository(db *sql.DB) repositories.UserRepository {
	return &postgresUserRepository{db: db}
}

func (r *postgresUserRepository) Create(ctx context.Context, user *entities.User) error {
	query := `
		INSERT INTO users (id, email, password, first_name, last_name, phone, balance, status, created_at, updated_at)
		VALUES ($1, $2, $3, $4, $5, $6, $7, $8, $9, $10)
	`
	
	_, err := r.db.ExecContext(ctx, query,
		user.ID,
		user.Email,
		user.Password,
		user.FirstName,
		user.LastName,
		user.Phone,
		user.Balance,
		user.Status,
		user.CreatedAt,
		user.UpdatedAt,
	)
	
	if err != nil {
		if pqErr, ok := err.(*pq.Error); ok {
			if pqErr.Code == "23505" { // unique_violation
				return customerrors.NewValidationError("Email already exists")
			}
		}
		return customerrors.NewInternalError("Failed to create user", err)
	}
	
	return nil
}

func (r *postgresUserRepository) GetByID(ctx context.Context, id uuid.UUID) (*entities.User, error) {
	user := &entities.User{}
	query := `
		SELECT id, email, password, first_name, last_name, phone, balance, status, created_at, updated_at
		FROM users
		WHERE id = $1
	`
	
	err := r.db.QueryRowContext(ctx, query, id).Scan(
		&user.ID,
		&user.Email,
		&user.Password,
		&user.FirstName,
		&user.LastName,
		&user.Phone,
		&user.Balance,
		&user.Status,
		&user.CreatedAt,
		&user.UpdatedAt,
	)
	
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, customerrors.NewNotFoundError("User not found")
		}
		return nil, customerrors.NewInternalError("Failed to get user", err)
	}
	
	return user, nil
}

func (r *postgresUserRepository) GetByEmail(ctx context.Context, email string) (*entities.User, error) {
	user := &entities.User{}
	query := `
		SELECT id, email, password, first_name, last_name, phone, balance, status, created_at, updated_at
		FROM users
		WHERE email = $1
	`
	
	err := r.db.QueryRowContext(ctx, query, email).Scan(
		&user.ID,
		&user.Email,
		&user.Password,
		&user.FirstName,
		&user.LastName,
		&user.Phone,
		&user.Balance,
		&user.Status,
		&user.CreatedAt,
		&user.UpdatedAt,
	)
	
	if err != nil {
		if err == sql.ErrNoRows {
			return nil, customerrors.NewNotFoundError("User not found")
		}
		return nil, customerrors.NewInternalError("Failed to get user", err)
	}
	
	return user, nil
}

func (r *postgresUserRepository) Update(ctx context.Context, user *entities.User) error {
	query := `
		UPDATE users
		SET email = $2, password = $3, first_name = $4, last_name = $5, phone = $6, 
		    balance = $7, status = $8, updated_at = $9
		WHERE id = $1
	`
	
	result, err := r.db.ExecContext(ctx, query,
		user.ID,
		user.Email,
		user.Password,
		user.FirstName,
		user.LastName,
		user.Phone,
		user.Balance,
		user.Status,
		user.UpdatedAt,
	)
	
	if err != nil {
		return customerrors.NewInternalError("Failed to update user", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewNotFoundError("User not found")
	}
	
	return nil
}

func (r *postgresUserRepository) UpdateBalance(ctx context.Context, userID uuid.UUID, balance decimal.Decimal) error {
	query := `
		UPDATE users
		SET balance = $2, updated_at = NOW()
		WHERE id = $1
	`
	
	result, err := r.db.ExecContext(ctx, query, userID, balance)
	if err != nil {
		return customerrors.NewInternalError("Failed to update balance", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewNotFoundError("User not found")
	}
	
	return nil
}

func (r *postgresUserRepository) AddBalance(ctx context.Context, userID uuid.UUID, amount decimal.Decimal) error {
	query := `
		UPDATE users
		SET balance = balance + $2, updated_at = NOW()
		WHERE id = $1
	`
	
	result, err := r.db.ExecContext(ctx, query, userID, amount)
	if err != nil {
		return customerrors.NewInternalError("Failed to add balance", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewNotFoundError("User not found")
	}
	
	return nil
}

func (r *postgresUserRepository) SubtractBalance(ctx context.Context, userID uuid.UUID, amount decimal.Decimal) error {
	query := `
		UPDATE users
		SET balance = balance - $2, updated_at = NOW()
		WHERE id = $1 AND balance >= $2
	`
	
	result, err := r.db.ExecContext(ctx, query, userID, amount)
	if err != nil {
		return customerrors.NewInternalError("Failed to subtract balance", err)
	}
	
	rowsAffected, err := result.RowsAffected()
	if err != nil {
		return customerrors.NewInternalError("Failed to get rows affected", err)
	}
	
	if rowsAffected == 0 {
		return customerrors.NewValidationError("Insufficient balance")
	}
	
	return nil
}

func (r *postgresUserRepository) GetBalance(ctx context.Context, userID uuid.UUID) (decimal.Decimal, error) {
	var balance decimal.Decimal
	query := `SELECT balance FROM users WHERE id = $1`
	
	err := r.db.QueryRowContext(ctx, query, userID).Scan(&balance)
	if err != nil {
		if err == sql.ErrNoRows {
			return decimal.Zero, customerrors.NewNotFoundError("User not found")
		}
		return decimal.Zero, customerrors.NewInternalError("Failed to get balance", err)
	}
	
	return balance, nil
}

func (r *postgresUserRepository) CheckEmailExists(ctx context.Context, email string) (bool, error) {
	var exists bool
	query := `SELECT EXISTS(SELECT 1 FROM users WHERE email = $1)`
	
	err := r.db.QueryRowContext(ctx, query, email).Scan(&exists)
	if err != nil {
		return false, customerrors.NewInternalError("Failed to check email existence", err)
	}
	
	return exists, nil
}
