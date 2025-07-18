<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoTransactionService;
use App\Services\MidtransService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    private GoTransactionService $goService;
    private MidtransService $midtransService;
    private ReportService $reportService;

    public function __construct(
        GoTransactionService $goService,
        MidtransService $midtransService,
        ReportService $reportService
    ) {
        $this->goService = $goService;
        $this->midtransService = $midtransService;
        $this->reportService = $reportService;
    }

    /**
     * Get all transactions with filtering.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|string|in:pending,processing,completed,failed,cancelled',
            'type' => 'sometimes|string|in:topup,payment,transfer,withdrawal',
            'user_id' => 'sometimes|exists:users,id',
            'limit' => 'sometimes|integer|min:1|max:100',
            'offset' => 'sometimes|integer|min:0',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $query = Transaction::with(['user', 'fromAccount', 'toAccount']);

        // Apply filters
        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->input('date_from'));
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->input('date_to'));
        }

        // Pagination
        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        $total = $query->count();
        $transactions = $query->latest()
            ->limit($limit)
            ->offset($offset)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'transactions' => $transactions,
                'pagination' => [
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                    'has_more' => ($offset + $limit) < $total,
                ],
            ],
        ]);
    }

    /**
     * Get specific transaction.
     */
    public function show(string $transactionId): JsonResponse
    {
        $transaction = Transaction::with(['user', 'fromAccount', 'toAccount', 'paymentCallbacks'])
            ->where('transaction_id', $transactionId)
            ->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $transaction,
        ]);
    }

    /**
     * Approve a transaction.
     */
    public function approve(Request $request, string $transactionId): JsonResponse
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (!$transaction->canBeApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be approved in current status',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Call Go service to approve transaction
            $goSuccess = $this->goService->approveTransaction($transactionId, auth()->id());

            if (!$goSuccess) {
                throw new \Exception('Failed to approve transaction in Go service');
            }

            // Update local transaction
            $transaction->approve(auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction approved successfully',
                'data' => $transaction->refresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to approve transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reject a transaction.
     */
    public function reject(Request $request, string $transactionId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reason' => 'required|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (!$transaction->canBeRejected()) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction cannot be rejected in current status',
            ], 400);
        }

        DB::beginTransaction();
        try {
            // Call Go service to reject transaction
            $goSuccess = $this->goService->rejectTransaction(
                $transactionId, 
                auth()->id(), 
                $request->input('reason')
            );

            if (!$goSuccess) {
                throw new \Exception('Failed to reject transaction in Go service');
            }

            // Update local transaction
            $transaction->reject(auth()->id());

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaction rejected successfully',
                'data' => $transaction->refresh(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Failed to reject transaction',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get transaction statistics.
     */
    public function statistics(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 400);
        }

        $dateFrom = $request->input('date_from') 
            ? \Carbon\Carbon::parse($request->input('date_from')) 
            : null;
        
        $dateTo = $request->input('date_to') 
            ? \Carbon\Carbon::parse($request->input('date_to')) 
            : null;

        $stats = $this->reportService->getFinancialStats($dateFrom, $dateTo);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Sync data from Go service.
     */
    public function syncFromGoService(): JsonResponse
    {
        try {
            $result = $this->goService->syncTransactionData();

            return response()->json([
                'success' => $result['success'],
                'message' => $result['success'] 
                    ? "Synced {$result['synced_count']} transactions successfully"
                    : 'Sync failed',
                'data' => $result,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Sync failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Check payment status via Midtrans.
     */
    public function checkPaymentStatus(string $transactionId): JsonResponse
    {
        $transaction = Transaction::where('transaction_id', $transactionId)->first();

        if (!$transaction) {
            return response()->json([
                'success' => false,
                'message' => 'Transaction not found',
            ], 404);
        }

        if (!$transaction->payment_gateway_id) {
            return response()->json([
                'success' => false,
                'message' => 'No payment gateway ID available',
            ], 400);
        }

        try {
            $status = $this->midtransService->checkTransactionStatus($transaction->payment_gateway_id);

            if (!$status) {
                throw new \Exception('Failed to check payment status');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'transaction_id' => $transactionId,
                    'payment_status' => $status,
                    'local_status' => $transaction->status,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to check payment status',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
