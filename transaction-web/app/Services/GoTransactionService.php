<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\Transaction;
use App\Models\User;

class GoTransactionService
{
    private string $baseUrl;
    private array $headers;

    public function __construct()
    {
        $this->baseUrl = config('services.go_transaction.url', 'http://localhost:8080');
        $this->headers = [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            'X-API-Key' => config('services.go_transaction.api_key', ''),
        ];
    }

    /**
     * Get all transactions from Go service.
     */
    public function getTransactions(array $filters = []): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/transactions', $filters);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::error('Failed to fetch transactions from Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error connecting to Go transaction service', [
                'error' => $e->getMessage(),
                'url' => $this->baseUrl . '/api/v1/transactions',
            ]);

            return [];
        }
    }

    /**
     * Get single transaction from Go service.
     */
    public function getTransaction(string $transactionId): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . "/api/v1/transactions/{$transactionId}");

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Failed to fetch transaction from Go service', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching transaction from Go service', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Approve transaction via Go service.
     */
    public function approveTransaction(string $transactionId, int $approvedBy): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . "/api/v1/transactions/{$transactionId}/approve", [
                    'approved_by' => $approvedBy,
                    'approved_at' => now()->toISOString(),
                ]);

            if ($response->successful()) {
                Log::info('Transaction approved successfully', [
                    'transaction_id' => $transactionId,
                    'approved_by' => $approvedBy,
                ]);

                return true;
            }

            Log::error('Failed to approve transaction via Go service', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error approving transaction via Go service', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reject transaction via Go service.
     */
    public function rejectTransaction(string $transactionId, int $rejectedBy, string $reason = ''): bool
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . "/api/v1/transactions/{$transactionId}/reject", [
                    'rejected_by' => $rejectedBy,
                    'rejected_at' => now()->toISOString(),
                    'reason' => $reason,
                ]);

            if ($response->successful()) {
                Log::info('Transaction rejected successfully', [
                    'transaction_id' => $transactionId,
                    'rejected_by' => $rejectedBy,
                    'reason' => $reason,
                ]);

                return true;
            }

            Log::error('Failed to reject transaction via Go service', [
                'transaction_id' => $transactionId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error rejecting transaction via Go service', [
                'transaction_id' => $transactionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get user balance from Go service.
     */
    public function getUserBalance(string $userId): ?float
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->get($this->baseUrl . "/api/v1/users/{$userId}/balance");

            if ($response->successful()) {
                return $response->json('data.balance');
            }

            Log::error('Failed to fetch user balance from Go service', [
                'user_id' => $userId,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching user balance from Go service', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create topup transaction via Go service.
     */
    public function createTopup(array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/topup', $data);

            if ($response->successful()) {
                Log::info('Topup transaction created successfully', [
                    'data' => $data,
                ]);

                return $response->json('data');
            }

            Log::error('Failed to create topup via Go service', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating topup via Go service', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create payment transaction via Go service.
     */
    public function createPayment(array $data): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/pay', $data);

            if ($response->successful()) {
                Log::info('Payment transaction created successfully', [
                    'data' => $data,
                ]);

                return $response->json('data');
            }

            Log::error('Failed to create payment via Go service', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating payment via Go service', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get system health from Go service.
     */
    public function getSystemHealth(): array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(10)
                ->get($this->baseUrl . '/health');

            if ($response->successful()) {
                return [
                    'status' => 'healthy',
                    'response_time' => $response->transferStats->getTransferTime() * 1000, // Convert to ms
                    'data' => $response->json(),
                ];
            }

            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'error' => 'HTTP ' . $response->status(),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'response_time' => null,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Sync transaction data from Go service.
     */
    public function syncTransactionData(): array
    {
        $syncedCount = 0;
        $errors = [];

        try {
            $goTransactions = $this->getTransactions();

            foreach ($goTransactions as $goTransaction) {
                try {
                    // Find or create user
                    $user = User::where('user_id', $goTransaction['user_id'])->first();
                    if (!$user) {
                        continue; // Skip if user doesn't exist in Laravel
                    }

                    // Update or create transaction
                    Transaction::updateOrCreate(
                        ['transaction_id' => $goTransaction['id']],
                        [
                            'reference' => $goTransaction['reference'] ?? '',
                            'user_id' => $user->id,
                            'from_account_id' => $this->getUserIdByGoId($goTransaction['from_account_id'] ?? null),
                            'to_account_id' => $this->getUserIdByGoId($goTransaction['to_account_id'] ?? null),
                            'type' => $goTransaction['type'] ?? 'payment',
                            'amount' => $goTransaction['amount'] ?? 0,
                            'fee' => $goTransaction['fee'] ?? 0,
                            'currency' => $goTransaction['currency'] ?? 'IDR',
                            'description' => $goTransaction['description'] ?? '',
                            'status' => $goTransaction['status'] ?? 'pending',
                            'payment_gateway_id' => $goTransaction['payment_gateway_id'] ?? null,
                            'payment_method' => $goTransaction['payment_method'] ?? null,
                            'metadata' => $goTransaction['metadata'] ?? null,
                            'processed_at' => $goTransaction['processed_at'] ? 
                                \Carbon\Carbon::parse($goTransaction['processed_at']) : null,
                        ]
                    );

                    $syncedCount++;
                } catch (\Exception $e) {
                    $errors[] = "Failed to sync transaction {$goTransaction['id']}: " . $e->getMessage();
                }
            }

            Log::info('Transaction sync completed', [
                'synced_count' => $syncedCount,
                'error_count' => count($errors),
            ]);

            return [
                'success' => true,
                'synced_count' => $syncedCount,
                'errors' => $errors,
            ];
        } catch (\Exception $e) {
            Log::error('Transaction sync failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'synced_count' => $syncedCount,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * Login user via Go service.
     */
    public function login(array $credentials): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/login', $credentials);

            if ($response->successful()) {
                $data = $response->json('data');
                
                Log::info('User login successful via Go service', [
                    'user_id' => $data['user']['id'] ?? null,
                    'email' => $credentials['email'],
                ]);

                return $data;
            }

            Log::error('Failed to login via Go service', [
                'email' => $credentials['email'],
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error logging in via Go service', [
                'email' => $credentials['email'],
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Register user via Go service.
     */
    public function register(array $userData): ?array
    {
        try {
            $response = Http::withHeaders($this->headers)
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/register', $userData);

            if ($response->successful()) {
                $data = $response->json('data');
                
                Log::info('User registration successful via Go service', [
                    'user_id' => $data['user']['id'] ?? null,
                    'email' => $userData['email'],
                ]);

                return $data;
            }

            Log::error('Failed to register via Go service', [
                'email' => $userData['email'],
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error registering via Go service', [
                'email' => $userData['email'],
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Logout user via Go service.
     */
    public function logout(string $token): bool
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/logout');

            if ($response->successful()) {
                Log::info('User logout successful via Go service');
                return true;
            }

            Log::error('Failed to logout via Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (\Exception $e) {
            Log::error('Error logging out via Go service', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Verify JWT token via Go service.
     */
    public function verifyToken(string $token): ?array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/verify-token');

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Failed to verify token via Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error verifying token via Go service', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get user profile via Go service.
     */
    public function getUserProfile(string $token): ?array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/profile');

            if ($response->successful()) {
                return $response->json('data');
            }

            Log::error('Failed to get user profile via Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error getting user profile via Go service', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Update user profile via Go service.
     */
    public function updateUserProfile(string $token, array $profileData): ?array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->put($this->baseUrl . '/api/v1/profile', $profileData);

            if ($response->successful()) {
                Log::info('User profile updated successfully via Go service');
                return $response->json('data');
            }

            Log::error('Failed to update user profile via Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error updating user profile via Go service', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get transaction history for authenticated user.
     */
    public function getUserTransactions(string $token, array $filters = []): array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/user/transactions', $filters);

            if ($response->successful()) {
                return $response->json('data', []);
            }

            Log::error('Failed to fetch user transactions from Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return [];
        } catch (\Exception $e) {
            Log::error('Error fetching user transactions from Go service', [
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    /**
     * Create authenticated topup transaction.
     */
    public function createAuthenticatedTopup(string $token, array $data): ?array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/topup', $data);

            if ($response->successful()) {
                Log::info('Authenticated topup transaction created successfully', [
                    'amount' => $data['amount'] ?? null,
                ]);

                return $response->json('data');
            }

            Log::error('Failed to create authenticated topup via Go service', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating authenticated topup via Go service', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Create authenticated payment transaction.
     */
    public function createAuthenticatedPayment(string $token, array $data): ?array
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->post($this->baseUrl . '/api/v1/pay', $data);

            if ($response->successful()) {
                Log::info('Authenticated payment transaction created successfully', [
                    'amount' => $data['amount'] ?? null,
                    'description' => $data['description'] ?? null,
                ]);

                return $response->json('data');
            }

            Log::error('Failed to create authenticated payment via Go service', [
                'data' => $data,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error creating authenticated payment via Go service', [
                'data' => $data,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Get authenticated user balance.
     */
    public function getAuthenticatedUserBalance(string $token): ?float
    {
        try {
            $response = Http::withHeaders(array_merge($this->headers, [
                'Authorization' => 'Bearer ' . $token,
            ]))
                ->timeout(30)
                ->get($this->baseUrl . '/api/v1/balance');

            if ($response->successful()) {
                return $response->json('data.balance');
            }

            Log::error('Failed to fetch authenticated user balance from Go service', [
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return null;
        } catch (\Exception $e) {
            Log::error('Error fetching authenticated user balance from Go service', [
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Helper method to get Laravel user ID from Go user ID.
     */
    private function getUserIdByGoId(?string $goUserId): ?int
    {
        if (!$goUserId) {
            return null;
        }

        $user = User::where('user_id', $goUserId)->first();
        return $user?->id;
    }
}
