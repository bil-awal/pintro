<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TopupController extends Controller
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
        $this->middleware('web.auth');
    }

    /**
     * Show topup form.
     */
    public function index(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $balance = $this->goService->getAuthenticatedUserBalance($token);
            $userData = Session::get('user_data');

            // Get recent topup transactions
            $recentTopups = $this->goService->getUserTransactions($token, [
                'type' => 'topup',
                'limit' => 10,
            ]);

            return view('topup.index', [
                'user' => $userData,
                'current_balance' => $balance ?? 0,
                'recent_topups' => $recentTopups,
                'topup_amounts' => $this->getTopupAmounts(),
            ]);

        } catch (\Exception $e) {
            Log::error('Topup page load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return view('topup.index', [
                'user' => Session::get('user_data', []),
                'current_balance' => 0,
                'recent_topups' => [],
                'topup_amounts' => $this->getTopupAmounts(),
                'error' => 'Failed to load topup data.',
            ]);
        }
    }

    /**
     * Process topup request.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:10000|max:10000000', // Min 10k, Max 10M IDR
            'payment_method' => 'required|string|in:credit_card,bank_transfer,va_bca,va_bni,va_bri,gopay,shopeepay',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $token = Session::get('user_token');
            $userData = Session::get('user_data');

            // Prepare topup data
            $topupData = [
                'amount' => $request->input('amount'),
                'payment_method' => $request->input('payment_method'),
                'description' => 'Balance top-up via ' . $this->getPaymentMethodName($request->input('payment_method')),
                'customer_details' => [
                    'first_name' => $userData['name'] ?? 'User',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                ],
            ];

            // Create topup transaction via Go service
            $result = $this->goService->createAuthenticatedTopup($token, $topupData);

            if (!$result) {
                return back()
                    ->withErrors(['topup' => 'Failed to create topup transaction. Please try again.'])
                    ->withInput();
            }

            Log::info('Topup transaction created', [
                'user_id' => Session::get('user_id'),
                'amount' => $topupData['amount'],
                'payment_method' => $topupData['payment_method'],
                'transaction_id' => $result['transaction_id'] ?? null,
            ]);

            // Check if we have payment URL for redirect
            if (isset($result['payment_url']) && $result['payment_url']) {
                return redirect($result['payment_url']);
            }

            // If we have token, redirect to payment page
            if (isset($result['snap_token']) && $result['snap_token']) {
                return view('topup.payment', [
                    'snap_token' => $result['snap_token'],
                    'transaction_id' => $result['transaction_id'],
                    'amount' => $topupData['amount'],
                    'payment_method' => $topupData['payment_method'],
                ]);
            }

            // If successful but no payment URL, redirect to success page
            return redirect()->route('topup.success', ['transaction_id' => $result['transaction_id'] ?? 'unknown'])
                ->with('success', 'Topup transaction created successfully!');

        } catch (\Exception $e) {
            Log::error('Topup creation error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
                'amount' => $request->input('amount'),
            ]);

            return back()
                ->withErrors(['topup' => 'Topup transaction failed. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show topup success page.
     */
    public function success(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Get transaction details
            $transaction = $this->goService->getTransaction($transactionId);
            $balance = $this->goService->getAuthenticatedUserBalance($token);

            return view('topup.success', [
                'transaction' => $transaction,
                'transaction_id' => $transactionId,
                'current_balance' => $balance ?? 0,
                'user' => Session::get('user_data'),
            ]);

        } catch (\Exception $e) {
            Log::error('Topup success page error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return view('topup.success', [
                'transaction' => null,
                'transaction_id' => $transactionId,
                'current_balance' => 0,
                'user' => Session::get('user_data'),
                'error' => 'Failed to load transaction details.',
            ]);
        }
    }

    /**
     * Show topup failure page.
     */
    public function failure(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Get transaction details
            $transaction = $this->goService->getTransaction($transactionId);

            return view('topup.failure', [
                'transaction' => $transaction,
                'transaction_id' => $transactionId,
                'user' => Session::get('user_data'),
                'error_message' => $request->query('message', 'Transaction failed'),
            ]);

        } catch (\Exception $e) {
            Log::error('Topup failure page error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return view('topup.failure', [
                'transaction' => null,
                'transaction_id' => $transactionId,
                'user' => Session::get('user_data'),
                'error_message' => 'Transaction failed and unable to load details.',
            ]);
        }
    }

    /**
     * Check topup status via AJAX.
     */
    public function checkStatus(Request $request, string $transactionId)
    {
        try {
            $transaction = $this->goService->getTransaction($transactionId);

            if (!$transaction) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaction not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'transaction' => $transaction,
                'status' => $transaction['status'] ?? 'unknown',
                'amount' => $transaction['amount'] ?? 0,
            ]);

        } catch (\Exception $e) {
            Log::error('Topup status check error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to check transaction status',
            ], 500);
        }
    }

    /**
     * Cancel topup transaction.
     */
    public function cancel(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Here you would call a cancel method if available in Go service
            // For now, we'll just redirect to dashboard with a message
            
            Log::info('Topup transaction cancelled by user', [
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return redirect()->route('dashboard')
                ->with('info', 'Topup transaction has been cancelled.');

        } catch (\Exception $e) {
            Log::error('Topup cancellation error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return redirect()->route('dashboard')
                ->with('error', 'Failed to cancel transaction.');
        }
    }

    /**
     * Get predefined topup amounts.
     */
    private function getTopupAmounts(): array
    {
        return [
            50000 => 'Rp 50.000',
            100000 => 'Rp 100.000',
            200000 => 'Rp 200.000',
            500000 => 'Rp 500.000',
            1000000 => 'Rp 1.000.000',
            2000000 => 'Rp 2.000.000',
            5000000 => 'Rp 5.000.000',
        ];
    }

    /**
     * Get payment method display name.
     */
    private function getPaymentMethodName(string $method): string
    {
        $methods = [
            'credit_card' => 'Credit Card',
            'bank_transfer' => 'Bank Transfer',
            'va_bca' => 'BCA Virtual Account',
            'va_bni' => 'BNI Virtual Account',
            'va_bri' => 'BRI Virtual Account',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
        ];

        return $methods[$method] ?? $method;
    }
}
