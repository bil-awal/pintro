package entities

import (
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
)

// Transaction represents a financial transaction in the system
// @Description Financial transaction details
type Transaction struct {
	ID               uuid.UUID         `json:"id" db:"id" example:"550e8400-e29b-41d4-a716-446655440000"`                              // Transaction unique identifier
	UserID           uuid.UUID         `json:"user_id" db:"user_id" example:"550e8400-e29b-41d4-a716-446655440000"`                   // User ID who initiated the transaction
	Type             TransactionType   `json:"type" db:"type" example:"topup"`                                                          // Transaction type (topup, payment, transfer)
	Amount           decimal.Decimal   `json:"amount" db:"amount" example:"100.50" swaggertype:"string"`                               // Transaction amount
	Status           TransactionStatus `json:"status" db:"status" example:"pending"`                                                   // Transaction status
	Reference        string            `json:"reference" db:"reference" example:"TXN-12345678"`                                        // Unique transaction reference
	PaymentGatewayID string            `json:"payment_gateway_id" db:"payment_gateway_id" example:"midtrans-12345"`                   // Payment gateway transaction ID
	Description      string            `json:"description" db:"description" example:"Top up wallet balance"`                          // Transaction description
	Metadata         map[string]string `json:"metadata" db:"metadata" swaggertype:"object"`                                            // Additional transaction metadata
	ProcessedAt      *time.Time        `json:"processed_at" db:"processed_at" example:"2024-01-01T00:00:00Z"`                        // Transaction processing timestamp
	CreatedAt        time.Time         `json:"created_at" db:"created_at" example:"2024-01-01T00:00:00Z"`                            // Transaction creation timestamp
	UpdatedAt        time.Time         `json:"updated_at" db:"updated_at" example:"2024-01-01T00:00:00Z"`                            // Last update timestamp
}

// TransactionType represents the type of transaction
// @Description Transaction type enumeration
type TransactionType string

const (
	TransactionTypeTopup    TransactionType = "topup"    // Balance top-up transaction
	TransactionTypePayment  TransactionType = "payment"  // Payment transaction
	TransactionTypeTransfer TransactionType = "transfer" // Transfer transaction
)

// TransactionStatus represents the status of a transaction
// @Description Transaction status enumeration
type TransactionStatus string

const (
	TransactionStatusPending    TransactionStatus = "pending"    // Transaction is pending
	TransactionStatusProcessing TransactionStatus = "processing" // Transaction is being processed
	TransactionStatusCompleted  TransactionStatus = "completed"  // Transaction completed successfully
	TransactionStatusFailed     TransactionStatus = "failed"     // Transaction failed
	TransactionStatusCancelled  TransactionStatus = "cancelled"  // Transaction was cancelled
)

// TopupRequest represents balance top-up request payload
// @Description Balance top-up request
type TopupRequest struct {
	Amount        decimal.Decimal `json:"amount" validate:"required,gt=0" example:"100.50" swaggertype:"string"`    // Top-up amount (must be greater than 0)
	PaymentMethod string          `json:"payment_method" validate:"required" example:"credit_card"`                 // Payment method for top-up
}

// PaymentRequest represents payment request payload
// @Description Payment transaction request
type PaymentRequest struct {
	Amount      decimal.Decimal `json:"amount" validate:"required,gt=0" example:"50.25" swaggertype:"string"`                // Payment amount (must be greater than 0)
	Description string          `json:"description" validate:"required" example:"Payment for services"`                      // Payment description
	ToUserID    uuid.UUID       `json:"to_user_id" validate:"required" example:"550e8400-e29b-41d4-a716-446655440000"`      // Recipient user ID
}

// TransferRequest represents money transfer request payload
// @Description Money transfer request
type TransferRequest struct {
	Amount      decimal.Decimal `json:"amount" validate:"required,gt=0" example:"75.00" swaggertype:"string"`                // Transfer amount (must be greater than 0)
	ToUserID    uuid.UUID       `json:"to_user_id" validate:"required" example:"550e8400-e29b-41d4-a716-446655440000"`      // Recipient user ID
	Description string          `json:"description" validate:"required" example:"Transfer to friend"`                        // Transfer description
}

// TransactionResponse represents transaction operation response
// @Description Transaction creation/operation response
type TransactionResponse struct {
	ID         uuid.UUID         `json:"id" example:"550e8400-e29b-41d4-a716-446655440000"`              // Transaction ID
	Status     TransactionStatus `json:"status" example:"pending"`                                       // Transaction status
	Amount     decimal.Decimal   `json:"amount" example:"100.50" swaggertype:"string"`                   // Transaction amount
	PaymentURL string            `json:"payment_url,omitempty" example:"https://app.midtrans.com/snap/"`  // Payment URL (for topup transactions)
	Reference  string            `json:"reference" example:"TXN-12345678"`                               // Transaction reference
	CreatedAt  time.Time         `json:"created_at" example:"2024-01-01T00:00:00Z"`                     // Creation timestamp
}

