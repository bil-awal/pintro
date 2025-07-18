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

type TransactionHandler struct {
	transactionUseCase usecase.TransactionUseCase
	validator          *validator.Validate
	logger             *zap.Logger
}

func NewTransactionHandler(transactionUseCase usecase.TransactionUseCase, validator *validator.Validate, logger *zap.Logger) *TransactionHandler {
	return &TransactionHandler{
		transactionUseCase: transactionUseCase,
		validator:          validator,
		logger:             logger,
	}
}

func (h *TransactionHandler) Topup(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	var req entities.TopupRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind request", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Validation failed", zap.Error(err))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.transactionUseCase.TopupBalance(c.Request().Context(), userID, req)
	if err != nil {
		h.logger.Error("Topup failed", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusCreated, "Topup transaction created successfully", response)
}

func (h *TransactionHandler) Pay(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	var req entities.PaymentRequest
	if err := c.Bind(&req); err != nil {
		h.logger.Error("Failed to bind request", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid request format")
	}

	if err := h.validator.Struct(req); err != nil {
		h.logger.Error("Validation failed", zap.Error(err))
		return utils.ValidationErrorResponse(c, err)
	}

	response, err := h.transactionUseCase.ProcessPayment(c.Request().Context(), userID, req)
	if err != nil {
		h.logger.Error("Payment failed", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusCreated, "Payment processed successfully", response)
}

func (h *TransactionHandler) GetTransactions(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	// Parse pagination parameters
	limitStr := c.QueryParam("limit")
	offsetStr := c.QueryParam("offset")

	limit := 10 // default limit
	offset := 0 // default offset

	if limitStr != "" {
		if parsedLimit, err := strconv.Atoi(limitStr); err == nil && parsedLimit > 0 {
			limit = parsedLimit
		}
	}

	if offsetStr != "" {
		if parsedOffset, err := strconv.Atoi(offsetStr); err == nil && parsedOffset >= 0 {
			offset = parsedOffset
		}
	}

	// Limit maximum results per page
	if limit > 100 {
		limit = 100
	}

	transactions, err := h.transactionUseCase.GetTransactionHistory(c.Request().Context(), userID, limit, offset)
	if err != nil {
		h.logger.Error("Failed to get transactions", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusOK, "Transactions retrieved successfully", map[string]interface{}{
		"transactions": transactions,
		"pagination": map[string]interface{}{
			"limit":  limit,
			"offset": offset,
			"count":  len(transactions),
		},
	})
}

func (h *TransactionHandler) GetBalance(c echo.Context) error {
	userID, err := utils.GetUserIDFromContext(c)
	if err != nil {
		return utils.ErrorResponse(c, http.StatusUnauthorized, "Unauthorized")
	}

	balance, err := h.transactionUseCase.GetBalance(c.Request().Context(), userID)
	if err != nil {
		h.logger.Error("Failed to get balance", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusOK, "Balance retrieved successfully", balance)
}

func (h *TransactionHandler) HandleCallback(c echo.Context) error {
	var payload map[string]interface{}
	if err := c.Bind(&payload); err != nil {
		h.logger.Error("Failed to bind callback payload", zap.Error(err))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Invalid payload format")
	}

	// Extract order_id (reference) from payload
	orderID, ok := payload["order_id"].(string)
	if !ok {
		h.logger.Error("Missing order_id in callback payload")
		return utils.ErrorResponse(c, http.StatusBadRequest, "Missing order_id")
	}

	// Extract transaction status from payload
	transactionStatus, ok := payload["transaction_status"].(string)
	if !ok {
		h.logger.Error("Missing transaction_status in callback payload")
		return utils.ErrorResponse(c, http.StatusBadRequest, "Missing transaction_status")
	}

	// Map Midtrans status to our transaction status
	var status entities.TransactionStatus
	switch transactionStatus {
	case "settlement", "capture":
		status = entities.TransactionStatusCompleted
	case "cancel", "expire":
		status = entities.TransactionStatusCancelled
	case "deny", "failure":
		status = entities.TransactionStatusFailed
	default:
		h.logger.Warn("Unknown transaction status", zap.String("status", transactionStatus))
		return utils.ErrorResponse(c, http.StatusBadRequest, "Unknown transaction status")
	}

	// Process the callback
	err := h.transactionUseCase.ProcessCallback(c.Request().Context(), orderID, status)
	if err != nil {
		h.logger.Error("Failed to process callback", zap.Error(err))
		return utils.HandleError(c, err)
	}

	return utils.SuccessResponse(c, http.StatusOK, "Callback processed successfully", nil)
}
