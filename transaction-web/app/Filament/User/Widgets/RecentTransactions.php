<?php

namespace App\Filament\User\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Support\Colors\Color;

class RecentTransactions extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $heading = 'Recent Transactions';

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date')
                    ->dateTime('M j, Y H:i')
                    ->sortable(),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'topup' => 'success',
                        'payment' => 'primary',
                        'transfer' => 'info',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'topup' => 'Top-up',
                        'payment' => 'Payment',
                        'transfer' => 'Transfer',
                        default => ucfirst($state),
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->color(fn (string $state, $record): string => 
                        ($record['type'] ?? '') === 'topup' ? 'success' : 'danger'
                    )
                    ->weight('bold'),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => ['completed', 'success'],
                        'danger' => ['failed', 'cancelled'],
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('View Details')
                    ->icon('heroicon-o-eye')
                    ->color('primary')
                    ->modalHeading('Transaction Details')
                    ->modalContent(function ($record) {
                        return view('filament.user.widgets.transaction-details', [
                            'transaction' => $record,
                        ]);
                    })
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel('Close'),
            ])
            ->emptyStateHeading('No transactions yet')
            ->emptyStateDescription('Your transaction history will appear here once you make your first transaction.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->defaultSort('created_at', 'desc')
            ->paginated(false);
    }

    protected function getTableQuery()
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');

            if (!$token) {
                return collect([]);
            }

            $transactions = $goService->getUserTransactions($token, [
                'limit' => 10,
                'offset' => 0,
            ]);

            // Convert to collection for Filament table
            return collect($transactions)->map(function ($transaction) {
                return (object) [
                    'id' => $transaction['id'] ?? '',
                    'created_at' => isset($transaction['created_at']) 
                        ? \Carbon\Carbon::parse($transaction['created_at']) 
                        : now(),
                    'type' => $transaction['type'] ?? 'unknown',
                    'description' => $transaction['description'] ?? 'No description',
                    'amount' => $transaction['amount'] ?? 0,
                    'status' => $transaction['status'] ?? 'unknown',
                    'reference' => $transaction['reference'] ?? '',
                    'fee' => $transaction['fee'] ?? 0,
                    'currency' => $transaction['currency'] ?? 'IDR',
                    'metadata' => $transaction['metadata'] ?? null,
                ];
            });

        } catch (\Exception $e) {
            Log::error('Recent transactions widget error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return collect([]);
        }
    }
}
