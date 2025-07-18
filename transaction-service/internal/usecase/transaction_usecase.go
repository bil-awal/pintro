package usecase

import (
	"context"
	"fmt"

	"github.com/google/uuid"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/domain/repositories"
	"go-transaction-service/pkg/errors"
)

type TransactionUseCase interface {
	TopupBalance(ctx context.Context, userID uuid.UUID, req entities.TopupRequest) (*entities.TransactionResponse, error)
	ProcessPayment(ctx context.Context, userID uuid.UUID, req entities.PaymentRequest) (*entities.TransactionResponse, error)
	GetTransactionHistory(ctx context.Context, userID uuid.UUID, limit, offset int) ([]*entities.TransactionHistoryResponse, error)
	GetBalance(ctx context.Context, userID uuid.UUID) (*entities.BalanceResponse, error)
	ProcessCallback(ctx context.Context, reference string, status entities.TransactionStatus) error
	GetTransactionByReference(ctx context.Context, reference string) (*entities.Transaction, error)
}

type transactionUseCase struct {
	transactionRepo repositories.TransactionRepository
	userRepo        repositories.UserRepository
	paymentGateway  PaymentGateway
}

type PaymentGateway interface {
	CreateTopupTransaction(ctx context.Context, userID uuid.UUID, amount string, orderId string) (*PaymentGatewayResponse, error)
	GetTransactionStatus(ctx context.Context, orderId string) (*PaymentGatewayResponse, error)
}

type PaymentGatewayResponse struct {
	OrderID     string
	Status      string
	PaymentURL  string
	GatewayID   string
}

func NewTransactionUseCase(
	transactionRepo repositories.TransactionRepository,
	userRepo repositories.UserRepository,
	paymentGateway PaymentGateway,
) TransactionUseCase {
	return &transactionUseCase{
		transactionRepo: transactionRepo,
		userRepo:        userRepo,
		paymentGateway:  paymentGateway,
	}
}

func (t *transactionUseCase) TopupBalance(ctx context.Context, userID uuid.UUID, req entities.TopupRequest) (*entities.TransactionResponse, error) {
	// Validate user exists
	user, err := t.userRepo.GetByID(ctx, userID)
	if err != nil {
		return nil, customerrors.NewNotFoundError("User not found")
	}

	if !user.IsActive() {
		return nil, customerrors.NewValidationError("User account is inactive")
	}

	// Create transaction
	transaction := entities.NewTransaction(userID, entities.TransactionTypeTopup, req.Amount, "Balance top-up")
	
	// Save transaction to database
	if err := t.transactionRepo.Create(ctx, transaction); err != nil {
		return nil, customerrors.NewInternalError("Failed to create transaction", err)
	}

	// Create payment with payment gateway
	paymentResp, err := t.paymentGateway.CreateTopupTransaction(ctx, userID, req.Amount.String(), transaction.Reference)
	if err != nil {
		// Mark transaction as failed
		transaction.MarkAsFailed()
		t.transactionRepo.Update(ctx, transaction)
		return nil, customerrors.NewInternalError("Failed to create payment", err)
	}

	// Update transaction with payment gateway info
	transaction.PaymentGatewayID = paymentResp.GatewayID
	transaction.MarkAsProcessing()
	if err := t.transactionRepo.Update(ctx, transaction); err != nil {
		return nil, customerrors.NewInternalError("Failed to update transaction", err)
	}

	return &entities.TransactionResponse{
		ID:         transaction.ID,
		Status:     transaction.Status,
		Amount:     transaction.Amount,
		PaymentURL: paymentResp.PaymentURL,
		Reference:  transaction.Reference,
		CreatedAt:  transaction.CreatedAt,
	}, nil
}

