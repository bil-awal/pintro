<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
        $this->middleware('web.auth');
    }

    /**
     * Show user dashboard.
     */
    public function index(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $userData = Session::get('user_data');

            // Get user balance
            $balance = $this->goService->getAuthenticatedUserBalance($token);

            // Get recent transactions
            $recentTransactions = $this->goService->getUserTransactions($token, [
                'limit' => 5,
                'offset' => 0,
            ]);

            // Get user profile
            $profile = $this->goService->getUserProfile($token);

            // Calculate statistics for dashboard
            $allTransactions = $this->goService->getUserTransactions($token);
            $stats = $this->calculateUserStats($allTransactions);

            return view('dashboard.index', [
                'user' => $userData,
                'profile' => $profile,
                'balance' => $balance ?? 0,
                'recent_transactions' => $recentTransactions,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Dashboard load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return view('dashboard.index', [
                'user' => Session::get('user_data', []),
                'profile' => null,
                'balance' => 0,
                'recent_transactions' => [],
                'stats' => $this->getDefaultStats(),
                'error' => 'Failed to load dashboard data. Please try again.',
            ]);
        }
    }

    /**
     * Get user profile data.
     */
    public function profile(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $profile = $this->goService->getUserProfile($token);

            return view('dashboard.profile', [
                'profile' => $profile,
                'user' => Session::get('user_data'),
            ]);

        } catch (\Exception $e) {
            Log::error('Profile load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return view('dashboard.profile', [
                'profile' => null,
                'user' => Session::get('user_data'),
                'error' => 'Failed to load profile data.',
            ]);
        }
    }

    /**
     * Update user profile.
     */
    public function updateProfile(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
        ]);

        try {
            $token = Session::get('user_token');
            
            $profileData = [
                'name' => $request->input('name'),
                'phone' => $request->input('phone'),
                'address' => $request->input('address'),
            ];

            $result = $this->goService->updateUserProfile($token, $profileData);

            if ($result) {
                // Update session data
                $userData = Session::get('user_data');
                $userData['name'] = $request->input('name');
                Session::put('user_data', $userData);

                return back()->with('success', 'Profile updated successfully!');
            }

            return back()->withErrors(['update' => 'Failed to update profile. Please try again.']);

        } catch (\Exception $e) {
            Log::error('Profile update error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return back()->withErrors(['update' => 'Profile update failed. Please try again.']);
        }
    }

    /**
     * Get balance data as JSON for AJAX requests.
     */
    public function getBalance(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $balance = $this->goService->getAuthenticatedUserBalance($token);

            return response()->json([
                'success' => true,
                'balance' => $balance ?? 0,
                'formatted_balance' => 'Rp ' . number_format($balance ?? 0, 0, ',', '.'),
            ]);

        } catch (\Exception $e) {
            Log::error('Balance fetch error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch balance',
                'balance' => 0,
                'formatted_balance' => 'Rp 0',
            ]);
        }
    }

    /**
     * Get transaction statistics.
     */
    public function getStats(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $transactions = $this->goService->getUserTransactions($token);
            $stats = $this->calculateUserStats($transactions);

            return response()->json([
                'success' => true,
                'stats' => $stats,
            ]);

        } catch (\Exception $e) {
            Log::error('Stats fetch error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch statistics',
                'stats' => $this->getDefaultStats(),
            ]);
        }
    }

    /**
     * Calculate user statistics from transactions.
     */
    private function calculateUserStats(array $transactions): array
    {
        $stats = [
            'total_transactions' => count($transactions),
            'total_topup' => 0,
            'total_payment' => 0,
            'total_spent' => 0,
            'this_month_transactions' => 0,
            'this_month_spending' => 0,
            'pending_transactions' => 0,
            'completed_transactions' => 0,
            'failed_transactions' => 0,
        ];

        $currentMonth = now()->format('Y-m');

        foreach ($transactions as $transaction) {
            // Count by type
            if ($transaction['type'] === 'topup') {
                $stats['total_topup'] += $transaction['amount'] ?? 0;
            } elseif ($transaction['type'] === 'payment') {
                $stats['total_payment'] += $transaction['amount'] ?? 0;
                $stats['total_spent'] += $transaction['amount'] ?? 0;
            }

            // Count by status
            switch ($transaction['status'] ?? 'unknown') {
                case 'pending':
                case 'processing':
                    $stats['pending_transactions']++;
                    break;
                case 'completed':
                case 'success':
                    $stats['completed_transactions']++;
                    break;
                case 'failed':
                case 'cancelled':
                    $stats['failed_transactions']++;
                    break;
            }

            // This month transactions
            $transactionDate = isset($transaction['created_at']) 
                ? \Carbon\Carbon::parse($transaction['created_at'])->format('Y-m')
                : null;

            if ($transactionDate === $currentMonth) {
                $stats['this_month_transactions']++;
                if ($transaction['type'] === 'payment') {
                    $stats['this_month_spending'] += $transaction['amount'] ?? 0;
                }
            }
        }

        return $stats;
    }

    /**
     * Get default statistics structure.
     */
    private function getDefaultStats(): array
    {
        return [
            'total_transactions' => 0,
            'total_topup' => 0,
            'total_payment' => 0,
            'total_spent' => 0,
            'this_month_transactions' => 0,
            'this_month_spending' => 0,
            'pending_transactions' => 0,
            'completed_transactions' => 0,
            'failed_transactions' => 0,
        ];
    }
}
