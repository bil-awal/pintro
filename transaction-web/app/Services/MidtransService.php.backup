<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MidtransService
{
    private string $serverKey;
    private string $clientKey;
    private string $environment;
    private string $baseUrl;

    public function __construct()
    {
        $this->serverKey = config('services.midtrans.server_key', '');
        $this->clientKey = config('services.midtrans.client_key', '');
        $this->environment = config('services.midtrans.environment', 'sandbox');
        $this->baseUrl = $this->environment === 'production' 
            ? 'https://api.midtrans.com/v2' 
            : 'https://api.sandbox.midtrans.com/v2';
    }

    /**
     * Create Snap transaction token.
     */
    public function createSnapToken(array $transactionData): ?array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post('https://app.sandbox.midtrans.com/snap/v1/transactions', $transactionData);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Snap token created successfully', [
                    'order_id' => $transactionData['transaction_details']['order_id'],
                    'token' => $data['token'],
                ]);

                return $data;
            }

            Log::error('Failed to create Snap token', [
                'status' => $response->status(),
                'response' => $response->body(),
                'transaction_data' => $transactionData,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating Snap token', [
                'error' => $e->getMessage(),
                'transaction_data' => $transactionData,
            ]);

            return null;
        }
    }

    /**
     * Check transaction status.
     */
    public function checkTransactionStatus(string $orderId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->get("{$this->baseUrl}/{$orderId}/status");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to check transaction status', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error checking transaction status', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Approve transaction.
     */
    public function approveTransaction(string $orderId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/{$orderId}/approve");

            if ($response->successful()) {
                Log::info('Transaction approved via Midtrans', [
                    'order_id' => $orderId,
                ]);

                return $response->json();
            }

            Log::error('Failed to approve transaction via Midtrans', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error approving transaction via Midtrans', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Cancel transaction.
     */
    public function cancelTransaction(string $orderId): ?array
    {
        try {
            $response = Http::withBasicAuth($this->serverKey, '')
                ->timeout(30)
                ->post("{$this->baseUrl}/{$orderId}/cancel");

            if ($response->successful()) {
                Log::info('Transaction cancelled via Midtrans', [
                    'order_id' => $orderId,
                ]);

                return $response->json();
            }

            Log::error('Failed to cancel transaction via Midtrans', [
                'order_id' => $orderId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error cancelling transaction via Midtrans', [
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Verify webhook signature.
     */
    public function verifyWebhookSignature(array $payload): bool
    {
        $orderId = $payload['order_id'] ?? '';
        $statusCode = $payload['status_code'] ?? '';
        $grossAmount = $payload['gross_amount'] ?? '';
        $signatureKey = $payload['signature_key'] ?? '';

        $input = $orderId . $statusCode . $grossAmount . $this->serverKey;
        $expectedSignature = hash('sha512', $input);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Process webhook notification.
     */
    public function processWebhookNotification(array $payload): array
    {
        try {
            // Verify signature
            if (!$this->verifyWebhookSignature($payload)) {
                Log::warning('Invalid webhook signature', [
                    'payload' => $payload,
                ]);

                return [
                    'success' => false,
                    'error' => 'Invalid signature',
                ];
            }

            $orderId = $payload['order_id'];
            $transactionStatus = $payload['transaction_status'];
            $fraudStatus = $payload['fraud_status'] ?? '';

            // Determine final status
            $finalStatus = $this->determineFinalStatus($transactionStatus, $fraudStatus);

            Log::info('Webhook notification processed', [
                'order_id' => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status' => $fraudStatus,
                'final_status' => $finalStatus,
            ]);

            return [
                'success' => true,
                'order_id' => $orderId,
                'status' => $finalStatus,
                'raw_payload' => $payload,
            ];
        } catch (\Exception $e) {
            Log::error('Error processing webhook notification', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Determine final transaction status.
     */
    private function determineFinalStatus(string $transactionStatus, string $fraudStatus): string
    {
        if ($transactionStatus === 'capture') {
            return $fraudStatus === 'challenge' ? 'challenge' : 'success';
        }

        return match ($transactionStatus) {
            'settlement' => 'success',
            'pending' => 'pending',
            'deny', 'cancel', 'expire' => 'failed',
            'failure' => 'failed',
            default => 'unknown',
        };
    }

    /**
     * Get available payment methods.
     */
    public function getPaymentMethods(): array
    {
        return [
            'credit_card' => 'Credit Card',
            'bca_va' => 'BCA Virtual Account',
            'bni_va' => 'BNI Virtual Account',
            'bri_va' => 'BRI Virtual Account',
            'echannel' => 'Mandiri Bill Payment',
            'permata_va' => 'Permata Virtual Account',
            'other_va' => 'Other Virtual Account',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
            'indomaret' => 'Indomaret',
            'alfamart' => 'Alfamart',
            'akulaku' => 'Akulaku',
        ];
    }

    /**
     * Format transaction data for Snap.
     */
    public function formatSnapTransaction(
        string $orderId,
        float $amount,
        array $itemDetails,
        array $customerDetails,
        array $enabledPayments = null
    ): array {
        $transactionData = [
            'transaction_details' => [
                'order_id' => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'item_details' => $itemDetails,
            'customer_details' => $customerDetails,
            'enabled_payments' => $enabledPayments ?? [
                'credit_card', 'bca_va', 'bni_va', 'bri_va', 
                'echannel', 'gopay', 'shopeepay'
            ],
        ];

        // Add credit card configuration
        if (in_array('credit_card', $transactionData['enabled_payments'])) {
            $transactionData['credit_card'] = [
                'secure' => true,
                'bank' => 'bca',
                'installment' => [
                    'required' => false,
                    'terms' => [
                        'bni' => [3, 6, 12],
                        'mandiri' => [3, 6, 12],
                        'cimb' => [3],
                        'bca' => [3, 6, 12],
                        'offline' => [6, 12],
                    ],
                ],
            ];
        }

        return $transactionData;
    }

    /**
     * Get Snap script URL.
     */
    public function getSnapScriptUrl(): string
    {
        return $this->environment === 'production'
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }

    /**
     * Get client key for frontend.
     */
    public function getClientKey(): string
    {
        return $this->clientKey;
    }
}
