<?php

namespace App\Filament\Resources\UserResource\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class UserStatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('All registered users')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('primary'),
            
            Stat::make('Active Users', User::where('status', 'active')->count())
                ->description('Currently active users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success'),
            
            Stat::make('New Users Today', User::whereDate('created_at', today())->count())
                ->description('Users registered today')
                ->descriptionIcon('heroicon-m-sparkles')
                ->color('info'),
            
            Stat::make('Total Balance', 'Rp ' . number_format(User::sum('balance'), 0, ',', '.'))
                ->description('Combined user balances')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
        ];
    }
}
