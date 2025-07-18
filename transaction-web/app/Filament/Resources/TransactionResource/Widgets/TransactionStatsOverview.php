<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Carbon\Carbon;

class TransactionStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $reportService = new ReportService();
        $stats = $reportService->getFinancialStats();

        return [
            Stat::make('Total Transactions', number_format($stats['total_transactions']))
                ->description('This month')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->chart([7, 2, 10, 3, 15, 4, 17])
                ->color('primary'),
            
            Stat::make('Total Volume', 'Rp ' . number_format($stats['total_volume'], 0, ',', '.'))
                ->description('This month')
                ->descriptionIcon('heroicon-m-banknotes')
                ->chart([15, 4, 10, 2, 12, 4, 12])
                ->color('success'),
            
            Stat::make('Today\'s Transactions', number_format($stats['today_transactions']))
                ->description('Transactions today')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
            
            Stat::make('Pending Transactions', number_format($stats['pending_count']))
                ->description('Awaiting approval')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Success Rate', $stats['success_rate'] . '%')
                ->description('Transaction success rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($stats['success_rate'] > 95 ? 'success' : 'danger'),
            
            Stat::make('Average Transaction', 'Rp ' . number_format($stats['avg_transaction_value'], 0, ',', '.'))
                ->description('Average amount per transaction')
                ->descriptionIcon('heroicon-m-calculator')
                ->color('secondary'),
        ];
    }
}
