package tests

import (
	"context"
	"testing"

	"github.com/golang/mock/gomock"
	"github.com/google/uuid"
	"github.com/shopspring/decimal"
	"github.com/stretchr/testify/assert"
	"github.com/stretchr/testify/require"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/mocks"
	"go-transaction-service/internal/usecase"
	"go-transaction-service/pkg/errors"
)

//go:generate mockgen -source=../internal/domain/repositories/transaction_repository.go -destination=../internal/mocks/transaction_repository_mock.go

func TestTransactionUseCase_ProcessPayment(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repositories
	mockTransactionRepo := mocks.NewMockTransactionRepository(ctrl)
	mockUserRepo := mocks.NewMockUserRepository(ctrl)
	mockPaymentGateway := mocks.NewMockPaymentGateway(ctrl)

	// Create use case
	transactionUseCase := usecase.NewTransactionUseCase(mockTransactionRepo, mockUserRepo, mockPaymentGateway)

	t.Run("successful payment", func(t *testing.T) {
		// Test data
		senderID := uuid.New()
		recipientID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		sender := &entities.User{
			ID:        senderID,
			Email:     "sender@example.com",
			FirstName: "John",
			LastName:  "Sender",
			Status:    entities.UserStatusActive,
		}

		recipient := &entities.User{
			ID:        recipientID,
			Email:     "recipient@example.com",
			FirstName: "Jane",
			LastName:  "Recipient",
			Status:    entities.UserStatusActive,
		}

		req := entities.PaymentRequest{
			Amount:      amount,
			Description: "Test payment",
			ToUserID:    recipientID,
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), senderID).Return(sender, nil)
		mockUserRepo.EXPECT().GetByID(gomock.Any(), recipientID).Return(recipient, nil)
		mockUserRepo.EXPECT().GetBalance(gomock.Any(), senderID).Return(decimal.NewFromFloat(500.00), nil)
		mockTransactionRepo.EXPECT().Create(gomock.Any(), gomock.Any()).Return(nil)
		mockUserRepo.EXPECT().SubtractBalance(gomock.Any(), senderID, amount).Return(nil)
		mockUserRepo.EXPECT().AddBalance(gomock.Any(), recipientID, amount).Return(nil)
		mockTransactionRepo.EXPECT().Update(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		response, err := transactionUseCase.ProcessPayment(context.Background(), senderID, req)

		// Assert
		require.NoError(t, err)
		assert.NotNil(t, response)
		assert.Equal(t, amount, response.Amount)
		assert.Equal(t, entities.TransactionStatusCompleted, response.Status)
		assert.NotEmpty(t, response.Reference)
	})

	t.Run("insufficient balance", func(t *testing.T) {
		// Test data
		senderID := uuid.New()
		recipientID := uuid.New()
		amount := decimal.NewFromFloat(1000.00)

		sender := &entities.User{
			ID:        senderID,
			Email:     "sender@example.com",
			FirstName: "John",
			LastName:  "Sender",
			Status:    entities.UserStatusActive,
		}

		recipient := &entities.User{
			ID:        recipientID,
			Email:     "recipient@example.com",
			FirstName: "Jane",
			LastName:  "Recipient",
			Status:    entities.UserStatusActive,
		}

		req := entities.PaymentRequest{
			Amount:      amount,
			Description: "Test payment",
			ToUserID:    recipientID,
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), senderID).Return(sender, nil)
		mockUserRepo.EXPECT().GetByID(gomock.Any(), recipientID).Return(recipient, nil)
		mockUserRepo.EXPECT().GetBalance(gomock.Any(), senderID).Return(decimal.NewFromFloat(100.00), nil)

		// Execute
		response, err := transactionUseCase.ProcessPayment(context.Background(), senderID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsValidationError(err))
	})

	t.Run("sender not found", func(t *testing.T) {
		// Test data
		senderID := uuid.New()
		recipientID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		req := entities.PaymentRequest{
			Amount:      amount,
			Description: "Test payment",
			ToUserID:    recipientID,
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), senderID).Return(nil, customerrors.NewNotFoundError("User not found"))

		// Execute
		response, err := transactionUseCase.ProcessPayment(context.Background(), senderID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsNotFoundError(err))
	})

	t.Run("recipient not found", func(t *testing.T) {
		// Test data
		senderID := uuid.New()
		recipientID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		sender := &entities.User{
			ID:        senderID,
			Email:     "sender@example.com",
			FirstName: "John",
			LastName:  "Sender",
			Status:    entities.UserStatusActive,
		}

		req := entities.PaymentRequest{
			Amount:      amount,
			Description: "Test payment",
			ToUserID:    recipientID,
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), senderID).Return(sender, nil)
		mockUserRepo.EXPECT().GetByID(gomock.Any(), recipientID).Return(nil, customerrors.NewNotFoundError("User not found"))

		// Execute
		response, err := transactionUseCase.ProcessPayment(context.Background(), senderID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsNotFoundError(err))
	})

	t.Run("inactive sender", func(t *testing.T) {
		// Test data
		senderID := uuid.New()
		recipientID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		sender := &entities.User{
			ID:        senderID,
			Email:     "sender@example.com",
			FirstName: "John",
			LastName:  "Sender",
			Status:    entities.UserStatusInactive,
		}

		req := entities.PaymentRequest{
			Amount:      amount,
			Description: "Test payment",
			ToUserID:    recipientID,
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), senderID).Return(sender, nil)

		// Execute
		response, err := transactionUseCase.ProcessPayment(context.Background(), senderID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsValidationError(err))
	})
}