// TransactionHistoryResponse represents transaction in history list
// @Description Transaction history item
type TransactionHistoryResponse struct {
	ID          uuid.UUID         `json:"id" example:"550e8400-e29b-41d4-a716-446655440000"`      // Transaction ID
	Type        TransactionType   `json:"type" example:"topup"`                                   // Transaction type
	Amount      decimal.Decimal   `json:"amount" example:"100.50" swaggertype:"string"`           // Transaction amount
	Status      TransactionStatus `json:"status" example:"completed"`                             // Transaction status
	Description string            `json:"description" example:"Top up wallet balance"`            // Transaction description
	Reference   string            `json:"reference" example:"TXN-12345678"`                       // Transaction reference
	ProcessedAt *time.Time        `json:"processed_at" example:"2024-01-01T00:00:00Z"`           // Processing timestamp
	CreatedAt   time.Time         `json:"created_at" example:"2024-01-01T00:00:00Z"`             // Creation timestamp
}

// BalanceResponse represents user balance response
// @Description User wallet balance
type BalanceResponse struct {
	Balance decimal.Decimal `json:"balance" example:"1000.50" swaggertype:"string"` // Current wallet balance
}

// PaginationResponse represents pagination metadata
// @Description Pagination information
type PaginationResponse struct {
	Limit  int `json:"limit" example:"10"`  // Number of items per page
	Offset int `json:"offset" example:"0"`  // Number of items to skip
	Count  int `json:"count" example:"5"`   // Number of items in current response
	Total  int `json:"total" example:"100"` // Total number of items available
}

// TransactionHistoryListResponse represents paginated transaction history
// @Description Paginated transaction history response
type TransactionHistoryListResponse struct {
	Transactions []TransactionHistoryResponse `json:"transactions"` // List of transactions
	Pagination   PaginationResponse           `json:"pagination"`   // Pagination metadata
}

// CallbackRequest represents payment gateway callback payload
// @Description Payment gateway callback request
type CallbackRequest struct {
	OrderID           string `json:"order_id" example:"TXN-12345678"`           // Transaction reference/order ID
	TransactionStatus string `json:"transaction_status" example:"settlement"`   // Payment gateway transaction status
	PaymentType       string `json:"payment_type" example:"credit_card"`        // Payment method used
	GrossAmount       string `json:"gross_amount" example:"100000.00"`          // Transaction amount in string format
	SignatureKey      string `json:"signature_key" example:"abc123..."`         // Security signature from payment gateway
}

// NewTransaction creates a new transaction entity
func NewTransaction(userID uuid.UUID, transactionType TransactionType, amount decimal.Decimal, description string) *Transaction {
	return &Transaction{
		ID:          uuid.New(),
		UserID:      userID,
		Type:        transactionType,
		Amount:      amount,
		Status:      TransactionStatusPending,
		Reference:   generateReference(),
		Description: description,
		Metadata:    make(map[string]string),
		CreatedAt:   time.Now(),
		UpdatedAt:   time.Now(),
	}
}

// MarkAsProcessing updates transaction status to processing
func (t *Transaction) MarkAsProcessing() {
	t.Status = TransactionStatusProcessing
	t.UpdatedAt = time.Now()
}

// MarkAsCompleted updates transaction status to completed
func (t *Transaction) MarkAsCompleted() {
	t.Status = TransactionStatusCompleted
	now := time.Now()
	t.ProcessedAt = &now
	t.UpdatedAt = now
}

// MarkAsFailed updates transaction status to failed
func (t *Transaction) MarkAsFailed() {
	t.Status = TransactionStatusFailed
	t.UpdatedAt = time.Now()
}

// MarkAsCancelled updates transaction status to cancelled
func (t *Transaction) MarkAsCancelled() {
	t.Status = TransactionStatusCancelled
	t.UpdatedAt = time.Now()
}

// IsCompleted checks if transaction is completed
func (t *Transaction) IsCompleted() bool {
	return t.Status == TransactionStatusCompleted
}

// IsPending checks if transaction is pending
func (t *Transaction) IsPending() bool {
	return t.Status == TransactionStatusPending
}

// IsProcessing checks if transaction is being processed
func (t *Transaction) IsProcessing() bool {
	return t.Status == TransactionStatusProcessing
}

// CanBeProcessed checks if transaction can be processed
func (t *Transaction) CanBeProcessed() bool {
	return t.Status == TransactionStatusPending || t.Status == TransactionStatusProcessing
}

// ToResponse converts Transaction to TransactionResponse
func (t *Transaction) ToResponse() TransactionResponse {
	return TransactionResponse{
		ID:        t.ID,
		Status:    t.Status,
		Amount:    t.Amount,
		Reference: t.Reference,
		CreatedAt: t.CreatedAt,
	}
}

// ToHistoryResponse converts Transaction to TransactionHistoryResponse
func (t *Transaction) ToHistoryResponse() TransactionHistoryResponse {
	return TransactionHistoryResponse{
		ID:          t.ID,
		Type:        t.Type,
		Amount:      t.Amount,
		Status:      t.Status,
		Description: t.Description,
		Reference:   t.Reference,
		ProcessedAt: t.ProcessedAt,
		CreatedAt:   t.CreatedAt,
	}
}

// generateReference generates a unique transaction reference
func generateReference() string {
	return "TXN-" + uuid.New().String()[:8]
}
