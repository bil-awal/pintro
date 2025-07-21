<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    protected $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
    }

    /**
     * Display transaction list page.
     */
    public function index(Request $request)
    {
        try {
            $adminToken = session('admin_token');
            $adminUser = session('admin_user');

            if (!$adminToken || !$adminUser) {
                return redirect()->route('sysadmin.login')->with('error', 'Please login to continue.');
            }

            // Get filters from request
            $filters = array_filter([
                'limit' => $request->get('limit', 20),
                'offset' => $request->get('offset', 0),
                'type' => $request->get('type'),
                'status' => $request->get('status'),
            ]);

            // Get transactions from Go service
            $response = $this->goService->getTransactions($filters, $adminToken);
            $transactions = $response['transactions'] ?? [];

            Log::info('Transactions Data', [
                'data' => $transactions
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transactions retrieved successfully',
                    'data' => [
                        'transactions' => $transactions,
                        'total' => count($transactions),
                        'filters' => $filters
                    ]
                ]);
            }

            Log::info('Transactions Data', $transactions);

            return view('admin.transactions.index', [
                'transactions' => $transactions,
                'filters' => $filters,
                'admin' => $adminUser
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction list error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id')
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve transactions',
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return view('admin.transactions.index', [
                'transactions' => [],
                'filters' => [],
                'admin' => session('admin_user'),
                'error' => 'Failed to load transactions'
            ]);
        }
    }

    /**
     * Show transaction details.
     */
    public function show(Request $request, string $id)
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

            // Get all transactions and find the specific one
            $response = $this->goService->getTransactions([], $adminToken);
            $allTransactions = $response['transactions'] ?? [];
            $transaction = collect($allTransactions)->firstWhere('id', $id);

            if (!$transaction) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Transaction not found'
                    ], 404);
                }
                return redirect()->route('sysadmin.transactions.index')->with('error', 'Transaction not found');
            }

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaction retrieved successfully',
                    'data' => $transaction
                ]);
            }

            return view('admin.transactions.show', [
                'transaction' => $transaction,
                'admin' => $adminUser
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction detail error', [
                'error' => $e->getMessage(),
                'transaction_id' => $id,
                'admin_id' => session('admin_user.id')
            ]);

            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to retrieve transaction',
                    'error' => ['code' => 500, 'message' => 'Service error']
                ], 500);
            }

            return redirect()->route('sysadmin.transactions.index')->with('error', 'Failed to load transaction details');
        }
    }

    /**
     * Show create transaction form.
     */
    public function create(Request $request)
    {
        $adminUser = session('admin_user');

        if (!$adminUser) {
            return redirect()->route('sysadmin.login');
        }

        return view('admin.transactions.create', [
            'admin' => $adminUser
        ]);
    }

    /**
     * Process payment transaction.
     */
    public function payment(Request $request)
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

            // Validate input
            $validator = Validator::make($request->all(), [
                'to_user_id' => 'required|string',
                'amount' => 'required|numeric|min:1000',
                'description' => 'required|string|max:255',
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

            $paymentData = [
                'to_user_id' => $request->input('to_user_id'),
                'amount' => $request->input('amount'),
                'description' => $request->input('description'),
            ];

            $result = $this->goService->createPayment($paymentData, $adminToken);

            if ($result) {
                Log::info('Payment transaction created successfully', [
                    'transaction_id' => $result['id'] ?? null,
                    'amount' => $paymentData['amount'],
                    'admin_id' => $adminUser['id']
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment processed successfully',
                        'data' => $result
                    ]);
                }

                return redirect()->route('sysadmin.transactions.index')
                    ->with('success', 'Payment processed successfully!');
            }

            $errorMessage = 'Failed to process payment. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage)->withInput();

        } catch (\Exception $e) {
            Log::error('Payment transaction error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'data' => $request->all()
            ]);

            $errorMessage = 'Payment processing failed. Please try again.';

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
     * Process transfer transaction.
     */
    public function transfer(Request $request)
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

            // Validate input
            $validator = Validator::make($request->all(), [
                'to_user_id' => 'required|string',
                'amount' => 'required|numeric|min:1000',
                'description' => 'required|string|max:255',
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

            $transferData = [
                'to_user_id' => $request->input('to_user_id'),
                'amount' => $request->input('amount'),
                'description' => $request->input('description'),
            ];

            $result = $this->goService->createTransfer($transferData, $adminToken);

            if ($result) {
                Log::info('Transfer transaction created successfully', [
                    'transaction_id' => $result['id'] ?? null,
                    'amount' => $transferData['amount'],
                    'admin_id' => $adminUser['id']
                ]);

                if ($request->expectsJson()) {
                    return response()->json([
                        'success' => true,
                        'message' => 'Transfer processed successfully',
                        'data' => $result
                    ]);
                }

                return redirect()->route('sysadmin.transactions.index')
                    ->with('success', 'Transfer processed successfully!');
            }

            $errorMessage = 'Failed to process transfer. Please try again.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage
                ], 500);
            }

            return back()->with('error', $errorMessage)->withInput();

        } catch (\Exception $e) {
            Log::error('Transfer transaction error', [
                'error' => $e->getMessage(),
                'admin_id' => session('admin_user.id'),
                'data' => $request->all()
            ]);

            $errorMessage = 'Transfer processing failed. Please try again.';

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
}