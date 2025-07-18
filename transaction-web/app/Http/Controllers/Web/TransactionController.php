<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Services\GoTransactionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class TransactionController extends Controller
{
    private GoTransactionService $goService;

    public function __construct(GoTransactionService $goService)
    {
        $this->goService = $goService;
        $this->middleware('web.auth');
    }

    /**
     * Show transaction history.
     */
    public function index(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $userData = Session::get('user_data');

            // Get filter parameters
            $filters = $this->buildFilters($request);

            // Get transactions from Go service
            $transactions = $this->goService->getUserTransactions($token, $filters);

            // Get summary statistics
            $stats = $this->calculateTransactionStats($transactions);

            return view('transactions.index', [
                'user' => $userData,
                'transactions' => $transactions,
                'stats' => $stats,
                'filters' => $filters,
                'transaction_types' => $this->getTransactionTypes(),
                'transaction_statuses' => $this->getTransactionStatuses(),
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction history load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return view('transactions.index', [
                'user' => Session::get('user_data', []),
                'transactions' => [],
                'stats' => $this->getDefaultStats(),
                'filters' => [],
                'transaction_types' => $this->getTransactionTypes(),
                'transaction_statuses' => $this->getTransactionStatuses(),
                'error' => 'Failed to load transaction history.',
            ]);
        }
    }

    /**
     * Show detailed transaction view.
     */
    public function show(Request $request, string $transactionId)
    {
        try {
            $token = Session::get('user_token');
            
            // Get transaction details
            $transaction = $this->goService->getTransaction($transactionId);

            if (!$transaction) {
                return redirect()->route('transactions.index')
                    ->with('error', 'Transaction not found.');
            }

            return view('transactions.show', [
                'transaction' => $transaction,
                'user' => Session::get('user_data'),
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction detail load error', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'user_id' => Session::get('user_id'),
            ]);

            return redirect()->route('transactions.index')
                ->with('error', 'Failed to load transaction details.');
        }
    }

    /**
     * Export transactions to CSV.
     */
    public function export(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'type' => 'nullable|string|in:topup,payment,transfer',
            'status' => 'nullable|string|in:pending,processing,completed,failed,cancelled',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator);
        }

        try {
            $token = Session::get('user_token');
            $filters = $this->buildFilters($request);

            // Get all transactions for export
            $transactions = $this->goService->getUserTransactions($token, $filters);

            // Generate CSV content
            $csvContent = $this->generateCsvContent($transactions);

            // Generate filename
            $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';

            return response($csvContent)
                ->header('Content-Type', 'text/csv')
                ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');

        } catch (\Exception $e) {
            Log::error('Transaction export error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return back()->with('error', 'Failed to export transactions.');
        }
    }

    /**
     * Get transactions via AJAX for pagination.
     */
    public function ajax(Request $request)
    {
        try {
            $token = Session::get('user_token');
            $filters = $this->buildFilters($request);

            $transactions = $this->goService->getUserTransactions($token, $filters);

            return response()->json([
                'success' => true,
                'transactions' => $transactions,
                'has_more' => count($transactions) >= ($filters['limit'] ?? 20),
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction AJAX load error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to load transactions',
                'transactions' => [],
            ], 500);
        }
    }

    /**
     * Search transactions.
     */
    public function search(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:3|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid search query',
                'transactions' => [],
            ], 400);
        }

        try {
            $token = Session::get('user_token');
            $searchQuery = $request->input('query');

            // Get all transactions and filter on frontend for now
            // In production, this should be handled by the Go service
            $allTransactions = $this->goService->getUserTransactions($token);
            
            $filteredTransactions = array_filter($allTransactions, function($transaction) use ($searchQuery) {
                $searchableFields = [
                    $transaction['description'] ?? '',
                    $transaction['reference'] ?? '',
                    $transaction['type'] ?? '',
                    $transaction['status'] ?? '',
                ];
                
                $searchableText = strtolower(implode(' ', $searchableFields));
                return str_contains($searchableText, strtolower($searchQuery));
            });

            return response()->json([
                'success' => true,
                'transactions' => array_values($filteredTransactions),
                'query' => $searchQuery,
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction search error', [
                'error' => $e->getMessage(),
                'query' => $request->input('query'),
                'user_id' => Session::get('user_id'),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Search failed',
                'transactions' => [],
            ], 500);
        }
    }

    /**
     * Build filters from request parameters.
     */
    private function buildFilters(Request $request): array
    {
        $filters = [];

        if ($request->has('type') && $request->input('type')) {
            $filters['type'] = $request->input('type');
        }

        if ($request->has('status') && $request->input('status')) {
            $filters['status'] = $request->input('status');
        }

        if ($request->has('date_from') && $request->input('date_from')) {
            $filters['date_from'] = $request->input('date_from');
        }

        if ($request->has('date_to') && $request->input('date_to')) {
            $filters['date_to'] = $request->input('date_to');
        }

        // Pagination
        $filters['limit'] = $request->input('limit', 20);
        $filters['offset'] = $request->input('offset', 0);

        return $filters;
    }

    /**
     * Calculate transaction statistics.
     */
    private function calculateTransactionStats(array $transactions): array
    {
        $stats = [
            'total_count' => count($transactions),
            'total_amount' => 0,
            'topup_count' => 0,
            'topup_amount' => 0,
            'payment_count' => 0,
            'payment_amount' => 0,
            'pending_count' => 0,
            'completed_count' => 0,
            'failed_count' => 0,
        ];

        foreach ($transactions as $transaction) {
            $amount = $transaction['amount'] ?? 0;
            $type = $transaction['type'] ?? '';
            $status = $transaction['status'] ?? '';

            $stats['total_amount'] += $amount;

            // Count by type
            if ($type === 'topup') {
                $stats['topup_count']++;
                $stats['topup_amount'] += $amount;
            } elseif ($type === 'payment') {
                $stats['payment_count']++;
                $stats['payment_amount'] += $amount;
            }

            // Count by status
            switch ($status) {
                case 'pending':
                case 'processing':
                    $stats['pending_count']++;
                    break;
                case 'completed':
                case 'success':
                    $stats['completed_count']++;
                    break;
                case 'failed':
                case 'cancelled':
                    $stats['failed_count']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Generate CSV content from transactions.
     */
    private function generateCsvContent(array $transactions): string
    {
        $headers = [
            'Transaction ID',
            'Date',
            'Type',
            'Description',
            'Amount',
            'Status',
            'Reference',
        ];

        $csv = implode(',', $headers) . "\n";

        foreach ($transactions as $transaction) {
            $row = [
                $transaction['id'] ?? '',
                isset($transaction['created_at']) ? date('Y-m-d H:i:s', strtotime($transaction['created_at'])) : '',
                $transaction['type'] ?? '',
                '"' . str_replace('"', '""', $transaction['description'] ?? '') . '"',
                $transaction['amount'] ?? 0,
                $transaction['status'] ?? '',
                $transaction['reference'] ?? '',
            ];

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    /**
     * Get transaction types.
     */
    private function getTransactionTypes(): array
    {
        return [
            '' => 'All Types',
            'topup' => 'Top-up',
            'payment' => 'Payment',
            'transfer' => 'Transfer',
        ];
    }

    /**
     * Get transaction statuses.
     */
    private function getTransactionStatuses(): array
    {
        return [
            '' => 'All Statuses',
            'pending' => 'Pending',
            'processing' => 'Processing',
            'completed' => 'Completed',
            'success' => 'Success',
            'failed' => 'Failed',
            'cancelled' => 'Cancelled',
        ];
    }

    /**
     * Get default stats structure.
     */
    private function getDefaultStats(): array
    {
        return [
            'total_count' => 0,
            'total_amount' => 0,
            'topup_count' => 0,
            'topup_amount' => 0,
            'payment_count' => 0,
            'payment_amount' => 0,
            'pending_count' => 0,
            'completed_count' => 0,
            'failed_count' => 0,
        ];
    }
}
