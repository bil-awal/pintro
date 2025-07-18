<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class TransactionService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = config('services.transaction_service.base_url', 'http://localhost:8080/api/v1');
        $this->timeout = config('services.transaction_service.timeout', 30);
    }

    /**
     * Register a new user
     */
    public function register(array $userData): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/auth/register", [
                    'email' => $userData['email'],
                    'password' => $userData['password'],
                    'name' => $userData['name'],
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('User registered successfully', [
                    'email' => $userData['email'],
                ]);

                return $data;
            }

            Log::error('Failed to register user', [
                'status' => $response->status(),
                'response' => $response->body(),
                'email' => $userData['email'],
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error registering user', [
                'error' => $e->getMessage(),
                'email' => $userData['email'],
            ]);

            return null;
        }
    }

    /**
     * Login user
     */
    public function login(string $email, string $password): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->post("{$this->baseUrl}/auth/login", [
                    'email' => $email,
                    'password' => $password,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('User logged in successfully', [
                    'email' => $email,
                ]);

                return $data;
            }

            Log::error('Failed to login user', [
                'status' => $response->status(),
                'response' => $response->body(),
                'email' => $email,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error logging in user', [
                'error' => $e->getMessage(),
                'email' => $email,
            ]);

            return null;
        }
    }

    /**
     * Get user profile
     */
    public function getUserProfile(string $token): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get("{$this->baseUrl}/user/profile");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get user profile', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting user profile', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get user balance
     */
    public function getUserBalance(string $token): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get("{$this->baseUrl}/user/balance");

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get user balance', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting user balance', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create top-up transaction
     */
    public function createTopup(string $token, array $topupData): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post("{$this->baseUrl}/transactions/topup", [
                    'amount' => $topupData['amount'],
                    'payment_method' => $topupData['payment_method'] ?? 'credit_card',
                    'customer_name' => $topupData['customer_name'] ?? '',
                    'customer_email' => $topupData['customer_email'] ?? '',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Top-up transaction created successfully', [
                    'amount' => $topupData['amount'],
                    'order_id' => $data['data']['order_id'] ?? 'unknown',
                ]);

                return $data;
            }

            Log::error('Failed to create top-up transaction', [
                'status' => $response->status(),
                'response' => $response->body(),
                'amount' => $topupData['amount'],
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating top-up transaction', [
                'error' => $e->getMessage(),
                'amount' => $topupData['amount'],
            ]);

            return null;
        }
    }

    /**
     * Create payment transaction
     */
    public function createPayment(string $token, array $paymentData): ?array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->post("{$this->baseUrl}/transactions/pay", [
                    'amount' => $paymentData['amount'],
                    'description' => $paymentData['description'] ?? '',
                    'payment_method' => $paymentData['payment_method'] ?? 'balance',
                    'recipient_id' => $paymentData['recipient_id'] ?? null,
                    'item_name' => $paymentData['item_name'] ?? '',
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                Log::info('Payment transaction created successfully', [
                    'amount' => $paymentData['amount'],
                    'order_id' => $data['data']['order_id'] ?? 'unknown',
                ]);

                return $data;
            }

            Log::error('Failed to create payment transaction', [
                'status' => $response->status(),
                'response' => $response->body(),
                'amount' => $paymentData['amount'],
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating payment transaction', [
                'error' => $e->getMessage(),
                'amount' => $paymentData['amount'],
            ]);

            return null;
        }
    }

    /**
     * Get transaction history
     */
    public function getTransactions(string $token, array $filters = []): ?array
    {
        try {
            $queryParams = [];
            
            if (!empty($filters['status'])) {
                $queryParams['status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $queryParams['type'] = $filters['type'];
            }
            
            if (!empty($filters['limit'])) {
                $queryParams['limit'] = $filters['limit'];
            }
            
            if (!empty($filters['offset'])) {
                $queryParams['offset'] = $filters['offset'];
            }

            $response = Http::timeout($this->timeout)
                ->withToken($token)
                ->get("{$this->baseUrl}/transactions", $queryParams);

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('Failed to get transactions', [
                'status' => $response->status(),
                'response' => $response->body(),
                'filters' => $filters,
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting transactions', [
                'error' => $e->getMessage(),
                'filters' => $filters,
            ]);

            return null;
        }
    }

    /**
     * Check if Go service is healthy
     */
    public function checkHealth(): bool
    {
        try {
            $cacheKey = 'transaction_service_health';
            
            // Check cache first to avoid too many health checks
            if (Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $response = Http::timeout(5)
                ->get(str_replace('/api/v1', '/health', $this->baseUrl));

            $isHealthy = $response->successful();
            
            // Cache result for 30 seconds
            Cache::put($cacheKey, $isHealthy, 30);
            
            return $isHealthy;
        } catch (\Exception $e) {
            Log::error('Error checking transaction service health', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Format error response from Go service
     */
    public function formatError(array $response): string
    {
        if (isset($response['error'])) {
            return $response['error'];
        }

        if (isset($response['message'])) {
            return $response['message'];
        }

        return 'Unknown error occurred';
    }

    /**
     * Get available payment methods
     */
    public function getPaymentMethods(): array
    {
        return [
            'balance' => 'Wallet Balance',
            'credit_card' => 'Credit Card',
            'bca_va' => 'BCA Virtual Account',
            'bni_va' => 'BNI Virtual Account',
            'bri_va' => 'BRI Virtual Account',
            'mandiri_va' => 'Mandiri Virtual Account',
            'permata_va' => 'Permata Virtual Account',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'shopeepay' => 'ShopeePay',
            'indomaret' => 'Indomaret',
            'alfamart' => 'Alfamart',
        ];
    }

    /**
     * Format currency amount
     */
    public function formatCurrency(float $amount, string $currency = 'IDR'): string
    {
        if ($currency === 'IDR') {
            return 'Rp ' . number_format($amount, 0, ',', '.');
        }

        return number_format($amount, 2) . ' ' . $currency;
    }

    /**
     * Validate transaction amount
     */
    public function validateAmount(float $amount): array
    {
        $errors = [];

        if ($amount <= 0) {
            $errors[] = 'Amount must be greater than 0';
        }

        if ($amount > 10000000) { // 10 million IDR
            $errors[] = 'Amount cannot exceed Rp 10,000,000';
        }

        if ($amount < 1000) { // 1000 IDR minimum
            $errors[] = 'Minimum amount is Rp 1,000';
        }

        return $errors;
    }

    /**
     * Get transaction status label
     */
    public function getStatusLabel(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed', 'success' => 'Completed',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
            'expired' => 'Expired',
            default => ucfirst($status),
        };
    }

    /**
     * Get transaction status color for UI
     */
    public function getStatusColor(string $status): string
    {
        return match (strtolower($status)) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed', 'success' => 'success',
            'failed' => 'danger',
            'cancelled' => 'secondary',
            'expired' => 'dark',
            default => 'secondary',
        };
    }

    /**
     * Get transaction type label
     */
    public function getTypeLabel(string $type): string
    {
        return match (strtolower($type)) {
            'topup' => 'Top-up',
            'payment' => 'Payment',
            'transfer' => 'Transfer',
            'refund' => 'Refund',
            'withdrawal' => 'Withdrawal',
            default => ucfirst($type),
        };
    }
}