func TestTransactionUseCase_TopupBalance(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repositories
	mockTransactionRepo := mocks.NewMockTransactionRepository(ctrl)
	mockUserRepo := mocks.NewMockUserRepository(ctrl)
	mockPaymentGateway := mocks.NewMockPaymentGateway(ctrl)

	// Create use case
	transactionUseCase := usecase.NewTransactionUseCase(mockTransactionRepo, mockUserRepo, mockPaymentGateway)

	t.Run("successful topup", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		user := &entities.User{
			ID:        userID,
			Email:     "user@example.com",
			FirstName: "John",
			LastName:  "User",
			Status:    entities.UserStatusActive,
		}

		req := entities.TopupRequest{
			Amount:        amount,
			PaymentMethod: "credit_card",
		}

		paymentResp := &usecase.PaymentGatewayResponse{
			OrderID:    "TXN-12345678",
			Status:     "pending",
			PaymentURL: "https://payment.gateway.com/pay/12345",
			GatewayID:  "gateway-123",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), userID).Return(user, nil)
		mockTransactionRepo.EXPECT().Create(gomock.Any(), gomock.Any()).Return(nil)
		mockPaymentGateway.EXPECT().CreateTopupTransaction(gomock.Any(), userID, amount.String(), gomock.Any()).Return(paymentResp, nil)
		mockTransactionRepo.EXPECT().Update(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		response, err := transactionUseCase.TopupBalance(context.Background(), userID, req)

		// Assert
		require.NoError(t, err)
		assert.NotNil(t, response)
		assert.Equal(t, amount, response.Amount)
		assert.Equal(t, entities.TransactionStatusProcessing, response.Status)
		assert.Equal(t, paymentResp.PaymentURL, response.PaymentURL)
		assert.NotEmpty(t, response.Reference)
	})

	t.Run("user not found", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		req := entities.TopupRequest{
			Amount:        amount,
			PaymentMethod: "credit_card",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), userID).Return(nil, customerrors.NewNotFoundError("User not found"))

		// Execute
		response, err := transactionUseCase.TopupBalance(context.Background(), userID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsNotFoundError(err))
	})

	t.Run("inactive user", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		user := &entities.User{
			ID:        userID,
			Email:     "user@example.com",
			FirstName: "John",
			LastName:  "User",
			Status:    entities.UserStatusInactive,
		}

		req := entities.TopupRequest{
			Amount:        amount,
			PaymentMethod: "credit_card",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), userID).Return(user, nil)

		// Execute
		response, err := transactionUseCase.TopupBalance(context.Background(), userID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsValidationError(err))
	})

	t.Run("payment gateway error", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		amount := decimal.NewFromFloat(100.00)

		user := &entities.User{
			ID:        userID,
			Email:     "user@example.com",
			FirstName: "John",
			LastName:  "User",
			Status:    entities.UserStatusActive,
		}

		req := entities.TopupRequest{
			Amount:        amount,
			PaymentMethod: "credit_card",
		}

		// Mock expectations
		mockUserRepo.EXPECT().GetByID(gomock.Any(), userID).Return(user, nil)
		mockTransactionRepo.EXPECT().Create(gomock.Any(), gomock.Any()).Return(nil)
		mockPaymentGateway.EXPECT().CreateTopupTransaction(gomock.Any(), userID, amount.String(), gomock.Any()).Return(nil, assert.AnError)
		mockTransactionRepo.EXPECT().Update(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		response, err := transactionUseCase.TopupBalance(context.Background(), userID, req)

		// Assert
		require.Error(t, err)
		assert.Nil(t, response)
		assert.True(t, customerrors.IsInternalError(err))
	})
}

