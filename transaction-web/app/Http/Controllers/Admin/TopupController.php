<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TopupController extends Controller
{
    protected $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Show top-up form.
     */
    public function index(Request $request)
    {
        $adminUser = session('admin_user');
        $adminToken = session('admin_token');

        if (!$adminUser || !$adminToken) {
            return redirect()->route('sysadmin.login')->with('error', 'Please login to continue.');
        }

        // Get user balance
        $balance = null;
        try {
            $balanceData = $this->goService->getUserBalance($adminToken);
            $balance = $balanceData['balance'] ?? 0;
        } catch (\Exception $e) {
            Log::warning('Failed to get user balance for topup page', [
                'error' => $e->getMessage(),
                'admin_id' => $adminUser['id']
            ]);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Top-up page data retrieved',
                'data' => [
                    'current_balance' => $balance,
                    'admin' => $adminUser
                ]
            ]);
        }

        return view('admin.topup.index', [
            'admin' => $adminUser,
            'current_balance' => $balance
        ]);
    }

    /**
     * Process top-up transaction.
     */
    public function store(Request $request)
    {
        try {

            Log::info("Top Up Store:" . $request);
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

            // Validate input
            $validator = Validator::make($request->all(), [
                'amount' => 'required|numeric|min:10000|max:10000000',
                'payment_method' => 'required|string|in:credit_card,bank_transfer,e_wallet',
            ], [
                'amount.min' => 'Minimum top-up amount is Rp 10,000',
                'amount.max' => 'Maximum top-up amount is Rp 10,000,000',
            ]);

            if ($validator->fails()) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Validation failed',
                        'errors' => $validator->errors()
                    ], 422);
                }
                return back()->withErrors($validator)->withInput();
            }

            $topupData = [
                'amount' => $request->input('amount'),
                'payment_method' => $request->input('payment_method'),
            ];

            $result = $this->goService->createTopup($topupData, $adminToken);

            if ($result) {
                Log::info('Top-up transaction created successfully', [
                    'transaction_id' => $result['id'] ?? null,
                    'amount' => $topupData['amount'],
                    'payment_method' => $topupData['payment_method'],
                    'admin_id' => $adminUser['id']
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Top-up transaction created successfully',
                        'data' => $result
                    ]);
                }

                // If there's a payment URL, redirect to it
                if (isset($result['payment_url']) && !empty($result['payment_url'])) {
                    return redirect($result['payment_url']);
                }

                return redirect()->route('sysadmin.transactions.index')
                    ->with('success', 'Top-up transaction created successfully! Reference: ' . ($result['reference'] ?? 'N/A'));
            }

            $errorMessage = 'Failed to create top-up transaction. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage)->withInput();

        } catch (\Exception $e) {
            Log::error('Top-up transaction error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'data' => $request->all()
            ]);

            $errorMessage = 'Top-up processing failed. Please try again.';

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return back()->with('error', $errorMessage)->withInput();
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
     * Show top-up history.
     */
    public function history(Request $request)
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

        $filters = [
            'type' => 'topup',
            'limit' => $request->get('limit', 20),
            'offset' => $request->get('offset', 0),
        ];

        $response = $this->goService->getTransactions($filters, $adminToken);
        $transactions = $response['transactions'] ?? [];

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Top-up history retrieved successfully',
                'data' => [
                    'transactions' => $transactions,
                    'total' => count($transactions)
                ]
            ]);
        }

        return view('admin.topup.history', [
            'transactions' => $transactions,
            'admin' => $adminUser
        ]);

    } catch (\Exception $e) {
        Log::error('Top-up history error', [
            'error' => $e->getMessage(),
            'admin_id' => session('admin_user.id')
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve top-up history',
                'error' => ['code' => 500, 'message' => 'Service error']
            ], 500);
        }

        return view('admin.topup.history', [
            'transactions' => [],
            'admin' => session('admin_user'),
            'error' => 'Failed to load top-up history'
        ]);
    }
}
}
