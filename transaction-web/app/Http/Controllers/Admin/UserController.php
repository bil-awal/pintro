<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    protected $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Show user profile page.
     */
    public function profile(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $adminUser = session('admin_user');

            if (!$adminToken || !$adminUser) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 401);
                }
                return redirect()->route('sysadmin.login');
            }

            // Get fresh profile data
            $profileData = $this->goService->getUserProfile($adminToken);
            if ($profileData) {
                session(['admin_user' => $profileData]);
                $adminUser = $profileData;
            }

            // Get balance data
            $balanceData = null;
            try {
                $balanceData = $this->goService->getUserBalance($adminToken);
            } catch (\Exception $e) {
                Log::warning('Failed to get balance for profile page', [
                    'error' => $e->getMessage(),
                    'admin_id' => $adminUser['id']
                ]);
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Profile data retrieved successfully',
                    'data' => [
                        'user' => $adminUser,
                        'balance' => $balanceData
                    ]
                ]);
            }

            return view('admin.user.profile', [
                'admin' => $adminUser,
                'balance' => $balanceData
            ]);

        } catch (\Exception $e) {
            Log::error('User profile error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve profile data',
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return view('admin.user.profile', [
                'admin' => session('admin_user'),
                'balance' => null,
                'error' => 'Failed to load profile data'
            ]);
        }
    }

    /**
     * Get current user balance.
     */
    public function balance(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $adminUser = session('admin_user');

            if (!$adminToken || !$adminUser) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }

            $balanceData = $this->goService->getUserBalance($adminToken);

            if ($balanceData) {
                return response()->json([
                    'success' => true,
                    'message' => 'Balance retrieved successfully',
                    'data' => $balanceData
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve balance'
            ], 500);

        } catch (\Exception $e) {
            Log::error('Balance retrieval error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve balance',
                'error' => ['code' => 500, 'message' => 'Service error']
            ], 500);
        }
    }

    /**
     * Show user transaction history.
     */
    public function transactions(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $adminUser = session('admin_user');

            if (!$adminToken || !$adminUser) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 401);
                }
                return redirect()->route('sysadmin.login');
            }

            // Get filters from request
            $filters = array_filter([
                'limit' => $request->get('limit', 20),
                'offset' => $request->get('offset', 0),
                'type' => $request->get('type'),
                'status' => $request->get('status'),
            ]);

            $transactions = $this->goService->getTransactions($filters, $adminToken);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User transactions retrieved successfully',
                    'data' => [
                        'transactions' => $transactions,
                        'total' => count($transactions),
                        'filters' => $filters
                    ]
                ]);
            }

            return view('admin.user.transactions', [
                'transactions' => $transactions,
                'filters' => $filters,
                'admin' => $adminUser
            ]);

        } catch (\Exception $e) {
            Log::error('User transactions error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve user transactions',
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return view('admin.user.transactions', [
                'transactions' => [],
                'filters' => [],
                'admin' => session('admin_user'),
                'error' => 'Failed to load transaction history'
            ]);
        }
    }

    /**
     * Show user dashboard with summary.
     */
    public function dashboard(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $adminUser = session('admin_user');

            if (!$adminToken || !$adminUser) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Unauthorized'
                    ], 401);
                }
                return redirect()->route('sysadmin.login');
            }

            // Get user balance
            $balanceData = null;
            try {
                $balanceData = $this->goService->getUserBalance($adminToken);
            } catch (\Exception $e) {
                Log::warning('Failed to get balance for user dashboard', [
                    'error' => $e->getMessage(),
                    'admin_id' => $adminUser['id']
                ]);
            }

            // Get recent transactions
            $recentTransactions = [];
            try {
                $recentTransactions = $this->goService->getTransactions(['limit' => 5], $adminToken);
            } catch (\Exception $e) {
                Log::warning('Failed to get recent transactions for user dashboard', [
                    'error' => $e->getMessage(),
                    'admin_id' => $adminUser['id']
                ]);
            }

            // Calculate stats
            $stats = [
                'total_transactions' => 0,
                'total_topups' => 0,
                'total_payments' => 0,
                'total_transfers' => 0,
                'total_spent' => 0,
                'total_received' => 0
            ];

            foreach ($recentTransactions as $transaction) {
                $stats['total_transactions']++;
                
                $type = $transaction['type'] ?? '';
                $amount = floatval($transaction['amount'] ?? 0);
                
                switch ($type) {
                    case 'topup':
                        $stats['total_topups']++;
                        $stats['total_received'] += $amount;
                        break;
                    case 'payment':
                        $stats['total_payments']++;
                        $stats['total_spent'] += $amount;
                        break;
                    case 'transfer':
                        $stats['total_transfers']++;
                        break;
                }
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'User dashboard data retrieved successfully',
                    'data' => [
                        'user' => $adminUser,
                        'balance' => $balanceData,
                        'recent_transactions' => $recentTransactions,
                        'stats' => $stats
                    ]
                ]);
            }

            return view('admin.user.dashboard', [
                'admin' => $adminUser,
                'balance' => $balanceData,
                'recent_transactions' => $recentTransactions,
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            Log::error('User dashboard error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve dashboard data',
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return view('admin.user.dashboard', [
                'admin' => session('admin_user'),
                'balance' => null,
                'recent_transactions' => [],
                'stats' => [],
                'error' => 'Failed to load dashboard data'
            ]);
        }
    }
}
