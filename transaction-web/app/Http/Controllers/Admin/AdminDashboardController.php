<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AdminDashboardController extends Controller
{
    protected $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Show the admin dashboard with improved error handling.
     */
    public function index(Request $request)
    {
        try {
            $adminUser = session('admin_user');
            $adminToken = session('admin_token');
            
            Log::info('Loading admin dashboard', [
                'admin_id' => $adminUser['id'] ?? 'unknown',
                'has_token' => !is_null($adminToken),
                'session_authenticated' => session('admin_authenticated', false)
            ]);
            
            $transactions = $this->goService->getTransactions([
                'limit' => 10,
                'sort' => 'created_at',
                'order' => 'desc'
            ], $adminToken);

            $stats = $this->calculateStatsFromTransactions($transactions, $adminToken);
            $dashboardData = [
                'system_health' => $this->goService->getSystemHealth(),
                'recent_transactions' => $transactions,
                'stats' => $stats,
                'last_updated' => now()->toISOString(),
                'connectivity_test' => null,
                'admin' => $adminUser
            ];

            Log::info('Dashboard Data', $dashboardData);
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Dashboard data retrieved successfully',
                    'data' => $dashboardData
                ]);
            }

            return view('admin.dashboard.index', $dashboardData);

        } catch (\Exception $e) {
            Log::error('Admin dashboard error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'ip' => $request->ip(),
                'trace' => $e->getTraceAsString()
            ]);

            $fallbackData = [
                'error' => 'Some dashboard data could not be loaded',
                'admin' => session('admin_user'),
                'stats' => $this->getDefaultStats(),
                'system_health' => ['status' => 'unknown', 'error' => 'Unable to check system health'],
                'recent_transactions' => [],
                'connectivity_test' => null
            ];

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to load complete dashboard data',
                    'data' => $fallbackData,
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return view('admin.dashboard.index', $fallbackData);
        }
    }

    /**
     * Get dashboard statistics for API.
     */
    public function stats(Request $request)
    {
        try {
            // Get admin token from session
            $adminToken = session('admin_token');
            
            $stats = $this->getSystemStats($adminToken);
            
            return response()->json([
                'success' => true,
                'message' => 'Statistics retrieved successfully',
                'data' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard stats error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve statistics',
                'error' => [
                    'code' => 500,
                    'message' => 'Service error'
                ]
            ], 500);
        }
    }

    /**
     * Get recent transactions for dashboard.
     */
    public function recentTransactions(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $limit = $request->get('limit', 10);
            
            Log::info('Fetching recent transactions', [
                'limit' => $limit,
                'has_admin_token' => !is_null($adminToken),
                'admin_id' => session('admin_user.id')
            ]);

            // Try with admin token first, then without
            $transactions = [];
            
            if ($adminToken) {
                $transactions = $this->goService->getTransactions([
                    'limit' => $limit,
                    'sort' => 'created_at',
                    'order' => 'desc'
                ], $adminToken);
            }

            // If no transactions received and we had a token, try without token
            if (empty($transactions) && $adminToken) {
                Log::info('Retrying transaction fetch without admin token');
                $transactions = $this->goService->getTransactions([
                    'limit' => $limit,
                    'sort' => 'created_at',
                    'order' => 'desc'
                ]);
            }

            // If still no transactions, try alternative approach
            if (empty($transactions)) {
                Log::warning('No transactions received from Go service, using fallback');
                $transactions = $this->getFallbackTransactions();
            }

            Log::info('Recent transactions retrieved', [
                'count' => count($transactions),
                'admin_id' => session('admin_user.id')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Recent transactions retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'total' => count($transactions),
                    'source' => empty($transactions) ? 'fallback' : 'api'
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Recent transactions error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve recent transactions',
                'data' => [
                    'transactions' => $this->getFallbackTransactions(),
                    'total' => 0,
                    'source' => 'fallback'
                ],
                'error' => [
                    'code' => 500,
                    'message' => 'Service error'
                ]
            ], 500);
        }
    }

    /**
     * Get user activity data.
     */
    public function userActivity(Request $request)
    {
        try {
            $period = $request->get('period', '7d'); // 7d, 30d, 90d
            
            // This would typically fetch user activity from your analytics
            // For now, we'll return mock data
            $userActivity = $this->getUserActivityData($period);

            return response()->json([
                'success' => true,
                'message' => 'User activity retrieved successfully',
                'data' => $userActivity
            ]);

        } catch (\Exception $e) {
            Log::error('User activity error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve user activity',
                'error' => [
                    'code' => 500,
                    'message' => 'Service error'
                ]
            ], 500);
        }
    }

    /**
     * Test connectivity to Go service.
     */
    public function testConnectivity(Request $request)
    {
        try {
            $results = $this->goService->testConnectivity();
            
            return response()->json([
                'success' => true,
                'message' => 'Connectivity test completed',
                'data' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Connectivity test error', [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Connectivity test failed',
                'error' => [
                    'code' => 500,
                    'message' => $e->getMessage()
                ]
            ], 500);
        }
    }

    /**
     * Get dashboard data with graceful error handling.
     */
    private function getDashboardDataSafely(?string $adminToken = null): array
    {
        $data = [
            'system_health' => ['status' => 'unknown'],
            'recent_transactions' => [],
            'stats' => $this->getDefaultStats(),
            'last_updated' => now()->toISOString(),
            'connectivity_test' => null
        ];

        try {
            // Test system health first
            Log::info('Testing Go service health');
            $systemHealth = $this->goService->getSystemHealth();
            $data['system_health'] = $systemHealth;

            // If Go service is healthy, try to get transactions
            if ($systemHealth['status'] === 'healthy') {
                Log::info('Go service is healthy, fetching transactions');
                
                // Try with admin token first
                $transactions = [];
                if ($adminToken) {
                    $transactions = $this->goService->getTransactions([
                        'limit' => 10,
                        'sort' => 'created_at',
                        'order' => 'desc'
                    ], $adminToken);
                }

                // If no transactions with token, try without
                if (empty($transactions) && $adminToken) {
                    Log::info('Retrying without admin token');
                    $transactions = $this->goService->getTransactions([
                        'limit' => 10,
                        'sort' => 'created_at',
                        'order' => 'desc'
                    ]);
                }

                $data['recent_transactions'] = $transactions;
                
                // Calculate stats from retrieved transactions
                if (!empty($transactions)) {
                    $data['stats'] = $this->calculateStatsFromTransactions($transactions, $adminToken);
                }

                Log::info('Dashboard data loaded successfully', [
                    'transaction_count' => count($transactions),
                    'system_status' => $systemHealth['status']
                ]);
            } else {
                Log::warning('Go service is not healthy', [
                    'health_status' => $systemHealth
                ]);
                
                // Run connectivity test for debugging
                $data['connectivity_test'] = $this->goService->testConnectivity();
            }

        } catch (\Exception $e) {
            Log::error('Error getting dashboard data', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            $data['error'] = 'Some data could not be loaded: ' . $e->getMessage();
            $data['connectivity_test'] = $this->goService->testConnectivity();
        }

        return $data;
    }

    /**
     * Get system statistics.
     */
    private function getSystemStats(?string $adminToken = null)
    {
        try {
            // Get all transactions for calculations (pass admin token)
            $allTransactions = $this->goService->getTransactions([], $adminToken);
            
            $stats = [
                'total_transactions' => count($allTransactions),
                'pending_transactions' => 0,
                'completed_transactions' => 0,
                'failed_transactions' => 0,
                'total_amount' => 0,
                'total_fees' => 0,
                'transaction_types' => [
                    'topup' => 0,
                    'payment' => 0,
                    'transfer' => 0
                ],
                'daily_transactions' => 0,
                'weekly_transactions' => 0,
                'monthly_transactions' => 0
            ];

            $today = now()->startOfDay();
            $weekAgo = now()->subDays(7);
            $monthAgo = now()->subDays(30);

            foreach ($allTransactions as $transaction) {
                // Status counts
                $status = $transaction['status'] ?? 'unknown';
                if (isset($stats[$status . '_transactions'])) {
                    $stats[$status . '_transactions']++;
                }

                // Amount totals
                $stats['total_amount'] += floatval($transaction['amount'] ?? 0);
                $stats['total_fees'] += floatval($transaction['fee'] ?? 0);

                // Transaction type counts
                $type = $transaction['type'] ?? 'unknown';
                if (isset($stats['transaction_types'][$type])) {
                    $stats['transaction_types'][$type]++;
                }

                // Time-based counts
                $createdAt = \Carbon\Carbon::parse($transaction['created_at'] ?? now());
                
                if ($createdAt->gte($today)) {
                    $stats['daily_transactions']++;
                }
                
                if ($createdAt->gte($weekAgo)) {
                    $stats['weekly_transactions']++;
                }
                
                if ($createdAt->gte($monthAgo)) {
                    $stats['monthly_transactions']++;
                }
            }

            // Format amounts
            $stats['formatted_total_amount'] = 'Rp ' . number_format($stats['total_amount'], 0, ',', '.');
            $stats['formatted_total_fees'] = 'Rp ' . number_format($stats['total_fees'], 0, ',', '.');

            return $stats;

        } catch (\Exception $e) {
            Log::error('Error calculating system stats', [
                'error' => $e->getMessage()
            ]);

            return $this->getDefaultStats();
        }
    }

    /**
     * Calculate stats from actual transaction data.
     */
    private function calculateStatsFromTransactions(array $transactions, ?string $adminToken = null): array
    {
        $stats = [
            'total_transactions' => count($transactions),
            'pending_transactions' => 0,
            'completed_transactions' => 0,
            'failed_transactions' => 0,
            'total_amount' => 0.0,
            'total_fees' => 0.0,
            'transaction_types' => [
                'topup' => 0,
                'payment' => 0,
                'transfer' => 0
            ],
            'daily_transactions' => 0,
            'weekly_transactions' => 0,
            'monthly_transactions' => 0,
            'formatted_total_amount' => 'Rp 0',
            'formatted_total_fees' => 'Rp 0'
        ];

        $today = now()->startOfDay();
        $weekAgo = now()->subDays(7);
        $monthAgo = now()->subDays(30);

        foreach ($transactions as $transaction) {
            // Debugging log untuk memeriksa data
            Log::info('Processing transaction', [
                'id' => $transaction['id'] ?? 'unknown',
                'type' => $transaction['type'] ?? 'unknown',
                'status' => $transaction['status'] ?? 'unknown',
                'amount' => $transaction['amount'] ?? 'not set',
                'fee' => $transaction['fee'] ?? 'not set',
                'created_at' => $transaction['created_at'] ?? 'not set'
            ]);

            // Hitung status transaksi
            $status = strtolower($transaction['status'] ?? 'unknown');
            if ($status === 'pending') {
                $stats['pending_transactions']++;
            } elseif ($status === 'completed') {
                $stats['completed_transactions']++;
            } elseif ($status === 'failed') {
                $stats['failed_transactions']++;
            }

            // Hitung total amount
            $amount = floatval(preg_replace('/[^0-9.]/', '', $transaction['amount'] ?? '0'));
            $stats['total_amount'] += $amount;

            // Hitung total fees (jika ada)
            $fee = floatval(preg_replace('/[^0-9.]/', '', $transaction['fee'] ?? '0'));
            $stats['total_fees'] += $fee;

            // Hitung tipe transaksi
            $type = strtolower($transaction['type'] ?? 'unknown');
            if (isset($stats['transaction_types'][$type])) {
                $stats['transaction_types'][$type]++;
            }

            // Hitung transaksi berdasarkan waktu
            $createdAt = \Carbon\Carbon::parse($transaction['created_at'] ?? now());
            if ($createdAt->gte($today)) {
                $stats['daily_transactions']++;
            }
            if ($createdAt->gte($weekAgo)) {
                $stats['weekly_transactions']++;
            }
            if ($createdAt->gte($monthAgo)) {
                $stats['monthly_transactions']++;
            }
        }

        // Format total amount dan fees
        $stats['formatted_total_amount'] = 'Rp ' . number_format($stats['total_amount'], 0, ',', '.');
        $stats['formatted_total_fees'] = 'Rp ' . number_format($stats['total_fees'], 0, ',', '.');

        return $stats;
    }

    /**
     * Get fallback transaction data when API is unavailable.
     */
    private function getFallbackTransactions(): array
    {
        return [
            [
                'id' => 'fallback-1',
                'type' => 'topup',
                'amount' => 100000,
                'status' => 'completed',
                'reference' => 'FALLBACK-001',
                'created_at' => now()->subHours(2)->toISOString(),
                'description' => 'Sample transaction (API unavailable)'
            ],
            [
                'id' => 'fallback-2',
                'type' => 'payment',
                'amount' => 50000,
                'status' => 'pending',
                'reference' => 'FALLBACK-002',
                'created_at' => now()->subHours(1)->toISOString(),
                'description' => 'Sample transaction (API unavailable)'
            ]
        ];
    }

    /**
     * Get user activity data for charts.
     */
    private function getUserActivityData($period = '7d')
    {
        // This is mock data - in a real implementation, you would
        // fetch this from your analytics system or database
        
        $days = $period === '7d' ? 7 : ($period === '30d' ? 30 : 90);
        $data = [];
        
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $data[] = [
                'date' => $date->format('Y-m-d'),
                'formatted_date' => $date->format('M j'),
                'users' => rand(10, 100),
                'transactions' => rand(5, 50),
                'amount' => rand(1000000, 10000000) // Random amount in IDR
            ];
        }

        return [
            'period' => $period,
            'data' => $data,
            'summary' => [
                'total_users' => array_sum(array_column($data, 'users')),
                'total_transactions' => array_sum(array_column($data, 'transactions')),
                'total_amount' => array_sum(array_column($data, 'amount')),
                'average_daily_users' => round(array_sum(array_column($data, 'users')) / count($data)),
                'average_daily_transactions' => round(array_sum(array_column($data, 'transactions')) / count($data)),
                'average_daily_amount' => round(array_sum(array_column($data, 'amount')) / count($data))
            ]
        ];
    }

    /**
     * Get default statistics when API is unavailable.
     */
    private function getDefaultStats()
    {
        return [
            'total_transactions' => 0,
            'pending_transactions' => 0,
            'completed_transactions' => 0,
            'failed_transactions' => 0,
            'total_amount' => 0,
            'total_fees' => 0,
            'formatted_total_amount' => 'Rp 0',
            'formatted_total_fees' => 'Rp 0',
            'transaction_types' => [
                'topup' => 0,
                'payment' => 0,
                'transfer' => 0
            ],
            'daily_transactions' => 0,
            'weekly_transactions' => 0,
            'monthly_transactions' => 0
        ];
    }
}
