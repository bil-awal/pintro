package external

import (
	"context"
	"fmt"
	"strconv"

	"github.com/google/uuid"
	"github.com/midtrans/midtrans-go"
	"github.com/midtrans/midtrans-go/snap"
	"go-transaction-service/internal/config"
	"go-transaction-service/internal/usecase"
	"go.uber.org/zap"
)

type midtransPaymentGateway struct {
	snapClient *snap.Client
	config     *config.Config
	logger     *zap.Logger
}

func NewMidtransPaymentGateway(config *config.Config, logger *zap.Logger) usecase.PaymentGateway {
	var env midtrans.EnvironmentType
	if config.Midtrans.Environment == "production" {
		env = midtrans.Production
	} else {
		env = midtrans.Sandbox
	}

	snapClient := snap.Client{}
	snapClient.New(config.Midtrans.ServerKey, env)

	return &midtransPaymentGateway{
		snapClient: &snapClient,
		config:     config,
		logger:     logger,
	}
}

func (m *midtransPaymentGateway) CreateTopupTransaction(ctx context.Context, userID uuid.UUID, amount string, orderId string) (*usecase.PaymentGatewayResponse, error) {
	// Convert amount to integer (in cents/rupiah)
	amountFloat, err := strconv.ParseFloat(amount, 64)
	if err != nil {
		m.logger.Error("Failed to parse amount", zap.Error(err))
		return nil, fmt.Errorf("invalid amount format: %w", err)
	}

	// Convert to integer (Midtrans expects integer amount)
	grossAmount := int64(amountFloat)

	// Create Snap transaction request
	snapReq := &snap.Request{
		TransactionDetails: midtrans.TransactionDetails{
			OrderID:  orderId,
			GrossAmt: grossAmount,
		},
		CustomerDetail: &midtrans.CustomerDetails{
			FName: "User",
			LName: userID.String(),
		},
		EnabledPayments: snap.AllSnapPaymentType,
		CreditCard: &snap.CreditCardDetails{
			Secure: true,
		},
	}

	// Create transaction with Midtrans
	snapResp, err := m.snapClient.CreateTransaction(snapReq)
	if err != nil {
		m.logger.Error("Failed to create Midtrans transaction", 
			zap.Error(err),
			zap.String("order_id", orderId),
			zap.String("user_id", userID.String()))
		return nil, fmt.Errorf("failed to create payment transaction: %w", err)
	}

	m.logger.Info("Midtrans transaction created successfully", 
		zap.String("order_id", orderId),
		zap.String("token", snapResp.Token),
		zap.String("redirect_url", snapResp.RedirectURL))

	return &usecase.PaymentGatewayResponse{
		OrderID:    orderId,
		Status:     "pending",
		PaymentURL: snapResp.RedirectURL,
		GatewayID:  snapResp.Token,
	}, nil
}

func (m *midtransPaymentGateway) GetTransactionStatus(ctx context.Context, orderId string) (*usecase.PaymentGatewayResponse, error) {
	// In a real implementation, you would call Midtrans API to get transaction status
	// For now, we'll return a mock response
	m.logger.Info("Getting transaction status from Midtrans", 
		zap.String("order_id", orderId))

	// This would be replaced with actual Midtrans API call
	// transactionStatusResp, err := m.coreClient.CheckTransaction(orderId)
	
	return &usecase.PaymentGatewayResponse{
		OrderID:    orderId,
		Status:     "pending",
		PaymentURL: "",
		GatewayID:  "",
	}, nil
}

// Mock implementation for testing
type mockPaymentGateway struct {
	logger *zap.Logger
}

func NewMockPaymentGateway(logger *zap.Logger) usecase.PaymentGateway {
	return &mockPaymentGateway{
		logger: logger,
	}
}

func (m *mockPaymentGateway) CreateTopupTransaction(ctx context.Context, userID uuid.UUID, amount string, orderId string) (*usecase.PaymentGatewayResponse, error) {
	m.logger.Info("Mock: Creating top-up transaction", 
		zap.String("user_id", userID.String()),
		zap.String("amount", amount),
		zap.String("order_id", orderId))

	// Mock payment URL
	paymentURL := fmt.Sprintf("https://app.sandbox.midtrans.com/snap/v2/vtweb/%s", orderId)

	return &usecase.PaymentGatewayResponse{
		OrderID:    orderId,
		Status:     "pending",
		PaymentURL: paymentURL,
		GatewayID:  "mock-gateway-id-" + orderId,
	}, nil
}

func (m *mockPaymentGateway) GetTransactionStatus(ctx context.Context, orderId string) (*usecase.PaymentGatewayResponse, error) {
	m.logger.Info("Mock: Getting transaction status", 
		zap.String("order_id", orderId))

	return &usecase.PaymentGatewayResponse{
		OrderID:    orderId,
		Status:     "settlement",
		PaymentURL: "",
		GatewayID:  "mock-gateway-id-" + orderId,
	}, nil
}
