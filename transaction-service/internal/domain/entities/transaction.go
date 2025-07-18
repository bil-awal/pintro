package entities

import (
	"time"

	"github.com/google/uuid"
	"github.com/shopspring/decimal"
)

type Transaction struct {
	ID               uuid.UUID         `json:"id" db:"id"`
	UserID           uuid.UUID         `json:"user_id" db:"user_id"`
	Type             TransactionType   `json:"type" db:"type"`
	Amount           decimal.Decimal   `json:"amount" db:"amount"`
	Status           TransactionStatus `json:"status" db:"status"`
	Reference        string            `json:"reference" db:"reference"`
	PaymentGatewayID string            `json:"payment_gateway_id" db:"payment_gateway_id"`
	Description      string            `json:"description" db:"description"`
	Metadata         map[string]string `json:"metadata" db:"metadata"`
	ProcessedAt      *time.Time        `json:"processed_at" db:"processed_at"`
	CreatedAt        time.Time         `json:"created_at" db:"created_at"`
	UpdatedAt        time.Time         `json:"updated_at" db:"updated_at"`
}

type TransactionType string

const (
	TransactionTypeTopup    TransactionType = "topup"
	TransactionTypePayment  TransactionType = "payment"
	TransactionTypeTransfer TransactionType = "transfer"
)

type TransactionStatus string

const (
	TransactionStatusPending    TransactionStatus = "pending"
	TransactionStatusProcessing TransactionStatus = "processing"
	TransactionStatusCompleted  TransactionStatus = "completed"
	TransactionStatusFailed     TransactionStatus = "failed"
	TransactionStatusCancelled  TransactionStatus = "cancelled"
)

type TopupRequest struct {
	Amount        decimal.Decimal `json:"amount" validate:"required,gt=0"`
	PaymentMethod string          `json:"payment_method" validate:"required"`
}

type PaymentRequest struct {
	Amount      decimal.Decimal `json:"amount" validate:"required,gt=0"`
	Description string          `json:"description" validate:"required"`
	ToUserID    uuid.UUID       `json:"to_user_id" validate:"required"`
}

type TransactionResponse struct {
	ID         uuid.UUID         `json:"id"`
	Status     TransactionStatus `json:"status"`
	Amount     decimal.Decimal   `json:"amount"`
	PaymentURL string            `json:"payment_url,omitempty"`
	Reference  string            `json:"reference"`
	CreatedAt  time.Time         `json:"created_at"`
}

type TransactionHistoryResponse struct {
	ID          uuid.UUID         `json:"id"`
	Type        TransactionType   `json:"type"`
	Amount      decimal.Decimal   `json:"amount"`
	Status      TransactionStatus `json:"status"`
	Description string            `json:"description"`
	Reference   string            `json:"reference"`
	ProcessedAt *time.Time        `json:"processed_at"`
	CreatedAt   time.Time         `json:"created_at"`
}

type BalanceResponse struct {
	Balance decimal.Decimal `json:"balance"`
}

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

func (t *Transaction) MarkAsProcessing() {
	t.Status = TransactionStatusProcessing
	t.UpdatedAt = time.Now()
}

func (t *Transaction) MarkAsCompleted() {
	t.Status = TransactionStatusCompleted
	now := time.Now()
	t.ProcessedAt = &now
	t.UpdatedAt = now
}

func (t *Transaction) MarkAsFailed() {
	t.Status = TransactionStatusFailed
	t.UpdatedAt = time.Now()
}

func (t *Transaction) MarkAsCancelled() {
	t.Status = TransactionStatusCancelled
	t.UpdatedAt = time.Now()
}

func (t *Transaction) IsCompleted() bool {
	return t.Status == TransactionStatusCompleted
}

func (t *Transaction) IsPending() bool {
	return t.Status == TransactionStatusPending
}

func (t *Transaction) IsProcessing() bool {
	return t.Status == TransactionStatusProcessing
}

func (t *Transaction) CanBeProcessed() bool {
	return t.Status == TransactionStatusPending || t.Status == TransactionStatusProcessing
}

func generateReference() string {
	return "TXN-" + uuid.New().String()[:8]
}
