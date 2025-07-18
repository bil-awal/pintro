<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Widgets\AccountWidget;
use App\Filament\User\Widgets\BalanceOverview;
use App\Filament\User\Widgets\RecentTransactions;
use App\Filament\User\Widgets\QuickActions;
use App\Filament\User\Widgets\TransactionChart;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    
    protected static string $view = 'filament.user.pages.dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?int $navigationSort = 1;

    public function getWidgets(): array
    {
        return [
            AccountWidget::class,
            BalanceOverview::class,
            QuickActions::class,
            RecentTransactions::class,
            TransactionChart::class,
        ];
    }

    public function getColumns(): int | array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
            'xl' => 4,
        ];
    }
}
