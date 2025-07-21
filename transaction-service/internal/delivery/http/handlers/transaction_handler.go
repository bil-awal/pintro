package handlers

import (
	"net/http"
	"strconv"

	"github.com/go-playground/validator/v10"
	"github.com/labstack/echo/v4"
	"go-transaction-service/internal/domain/entities"
	"go-transaction-service/internal/usecase"
	"go-transaction-service/pkg/utils"
	"go.uber.org/zap"
)

// TransactionHandler handles transaction-related HTTP requests
type TransactionHandler struct {
	transactionUseCase usecase.TransactionUseCase
	validator          *validator.Validate
	logger             *zap.Logger
}

// NewTransactionHandler creates a new transaction handler
func NewTransactionHandler(transactionUseCase usecase.TransactionUseCase, validator *validator.Validate, logger *zap.Logger) *TransactionHandler {
	return &TransactionHandler{
		transactionUseCase: transactionUseCase,
		validator:          validator,
		logger:             logger,
	}
}

// Topup handles balance top-up requests
// @Summary Top up user balance
// @Description Add money to user's wallet balance through payment gateway
// @Tags Transactions
// @Accept json
// @Produce json
// @Security BearerAuth
// @Param request body entities.TopupRequest true "Top-up request details"
// @Success 201 {object} entities.APIResponse{data=entities.TransactionResponse} "Topup transaction created successfully"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid input format"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 422 {object} entities.APIResponse{data=[]entities.ValidationError} "Validation failed"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /transactions/topup [post]
func (h *TransactionHandler) Topup(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for topup", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	var req entities.TopupRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind topup request", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Topup validation failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.transactionUseCase.TopupBalance(c.Request().Context(), userID, req)
	if err != nil {
		h.logger.Error("Topup failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()),
			zap.String("amount", req.Amount.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Info("Topup transaction created successfully", 
		zap.String("user_id", userID.String()),
		zap.String("transaction_id", response.ID.String()),
		zap.String("amount", response.Amount.String()))

	return utils.SuccessResponse(c, http.StatusCreated, "Topup transaction created successfully", response)
}

// Pay handles payment requests
// @Summary Process payment
// @Description Process payment from user's wallet balance to another user
// @Tags Transactions
// @Accept json
// @Produce json
// @Security BearerAuth
// @Param request body entities.PaymentRequest true "Payment request details"
// @Success 201 {object} entities.APIResponse{data=entities.TransactionResponse} "Payment processed successfully"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid input format"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 422 {object} entities.APIResponse{data=[]entities.ValidationError} "Validation failed"
// @Failure 402 {object} entities.APIResponse{error=entities.ErrorInfo} "Insufficient balance"
// @Failure 404 {object} entities.APIResponse{error=entities.ErrorInfo} "Recipient user not found"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /transactions/pay [post]
func (h *TransactionHandler) Pay(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for payment", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	var req entities.PaymentRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind payment request", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Payment validation failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.transactionUseCase.ProcessPayment(c.Request().Context(), userID, req)
	if err != nil {
		h.logger.Error("Payment failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()),
			zap.String("to_user_id", req.ToUserID.String()),
			zap.String("amount", req.Amount.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Info("Payment processed successfully", 
		zap.String("user_id", userID.String()),
		zap.String("transaction_id", response.ID.String()),
		zap.String("to_user_id", req.ToUserID.String()),
		zap.String("amount", response.Amount.String()))

	return utils.SuccessResponse(c, http.StatusCreated, "Payment processed successfully", response)
}

// Transfer handles money transfer requests
// @Summary Transfer money
// @Description Transfer money from user's wallet to another user's wallet
// @Tags Transactions
// @Accept json
// @Produce json
// @Security BearerAuth
// @Param request body entities.TransferRequest true "Transfer request details"
// @Success 201 {object} entities.APIResponse{data=entities.TransactionResponse} "Transfer processed successfully"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid input format"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 422 {object} entities.APIResponse{data=[]entities.ValidationError} "Validation failed"
// @Failure 402 {object} entities.APIResponse{error=entities.ErrorInfo} "Insufficient balance"
// @Failure 404 {object} entities.APIResponse{error=entities.ErrorInfo} "Recipient user not found"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /transactions/transfer [post]
func (h *TransactionHandler) Transfer(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for transfer", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	var req entities.TransferRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind transfer request", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Transfer validation failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ValidationErrorResponse(c, err)
	}

	// Convert to PaymentRequest for processing
	paymentReq := entities.PaymentRequest{
		Amount:      req.Amount,
		Description: req.Description,
		ToUserID:    req.ToUserID,
	}

	response, err := h.transactionUseCase.ProcessPayment(c.Request().Context(), userID, paymentReq)
	if err != nil {
		h.logger.Error("Transfer failed", 
			zap.Error(err),
			zap.String("user_id", userID.String()),
			zap.String("to_user_id", req.ToUserID.String()),
			zap.String("amount", req.Amount.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Info("Transfer processed successfully", 
		zap.String("user_id", userID.String()),
		zap.String("transaction_id", response.ID.String()),
		zap.String("to_user_id", req.ToUserID.String()),
		zap.String("amount", response.Amount.String()))

	return utils.SuccessResponse(c, http.StatusCreated, "Transfer processed successfully", response)
}

// GetTransactions retrieves user transaction history
// @Summary Get transaction history
// @Description Retrieve paginated list of user's transaction history
// @Tags Transactions
// @Accept json
// @Produce json
// @Security BearerAuth
// @Param limit query int false "Number of transactions per page (max 100)" default(10)
// @Param offset query int false "Number of transactions to skip" default(0)
// @Param type query string false "Filter by transaction type (topup, payment, transfer)"
// @Param status query string false "Filter by transaction status (pending, completed, failed, cancelled)"
// @Success 200 {object} entities.APIResponse{data=entities.TransactionHistoryListResponse} "Transactions retrieved successfully"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid query parameters"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /transactions [get]
func (h *TransactionHandler) GetTransactions(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for transaction history", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	// Parse pagination parameters
	limit, offset, err := h.parsePaginationParams(c)
	if err != nil {
		h.logger.Error("Invalid pagination parameters", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid pagination parameters")
	}

	transactions, err := h.transactionUseCase.GetTransactionHistory(c.Request().Context(), userID, limit, offset)
	if err != nil {
		h.logger.Error("Failed to get transactions", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.HandleError(c, err)
	}

	// Convert to history response format
	var historyResponses []entities.TransactionHistoryResponse
	for _, tx := range transactions {
		historyResponses = append(historyResponses, *tx)
	}

	response := entities.TransactionHistoryListResponse{
		Transactions: historyResponses,
		Pagination: entities.PaginationResponse{
			Limit:  limit,
			Offset: offset,
			Count:  len(historyResponses),
		},
	}

	h.logger.Debug("Transaction history retrieved", 
		zap.String("user_id", userID.String()),
		zap.Int("count", len(historyResponses)))

	return utils.SuccessResponse(c, http.StatusOK, "Transactions retrieved successfully", response)
}

// GetBalance retrieves user's current balance
// @Summary Get user balance
// @Description Retrieve current wallet balance of the authenticated user
// @Tags User
// @Accept json
// @Produce json
// @Security BearerAuth
// @Success 200 {object} entities.APIResponse{data=entities.BalanceResponse} "Balance retrieved successfully"
// @Failure 401 {object} entities.APIResponse{error=entities.ErrorInfo} "Unauthorized - invalid or missing token"
// @Failure 404 {object} entities.APIResponse{error=entities.ErrorInfo} "User not found"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /user/balance [get]
func (h *TransactionHandler) GetBalance(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		h.logger.Error("Failed to get user ID from context for balance check", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	balance, err := h.transactionUseCase.GetBalance(c.Request().Context(), userID)
	if err != nil {
		h.logger.Error("Failed to get balance", 
			zap.Error(err),
			zap.String("user_id", userID.String()))
		return utils.HandleError(c, err)
	}

	h.logger.Debug("Balance retrieved", 
		zap.String("user_id", userID.String()),
		zap.String("balance", balance.Balance.String()))

	return utils.SuccessResponse(c, http.StatusOK, "Balance retrieved successfully", balance)
}

// HandleCallback handles payment gateway callbacks
// @Summary Handle payment callback
// @Description Process payment gateway callbacks for transaction status updates
// @Tags Webhooks
// @Accept json
// @Produce json
// @Param request body entities.CallbackRequest true "Payment gateway callback payload"
// @Success 200 {object} entities.APIResponse "Callback processed successfully"
// @Failure 400 {object} entities.APIResponse{error=entities.ErrorInfo} "Bad request - invalid payload format"
// @Failure 404 {object} entities.APIResponse{error=entities.ErrorInfo} "Transaction not found"
// @Failure 500 {object} entities.APIResponse{error=entities.ErrorInfo} "Internal server error"
// @Router /webhook/payment/callback [post]
func (h *TransactionHandler) HandleCallback(c echo.Context) error {
	var payload map[string]interface{}
	if err := c.Bind(&payload); err != nil {
		h.logger.Error("Failed to bind callback payload", 
			zap.Error(err),
			zap.String("remote_addr", c.RealIP()))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid payload format")
	}

	// Extract order_id (reference) from payload
	orderID, ok := payload["order_id"].(string)
	if !ok {
		h.logger.Error("Missing order_id in callback payload", 
			zap.Any("payload", payload))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Missing order_id")
	}

	// Extract transaction status from payload
	transactionStatus, ok := payload["transaction_status"].(string)
	if !ok {
		h.logger.Error("Missing transaction_status in callback payload", 
			zap.String("order_id", orderID))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Missing transaction_status")
	}

	// Map Midtrans status to our transaction status
	status := h.mapPaymentGatewayStatus(transactionStatus)
	if status == "" {
		h.logger.Warn("Unknown transaction status in callback", 
			zap.String("status", transactionStatus),
			zap.String("order_id", orderID))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Unknown transaction status")
	}

	// Process the callback
	err := h.transactionUseCase.ProcessCallback(c.Request().Context(), orderID, status)
	if err != nil {
		h.logger.Error("Failed to process callback", 
			zap.Error(err),
			zap.String("order_id", orderID),
			zap.String("status", string(status)))
		return utils.HandleError(c, err)
	}

	h.logger.Info("Callback processed successfully", 
		zap.String("order_id", orderID),
		zap.String("status", string(status)))

	return utils.SuccessResponse(c, http.StatusOK, "Callback processed successfully", nil)
}

// parsePaginationParams extracts and validates pagination parameters from query
func (h *TransactionHandler) parsePaginationParams(c echo.Context) (limit, offset int, err error) {
	// Parse pagination parameters
	limitStr := c.QueryParam("limit")
	offsetStr := c.QueryParam("offset")

	limit = 10 // default limit
	offset = 0 // default offset

	if limitStr != "" {
		if parsedLimit, parseErr := strconv.Atoi(limitStr); parseErr == nil && parsedLimit > 0 {
			limit = parsedLimit
		} else if parseErr != nil {
			return 0, 0, parseErr
		}
	}

	if offsetStr != "" {
		if parsedOffset, parseErr := strconv.Atoi(offsetStr); parseErr == nil && parsedOffset >= 0 {
			offset = parsedOffset
		} else if parseErr != nil {
			return 0, 0, parseErr
		}
	}

	// Limit maximum results per page
	if limit > 100 {
		limit = 100
	}

	return limit, offset, nil
}

// mapPaymentGatewayStatus maps payment gateway status to internal transaction status
func (h *TransactionHandler) mapPaymentGatewayStatus(gatewayStatus string) entities.TransactionStatus {
	switch gatewayStatus {
	case "settlement", "capture":
		return entities.TransactionStatusCompleted
	case "cancel", "expire":
		return entities.TransactionStatusCancelled
	case "deny", "failure":
		return entities.TransactionStatusFailed
	case "pending":
		return entities.TransactionStatusPending
	default:
		return ""
	}
}
