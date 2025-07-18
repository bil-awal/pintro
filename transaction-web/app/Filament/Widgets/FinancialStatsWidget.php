<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class FinancialStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $reportService = new ReportService();
        $stats = $reportService->getRealTimeMetrics();
        
        $todayStats = $stats['today'];
        $growth = $stats['growth'];

        return [
            Stat::make('Today\'s Transactions', number_format($todayStats['today_transactions']))
                ->description($growth['transactions'] >= 0 
                    ? "+{$growth['transactions']}% from yesterday" 
                    : "{$growth['transactions']}% from yesterday")
                ->descriptionIcon($growth['transactions'] >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($growth['transactions'] >= 0 ? 'success' : 'danger')
                ->chart([7, 2, 10, 3, 15, 4, 17]),
            
            Stat::make('Today\'s Volume', 'Rp ' . number_format($todayStats['today_volume'], 0, ',', '.'))
                ->description($growth['volume'] >= 0 
                    ? "+{$growth['volume']}% from yesterday" 
                    : "{$growth['volume']}% from yesterday")
                ->descriptionIcon($growth['volume'] >= 0 
                    ? 'heroicon-m-arrow-trending-up' 
                    : 'heroicon-m-arrow-trending-down')
                ->color($growth['volume'] >= 0 ? 'success' : 'danger')
                ->chart([15, 4, 10, 2, 12, 4, 12]),
            
            Stat::make('Pending Transactions', number_format($todayStats['pending_count']))
                ->description('Awaiting processing')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),
            
            Stat::make('Success Rate', $todayStats['success_rate'] . '%')
                ->description('Transaction success rate')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color($todayStats['success_rate'] > 95 ? 'success' : 'danger'),
        ];
    }

    protected function getPollingInterval(): ?string
    {
        return '30s';
    }
}
