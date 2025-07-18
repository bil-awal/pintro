<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected function getHeaderWidgets(): array
    {
        return [
            \App\Filament\Widgets\FinancialStatsWidget::class,
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\TransactionChartWidget::class,
            \App\Filament\Widgets\RecentTransactionsWidget::class,
            \App\Filament\Widgets\SystemHealthWidget::class,
        ];
    }
}