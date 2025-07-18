<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\PaymentCallback;
use App\Services\MidtransService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    private MidtransService $midtransService;

    public function __construct(MidtransService $midtransService)
    {
        $this->midtransService = $midtransService;
    }

    /**
     * Handle Midtrans payment notification webhook.
     */
    public function midtransNotification(Request $request): JsonResponse
    {
        try {
            Log::info('Received Midtrans webhook notification', [
                'payload' => $request->all(),
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            // Validate required fields
            $validator = Validator::make($request->all(), [
                'order_id' => 'required|string',
                'transaction_status' => 'required|string',
                'status_code' => 'required|string',
                'gross_amount' => 'required|string',
                'signature_key' => 'required|string',
            ]);

            if ($validator->fails()) {
                Log::warning('Invalid webhook payload', [
                    'errors' => $validator->errors(),
                    'payload' => $request->all(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payload format',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Process the webhook notification
            $result = $this->midtransService->processWebhookNotification($request->all());

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => $result['error'],
                ], 400);
            }

            // Store the callback record
            $callback = PaymentCallback::create([
                'transaction_id' => $result['order_id'],
                'gateway_transaction_id' => $request->input('transaction_id', $result['order_id']),
                'gateway_status' => $result['status'],
                'raw_payload' => $request->all(),
                'signature' => $request->input('signature_key'),
                'verified' => true,
                'received_at' => now(),
            ]);

            // Update the related transaction if it exists
            $transaction = Transaction::where('transaction_id', $result['order_id'])->first();
            
            if ($transaction) {
                $this->updateTransactionStatus($transaction, $result['status']);
                $callback->markAsProcessed();
            }

            Log::info('Webhook processed successfully', [
                'order_id' => $result['order_id'],
                'status' => $result['status'],
                'callback_id' => $callback->id,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'data' => [
                    'order_id' => $result['order_id'],
                    'status' => $result['status'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle Go service webhook notifications.
     */
    public function goServiceNotification(Request $request): JsonResponse
    {
        try {
            Log::info('Received Go service webhook notification', [
                'payload' => $request->all(),
                'ip' => $request->ip(),
            ]);

            // Validate API key
            $apiKey = $request->header('X-API-Key');
            if ($apiKey !== config('services.go_transaction.api_key')) {
                Log::warning('Invalid API key in Go service webhook', [
                    'provided_key' => $apiKey,
                    'ip' => $request->ip(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized',
                ], 401);
            }

            // Validate payload
            $validator = Validator::make($request->all(), [
                'transaction_id' => 'required|string',
                'status' => 'required|string',
                'user_id' => 'sometimes|string',
                'amount' => 'sometimes|numeric',
                'timestamp' => 'required|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid payload format',
                    'errors' => $validator->errors(),
                ], 400);
            }

            // Find and update transaction
            $transaction = Transaction::where('transaction_id', $request->input('transaction_id'))->first();

            if (!$transaction) {
                Log::warning('Transaction not found for Go service webhook', [
                    'transaction_id' => $request->input('transaction_id'),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            // Update transaction status
            $newStatus = $this->mapGoServiceStatus($request->input('status'));
            $transaction->update([
                'status' => $newStatus,
                'processed_at' => now(),
            ]);

            Log::info('Go service webhook processed successfully', [
                'transaction_id' => $transaction->transaction_id,
                'old_status' => $transaction->getOriginal('status'),
                'new_status' => $newStatus,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Webhook processed successfully',
                'data' => [
                    'transaction_id' => $transaction->transaction_id,
                    'status' => $newStatus,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Go service webhook processing failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'payload' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Webhook processing failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Health check endpoint.
     */
    public function healthCheck(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
        ]);
    }

    /**
     * Update transaction status based on payment gateway response.
     */
    private function updateTransactionStatus(Transaction $transaction, string $status): void
    {
        $newStatus = match ($status) {
            'success' => 'completed',
            'pending' => 'processing',
            'failed' => 'failed',
            'challenge' => 'processing',
            default => $transaction->status,
        };

        if ($newStatus !== $transaction->status) {
            $transaction->update([
                'status' => $newStatus,
                'processed_at' => now(),
            ]);

            Log::info('Transaction status updated via webhook', [
                'transaction_id' => $transaction->transaction_id,
                'old_status' => $transaction->getOriginal('status'),
                'new_status' => $newStatus,
                'gateway_status' => $status,
            ]);
        }
    }

    /**
     * Map Go service status to transaction status.
     */
    private function mapGoServiceStatus(string $goStatus): string
    {
        return match ($goStatus) {
            'approved', 'completed', 'success' => 'completed',
            'rejected', 'failed' => 'failed',
            'processing' => 'processing',
            'cancelled' => 'cancelled',
            default => 'pending',
        };
    }
}