func TestTransactionUseCase_ProcessCallback(t *testing.T) {
	ctrl := gomock.NewController(t)
	defer ctrl.Finish()

	// Mock repositories
	mockTransactionRepo := mocks.NewMockTransactionRepository(ctrl)
	mockUserRepo := mocks.NewMockUserRepository(ctrl)
	mockPaymentGateway := mocks.NewMockPaymentGateway(ctrl)

	// Create use case
	transactionUseCase := usecase.NewTransactionUseCase(mockTransactionRepo, mockUserRepo, mockPaymentGateway)

	t.Run("successful callback processing for topup", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		reference := "TXN-12345678"
		amount := decimal.NewFromFloat(100.00)

		transaction := &entities.Transaction{
			ID:          uuid.New(),
			UserID:      userID,
			Type:        entities.TransactionTypeTopup,
			Amount:      amount,
			Status:      entities.TransactionStatusProcessing,
			Reference:   reference,
			Description: "Balance top-up",
		}

		// Mock expectations
		mockTransactionRepo.EXPECT().GetByReference(gomock.Any(), reference).Return(transaction, nil)
		mockUserRepo.EXPECT().AddBalance(gomock.Any(), userID, amount).Return(nil)
		mockTransactionRepo.EXPECT().Update(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		err := transactionUseCase.ProcessCallback(context.Background(), reference, entities.TransactionStatusCompleted)

		// Assert
		require.NoError(t, err)
		assert.Equal(t, entities.TransactionStatusCompleted, transaction.Status)
		assert.NotNil(t, transaction.ProcessedAt)
	})

	t.Run("transaction not found", func(t *testing.T) {
		// Test data
		reference := "TXN-nonexistent"

		// Mock expectations
		mockTransactionRepo.EXPECT().GetByReference(gomock.Any(), reference).Return(nil, customerrors.NewNotFoundError("Transaction not found"))

		// Execute
		err := transactionUseCase.ProcessCallback(context.Background(), reference, entities.TransactionStatusCompleted)

		// Assert
		require.Error(t, err)
		assert.True(t, customerrors.IsNotFoundError(err))
	})

	t.Run("transaction already completed", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		reference := "TXN-12345678"
		amount := decimal.NewFromFloat(100.00)

		transaction := &entities.Transaction{
			ID:          uuid.New(),
			UserID:      userID,
			Type:        entities.TransactionTypeTopup,
			Amount:      amount,
			Status:      entities.TransactionStatusCompleted,
			Reference:   reference,
			Description: "Balance top-up",
		}

		// Mock expectations
		mockTransactionRepo.EXPECT().GetByReference(gomock.Any(), reference).Return(transaction, nil)

		// Execute
		err := transactionUseCase.ProcessCallback(context.Background(), reference, entities.TransactionStatusCompleted)

		// Assert
		require.Error(t, err)
		assert.True(t, customerrors.IsValidationError(err))
	})

	t.Run("failed transaction callback", func(t *testing.T) {
		// Test data
		userID := uuid.New()
		reference := "TXN-12345678"
		amount := decimal.NewFromFloat(100.00)

		transaction := &entities.Transaction{
			ID:          uuid.New(),
			UserID:      userID,
			Type:        entities.TransactionTypeTopup,
			Amount:      amount,
			Status:      entities.TransactionStatusProcessing,
			Reference:   reference,
			Description: "Balance top-up",
		}

		// Mock expectations
		mockTransactionRepo.EXPECT().GetByReference(gomock.Any(), reference).Return(transaction, nil)
		mockTransactionRepo.EXPECT().Update(gomock.Any(), gomock.Any()).Return(nil)

		// Execute
		err := transactionUseCase.ProcessCallback(context.Background(), reference, entities.TransactionStatusFailed)

		// Assert
		require.NoError(t, err)
		assert.Equal(t, entities.TransactionStatusFailed, transaction.Status)
	})
}