func (t *transactionUseCase) ProcessPayment(ctx context.Context, userID uuid.UUID, req entities.PaymentRequest) (*entities.TransactionResponse, error) {
	// Validate user exists
	user, err := t.userRepo.GetByID(ctx, userID)
	if err != nil {
		return nil, customerrors.NewNotFoundError("User not found")
	}

	if !user.IsActive() {
		return nil, customerrors.NewValidationError("User account is inactive")
	}

	// Validate recipient exists
	recipient, err := t.userRepo.GetByID(ctx, req.ToUserID)
	if err != nil {
		return nil, customerrors.NewNotFoundError("Recipient not found")
	}

	if !recipient.IsActive() {
		return nil, customerrors.NewValidationError("Recipient account is inactive")
	}

	// Check if user has sufficient balance
	balance, err := t.userRepo.GetBalance(ctx, userID)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to get user balance", err)
	}

	if balance.LessThan(req.Amount) {
		return nil, customerrors.NewValidationError("Insufficient balance")
	}

	// Create transaction
	transaction := entities.NewTransaction(userID, entities.TransactionTypePayment, req.Amount, req.Description)
	transaction.Metadata["to_user_id"] = req.ToUserID.String()
	
	// Save transaction to database
	if err := t.transactionRepo.Create(ctx, transaction); err != nil {
		return nil, customerrors.NewInternalError("Failed to create transaction", err)
	}

	// Process payment (deduct from sender, add to recipient)
	if err := t.userRepo.SubtractBalance(ctx, userID, req.Amount); err != nil {
		transaction.MarkAsFailed()
		t.transactionRepo.Update(ctx, transaction)
		return nil, customerrors.NewInternalError("Failed to deduct balance", err)
	}

	if err := t.userRepo.AddBalance(ctx, req.ToUserID, req.Amount); err != nil {
		// Rollback: add balance back to sender
		t.userRepo.AddBalance(ctx, userID, req.Amount)
		transaction.MarkAsFailed()
		t.transactionRepo.Update(ctx, transaction)
		return nil, customerrors.NewInternalError("Failed to add balance to recipient", err)
	}

	// Mark transaction as completed
	transaction.MarkAsCompleted()
	if err := t.transactionRepo.Update(ctx, transaction); err != nil {
		return nil, customerrors.NewInternalError("Failed to update transaction", err)
	}

	return &entities.TransactionResponse{
		ID:        transaction.ID,
		Status:    transaction.Status,
		Amount:    transaction.Amount,
		Reference: transaction.Reference,
		CreatedAt: transaction.CreatedAt,
	}, nil
}

func (t *transactionUseCase) GetTransactionHistory(ctx context.Context, userID uuid.UUID, limit, offset int) ([]*entities.TransactionHistoryResponse, error) {
	transactions, err := t.transactionRepo.GetByUserID(ctx, userID, limit, offset)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to get transactions", err)
	}

	var response []*entities.TransactionHistoryResponse
	for _, tx := range transactions {
		response = append(response, &entities.TransactionHistoryResponse{
			ID:          tx.ID,
			Type:        tx.Type,
			Amount:      tx.Amount,
			Status:      tx.Status,
			Description: tx.Description,
			Reference:   tx.Reference,
			ProcessedAt: tx.ProcessedAt,
			CreatedAt:   tx.CreatedAt,
		})
	}

	return response, nil
}

func (t *transactionUseCase) GetBalance(ctx context.Context, userID uuid.UUID) (*entities.BalanceResponse, error) {
	balance, err := t.userRepo.GetBalance(ctx, userID)
	if err != nil {
		return nil, customerrors.NewInternalError("Failed to get balance", err)
	}

	return &entities.BalanceResponse{
		Balance: balance,
	}, nil
}

func (t *transactionUseCase) ProcessCallback(ctx context.Context, reference string, status entities.TransactionStatus) error {
	// Get transaction by reference
	transaction, err := t.transactionRepo.GetByReference(ctx, reference)
	if err != nil {
		return customerrors.NewNotFoundError("Transaction not found")
	}

	// Only process if transaction is in pending or processing state
	if !transaction.CanBeProcessed() {
		return customerrors.NewValidationError("Transaction cannot be processed")
	}

	// Update transaction status based on callback
	switch status {
	case entities.TransactionStatusCompleted:
		// For top-up transactions, add balance to user
		if transaction.Type == entities.TransactionTypeTopup {
			if err := t.userRepo.AddBalance(ctx, transaction.UserID, transaction.Amount); err != nil {
				return customerrors.NewInternalError("Failed to add balance", err)
			}
		}
		transaction.MarkAsCompleted()
	case entities.TransactionStatusFailed:
		transaction.MarkAsFailed()
	case entities.TransactionStatusCancelled:
		transaction.MarkAsCancelled()
	default:
		return customerrors.NewValidationError(fmt.Sprintf("Invalid transaction status: %s", status))
	}

	// Update transaction in database
	if err := t.transactionRepo.Update(ctx, transaction); err != nil {
		return customerrors.NewInternalError("Failed to update transaction", err)
	}

	return nil
}

func (t *transactionUseCase) GetTransactionByReference(ctx context.Context, reference string) (*entities.Transaction, error) {
	transaction, err := t.transactionRepo.GetByReference(ctx, reference)
	if err != nil {
		return nil, customerrors.NewNotFoundError("Transaction not found")
	}

	return transaction, nil
}
