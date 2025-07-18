<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentController extends Controller
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
        $this->middleware('web.auth');
    }

    /**
     * Show payment form.
     */
    public function index(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $balance = $this->goService->getAuthenticatedUserBalance($token);
            $userData = Session::get('user_data');

            // Get recent payment transactions
            $recentPayments = $this->goService->getUserTransactions($token, [
                'type' => 'payment',
                'limit' => 10,
            ]);

            return view('payment.index', [
                'user' => $userData,
                'current_balance' => $balance ?? 0,
                'recent_payments' => $recentPayments,
                'payment_categories' => $this->getPaymentCategories(),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment page load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return view('payment.index', [
                'user' => Session::get('user_data', []),
                'current_balance' => 0,
                'recent_payments' => [],
                'payment_categories' => $this->getPaymentCategories(),
                'error' => 'Failed to load payment data.',
            ]);
        }
    }

    /**
     * Process payment request.
     */
    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'amount' => 'required|numeric|min:1000|max:50000000', // Min 1k, Max 50M IDR
            'description' => 'required|string|max:255',
            'category' => 'required|string|in:food,transport,shopping,entertainment,bills,health,education,other',
            'recipient_type' => 'required|string|in:merchant,user',
            'recipient_identifier' => 'nullable|string|max:255', // Email, phone, or merchant ID
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $token = Session::get('user_token');
            $userData = Session::get('user_data');
            
            // Check balance first
            $currentBalance = $this->goService->getAuthenticatedUserBalance($token);
            $requestedAmount = $request->input('amount');

            if ($currentBalance === null) {
                return back()
                    ->withErrors(['payment' => 'Unable to verify balance. Please try again.'])
                    ->withInput();
            }

            if ($currentBalance < $requestedAmount) {
                return back()
                    ->withErrors(['amount' => 'Insufficient balance. Current balance: Rp ' . number_format($currentBalance, 0, ',', '.')])
                    ->withInput();
            }

            // Prepare payment data
            $paymentData = [
                'amount' => $requestedAmount,
                'description' => $request->input('description'),
                'category' => $request->input('category'),
                'recipient_type' => $request->input('recipient_type'),
                'recipient_identifier' => $request->input('recipient_identifier'),
                'customer_details' => [
                    'first_name' => $userData['name'] ?? 'User',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                ],
            ];

            // Create payment transaction via Go service
            $result = $this->goService->createAuthenticatedPayment($token, $paymentData);

            if (!$result) {
                return back()
                    ->withErrors(['payment' => 'Failed to create payment transaction. Please try again.'])
                    ->withInput();
            }

            Log::info('Payment transaction created', [
                'user_id' => Session::get('user_id'),
                'amount' => $paymentData['amount'],
                'description' => $paymentData['description'],
                'category' => $paymentData['category'],
                'transaction_id' => $result['transaction_id'] ?? null,
            ]);

            // Check if payment requires additional confirmation (e.g., OTP, PIN)
            if (isset($result['requires_confirmation']) && $result['requires_confirmation']) {
                return view('payment.confirm', [
                    'transaction' => $result,
                    'payment_data' => $paymentData,
                ]);
            }

            // If payment is processed successfully
            return redirect()->route('payment.success', ['transaction_id' => $result['transaction_id'] ?? 'unknown'])
                ->with('success', 'Payment transaction completed successfully!');

        } catch (\Exception $e) {
            Log::error('Payment creation error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
                'amount' => $request->input('amount'),
            ]);

            return back()
                ->withErrors(['payment' => 'Payment transaction failed. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Confirm payment with PIN or OTP.
     */
    public function confirm(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'transaction_id' => 'required|string',
            'confirmation_code' => 'required|string|min:4|max:10',
        ]);

        if ($validator->fails()) {
            return back()
                ->withErrors($validator)
                ->withInput();
        }

        try {
            $token = Session::get('user_token');
            
            // Here you would confirm the transaction with the Go service
            // For now, we'll simulate confirmation
            
            $transactionId = $request->input('transaction_id');
            
            Log::info('Payment confirmation attempted', [
                'user_id' => Session::get('user_id'),
                'transaction_id' => $transactionId,
            ]);

            // Simulate confirmation logic
            $confirmationCode = $request->input('confirmation_code');
            if (strlen($confirmationCode) >= 4) {
                return redirect()->route('payment.success', ['transaction_id' => $transactionId])
                    ->with('success', 'Payment confirmed and completed successfully!');
            }

            return back()
                ->withErrors(['confirmation_code' => 'Invalid confirmation code.'])
                ->withInput();

        } catch (\Exception $e) {
            Log::error('Payment confirmation error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
                'transaction_id' => $request->input('transaction_id'),
            ]);

            return back()
                ->withErrors(['confirmation' => 'Payment confirmation failed. Please try again.'])
                ->withInput();
        }
    }

    /**
     * Show payment success page.
     */
    public function success(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Get transaction details
            $transaction = $this->goService->getTransaction($transactionId);
            $balance = $this->goService->getAuthenticatedUserBalance($token);

            return view('payment.success', [
                'transaction' => $transaction,
                'transaction_id' => $transactionId,
                'current_balance' => $balance ?? 0,
                'user' => Session::get('user_data'),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment success page error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return view('payment.success', [
                'transaction' => null,
                'transaction_id' => $transactionId,
                'current_balance' => 0,
                'user' => Session::get('user_data'),
                'error' => 'Failed to load transaction details.',
            ]);
        }
    }

    /**
     * Show payment failure page.
     */
    public function failure(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Get transaction details
            $transaction = $this->goService->getTransaction($transactionId);

            return view('payment.failure', [
                'transaction' => $transaction,
                'transaction_id' => $transactionId,
                'user' => Session::get('user_data'),
                'error_message' => $request->query('message', 'Payment failed'),
            ]);

        } catch (\Exception $e) {
            Log::error('Payment failure page error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return view('payment.failure', [
                'transaction' => null,
                'transaction_id' => $transactionId,
                'user' => Session::get('user_data'),
                'error_message' => 'Payment failed and unable to load details.',
            ]);
        }
    }

    /**
     * Check payment status via AJAX.
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
            Log::error('Payment status check error', [
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
     * Get payment categories.
     */
    private function getPaymentCategories(): array
    {
        return [
            'food' => 'Food & Drinks',
            'transport' => 'Transportation',
            'shopping' => 'Shopping',
            'entertainment' => 'Entertainment',
            'bills' => 'Bills & Utilities',
            'health' => 'Health & Medical',
            'education' => 'Education',
            'other' => 'Other',
        ];
    }
}
