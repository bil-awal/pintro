<?php

namespace App\Filament\Widgets;

use App\Models\Transaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentTransactionsWidget extends BaseWidget
{
    protected static ?string $heading = 'Recent Transactions';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Transaction::query()
                    ->with(['user', 'fromAccount', 'toAccount'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->copyable()
                    ->limit(20),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'primary' => 'topup',
                        'success' => 'payment',
                        'warning' => 'transfer',
                        'danger' => 'withdrawal',
                    ]),
                Tables\Columns\TextColumn::make('formatted_amount')
                    ->label('Amount')
                    ->alignEnd()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View')
                    ->icon('heroicon-o-eye')
                    ->url(fn (Transaction $record): string => 
                        route('filament.admin.resources.transactions.view', $record)
                    ),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
