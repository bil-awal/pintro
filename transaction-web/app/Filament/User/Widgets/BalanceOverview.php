<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;

class BalanceOverview extends BaseWidget
{
    protected static ?int $sort = 1;
    
    protected int | string | array $columnSpan = 'full';

    public function getStats(): array
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');
            
            if (!$token) {
                return $this->getDefaultStats();
            }

            // Get current balance
            $balance = $goService->getAuthenticatedUserBalance($token);
            
            // Get transaction stats
            $transactions = $goService->getUserTransactions($token, ['limit' => 100]);
            $stats = $this->calculateStats($transactions);

            return [
                Stat::make('Current Balance', 'Rp ' . number_format($balance ?? 0, 0, ',', '.'))
                    ->description('Your current account balance')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('success')
                    ->chart([40, 45, 42, 47, 43, 48, 50]),

                Stat::make('This Month Spending', 'Rp ' . number_format($stats['this_month_spending'], 0, ',', '.'))
                    ->description('Total spent this month')
                    ->descriptionIcon('heroicon-m-credit-card')
                    ->color('warning')
                    ->chart([30, 35, 32, 37, 33, 38, 40]),

                Stat::make('Total Transactions', $stats['total_count'])
                    ->description('All time transactions')
                    ->descriptionIcon('heroicon-m-list-bullet')
                    ->color('primary')
                    ->chart([10, 15, 12, 17, 13, 18, 20]),

                Stat::make('Pending Transactions', $stats['pending_count'])
                    ->description('Transactions awaiting processing')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color($stats['pending_count'] > 0 ? 'danger' : 'success')
                    ->chart([2, 3, 1, 4, 2, 3, 1]),
            ];

        } catch (\Exception $e) {
            Log::error('Balance overview widget error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return $this->getDefaultStats();
        }
    }

    private function calculateStats(array $transactions): array
    {
        $stats = [
            'total_count' => count($transactions),
            'this_month_spending' => 0,
            'pending_count' => 0,
        ];

        $currentMonth = now()->format('Y-m');

        foreach ($transactions as $transaction) {
            // Count pending transactions
            if (in_array($transaction['status'] ?? '', ['pending', 'processing'])) {
                $stats['pending_count']++;
            }

            // Calculate this month spending
            $transactionDate = isset($transaction['created_at']) 
                ? \Carbon\Carbon::parse($transaction['created_at'])->format('Y-m')
                : null;

            if ($transactionDate === $currentMonth && ($transaction['type'] ?? '') === 'payment') {
                $stats['this_month_spending'] += $transaction['amount'] ?? 0;
            }
        }

        return $stats;
    }

    private function getDefaultStats(): array
    {
        return [
            Stat::make('Current Balance', 'Rp 0')
                ->description('Unable to load balance')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('This Month Spending', 'Rp 0')
                ->description('Unable to load spending data')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Total Transactions', '0')
                ->description('Unable to load transaction count')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),

            Stat::make('Pending Transactions', '0')
                ->description('Unable to load pending count')
                ->descriptionIcon('heroicon-m-exclamation-triangle')
                ->color('danger'),
        ];
    }
}
