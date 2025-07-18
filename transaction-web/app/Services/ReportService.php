<?php

namespace App\Services;

use App\Models\Transaction;
use App\Models\User;
use App\Models\PaymentCallback;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportService
{
    /**
     * Generate financial statistics.
     */
    public function getFinancialStats(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $totalTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])->count();
        
        $totalVolume = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->sum('amount');

        $todayTransactions = Transaction::whereDate('created_at', today())->count();
        
        $todayVolume = Transaction::whereDate('created_at', today())
            ->where('status', 'completed')
            ->sum('amount');

        $pendingCount = Transaction::where('status', 'pending')->count();
        
        $failedCount = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'failed')
            ->count();

        $successRate = $totalTransactions > 0 
            ? (($totalTransactions - $failedCount) / $totalTransactions) * 100 
            : 0;

        $avgTransactionValue = $totalTransactions > 0 
            ? $totalVolume / $totalTransactions 
            : 0;

        return [
            'total_transactions' => $totalTransactions,
            'total_volume' => $totalVolume,
            'today_transactions' => $todayTransactions,
            'today_volume' => $todayVolume,
            'pending_count' => $pendingCount,
            'failed_count' => $failedCount,
            'success_rate' => round($successRate, 2),
            'avg_transaction_value' => round($avgTransactionValue, 2),
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
        ];
    }

    /**
     * Get transaction statistics by type.
     */
    public function getTransactionStatsByType(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->select('type', 
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('type')
            ->get()
            ->toArray();
    }

    /**
     * Get transaction statistics by status.
     */
    public function getTransactionStatsByStatus(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->select('status', 
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy('status')
            ->get()
            ->toArray();
    }

    /**
     * Get daily transaction trends.
     */
    public function getDailyTransactionTrends(int $days = 30): array
    {
        $startDate = Carbon::now()->subDays($days);

        return Transaction::where('created_at', '>=', $startDate)
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('COUNT(CASE WHEN status = \'completed\' THEN 1 END) as completed_count'),
                DB::raw('COUNT(CASE WHEN status = \'failed\' THEN 1 END) as failed_count')
            )
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    /**
     * Get hourly transaction distribution.
     */
    public function getHourlyTransactionDistribution(Carbon $date = null): array
    {
        $date = $date ?? Carbon::today();

        return Transaction::whereDate('created_at', $date)
            ->select(
                DB::raw('HOUR(created_at) as hour'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Get top users by transaction volume.
     */
    public function getTopUsersByVolume(int $limit = 10, Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return User::select('users.*', 
                DB::raw('COUNT(transactions.id) as transaction_count'),
                DB::raw('SUM(transactions.amount) as total_volume')
            )
            ->join('transactions', 'users.id', '=', 'transactions.user_id')
            ->whereBetween('transactions.created_at', [$startDate, $endDate])
            ->where('transactions.status', 'completed')
            ->groupBy('users.id')
            ->orderBy('total_volume', 'desc')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    /**
     * Get payment method statistics.
     */
    public function getPaymentMethodStats(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('payment_method')
            ->select('payment_method', 
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->groupBy('payment_method')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get failed transaction analysis.
     */
    public function getFailedTransactionAnalysis(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        $failedTransactions = Transaction::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'failed')
            ->with(['user', 'paymentCallbacks'])
            ->get();

        $failureReasons = [];
        $paymentMethodFailures = [];

        foreach ($failedTransactions as $transaction) {
            // Analyze payment callbacks for failure reasons
            foreach ($transaction->paymentCallbacks as $callback) {
                if ($callback->isFailed()) {
                    $reason = $callback->gateway_status;
                    $failureReasons[$reason] = ($failureReasons[$reason] ?? 0) + 1;
                }
            }

            // Analyze by payment method
            if ($transaction->payment_method) {
                $method = $transaction->payment_method;
                $paymentMethodFailures[$method] = ($paymentMethodFailures[$method] ?? 0) + 1;
            }
        }

        return [
            'total_failed' => $failedTransactions->count(),
            'failure_reasons' => $failureReasons,
            'payment_method_failures' => $paymentMethodFailures,
            'failed_transactions' => $failedTransactions->take(20)->toArray(), // Latest 20 failed
        ];
    }

    /**
     * Generate comprehensive report.
     */
    public function generateComprehensiveReport(Carbon $startDate = null, Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfMonth();

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end' => $endDate->toDateString(),
            ],
            'financial_stats' => $this->getFinancialStats($startDate, $endDate),
            'transaction_by_type' => $this->getTransactionStatsByType($startDate, $endDate),
            'transaction_by_status' => $this->getTransactionStatsByStatus($startDate, $endDate),
            'daily_trends' => $this->getDailyTransactionTrends(
                $startDate->diffInDays($endDate)
            ),
            'top_users' => $this->getTopUsersByVolume(10, $startDate, $endDate),
            'payment_methods' => $this->getPaymentMethodStats($startDate, $endDate),
            'failed_analysis' => $this->getFailedTransactionAnalysis($startDate, $endDate),
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Export report to CSV format.
     */
    public function exportToCsv(array $data, string $filename = null): string
    {
        $filename = $filename ?? 'transaction_report_' . now()->format('Y-m-d_H-i-s') . '.csv';
        $filepath = storage_path('app/reports/' . $filename);

        // Ensure directory exists
        if (!file_exists(dirname($filepath))) {
            mkdir(dirname($filepath), 0755, true);
        }

        $file = fopen($filepath, 'w');

        // Write header
        fputcsv($file, array_keys($data[0] ?? []));

        // Write data
        foreach ($data as $row) {
            fputcsv($file, $row);
        }

        fclose($file);

        return $filepath;
    }

    /**
     * Get real-time metrics for dashboard.
     */
    public function getRealTimeMetrics(): array
    {
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $yesterdayStart = $now->copy()->subDay()->startOfDay();
        $yesterdayEnd = $now->copy()->subDay()->endOfDay();

        $todayStats = $this->getFinancialStats($todayStart, $now);
        $yesterdayStats = $this->getFinancialStats($yesterdayStart, $yesterdayEnd);

        return [
            'today' => $todayStats,
            'yesterday' => $yesterdayStats,
            'growth' => [
                'transactions' => $this->calculateGrowthPercentage(
                    $yesterdayStats['total_transactions'],
                    $todayStats['today_transactions']
                ),
                'volume' => $this->calculateGrowthPercentage(
                    $yesterdayStats['total_volume'],
                    $todayStats['today_volume']
                ),
            ],
            'last_updated' => $now->toISOString(),
        ];
    }

    /**
     * Calculate growth percentage.
     */
    private function calculateGrowthPercentage(float $previous, float $current): float
    {
        if ($previous == 0) {
            return $current > 0 ? 100 : 0;
        }

        return round((($current - $previous) / $previous) * 100, 2);
    }
}
