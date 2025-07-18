<?php

namespace App\Filament\User\Resources;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Actions\Action;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Builder;

class TransactionResource extends Resource
{
    protected static ?string $model = null; // We'll use a custom data source

    protected static ?string $navigationIcon = 'heroicon-o-list-bullet';

    protected static ?string $navigationLabel = 'Transaction History';

    protected static ?string $slug = 'transactions';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Transactions';

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Form is not needed for read-only resource
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(static::getTableQuery())
            ->columns([
                TextColumn::make('created_at')
                    ->label('Date & Time')
                    ->dateTime('M j, Y H:i')
                    ->sortable()
                    ->searchable(),

                BadgeColumn::make('type')
                    ->label('Type')
                    ->colors([
                        'success' => 'topup',
                        'primary' => 'payment',
                        'info' => 'transfer',
                        'gray' => 'other',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'topup' => 'Top-up',
                        'payment' => 'Payment',
                        'transfer' => 'Transfer',
                        default => ucfirst($state),
                    }),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->searchable()
                    ->tooltip(function (TextColumn $column): ?string {
                        $state = $column->getState();
                        return strlen($state) > 50 ? $state : null;
                    }),

                TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(function ($state, $record) {
                        $prefix = ($record['type'] ?? '') === 'topup' ? '+' : '-';
                        return $prefix . 'Rp ' . number_format($state, 0, ',', '.');
                    })
                    ->color(fn ($record): string => 
                        ($record['type'] ?? '') === 'topup' ? 'success' : 'danger'
                    )
                    ->weight('bold')
                    ->sortable(),

                BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => 'processing',
                        'success' => ['completed', 'success'],
                        'danger' => ['failed', 'cancelled'],
                    ])
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('reference')
                    ->label('Reference')
                    ->fontFamily('mono')
                    ->copyable()
                    ->copyMessage('Reference copied to clipboard')
                    ->limit(20)
                    ->tooltip(function (TextColumn $column): ?string {
                        return $column->getState();
                    }),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        'topup' => 'Top-up',
                        'payment' => 'Payment',
                        'transfer' => 'Transfer',
                    ])
                    ->placeholder('All Types'),

                SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->placeholder('All Statuses'),

                Filter::make('date_range')
                    ->form([
                        DatePicker::make('date_from')
                            ->label('From Date')
                            ->placeholder('Select start date'),
                        DatePicker::make('date_to')
                            ->label('To Date')
                            ->placeholder('Select end date'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        // This will be handled in our custom query method
                        return $query;
                    }),
            ])
            ->actions([
                Action::make('view_details')
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
            ->headerActions([
                Action::make('export')
                    ->label('Export History')
                    ->icon('heroicon-o-document-arrow-down')
                    ->color('success')
                    ->action(function () {
                        // Export functionality
                        return static::exportTransactions();
                    }),
                
                Action::make('refresh')
                    ->label('Refresh')
                    ->icon('heroicon-o-arrow-path')
                    ->color('gray')
                    ->action(function () {
                        // Just refresh the page
                        return redirect()->to(request()->url());
                    }),
            ])
            ->emptyStateHeading('No transactions found')
            ->emptyStateDescription('Your transaction history will appear here once you make your first transaction.')
            ->emptyStateIcon('heroicon-o-banknotes')
            ->defaultSort('created_at', 'desc')
            ->striped()
            ->paginated([10, 25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
        ];
    }

    public static function getTableQuery()
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');

            if (!$token) {
                return collect([]);
            }

            // Get filters from request
            $filters = request()->all();
            $queryFilters = [];

            if (!empty($filters['tableFilters']['type']['value'])) {
                $queryFilters['type'] = $filters['tableFilters']['type']['value'];
            }

            if (!empty($filters['tableFilters']['status']['value'])) {
                $queryFilters['status'] = $filters['tableFilters']['status']['value'];
            }

            if (!empty($filters['tableFilters']['date_range']['date_from'])) {
                $queryFilters['date_from'] = $filters['tableFilters']['date_range']['date_from'];
            }

            if (!empty($filters['tableFilters']['date_range']['date_to'])) {
                $queryFilters['date_to'] = $filters['tableFilters']['date_range']['date_to'];
            }

            // Set pagination
            $queryFilters['limit'] = request('tableRecordsPerPage', 25);
            $queryFilters['offset'] = (request('page', 1) - 1) * $queryFilters['limit'];

            $transactions = $goService->getUserTransactions($token, $queryFilters);

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
            Log::error('Transaction resource query error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return collect([]);
        }
    }

    protected static function exportTransactions()
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');

            if (!$token) {
                throw new \Exception('Not authenticated');
            }

            // Get all transactions for export
            $transactions = $goService->getUserTransactions($token, ['limit' => 10000]);

            // Generate CSV content
            $csvContent = static::generateCsvContent($transactions);

            // Generate filename
            $filename = 'transactions_' . date('Y-m-d_H-i-s') . '.csv';

            return response()->streamDownload(function () use ($csvContent) {
                echo $csvContent;
            }, $filename, [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]);

        } catch (\Exception $e) {
            Log::error('Transaction export error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            throw $e;
        }
    }

    private static function generateCsvContent(array $transactions): string
    {
        $headers = [
            'Transaction ID',
            'Date',
            'Type',
            'Description',
            'Amount',
            'Status',
            'Reference',
            'Fee',
            'Currency',
        ];

        $csv = implode(',', $headers) . "\n";

        foreach ($transactions as $transaction) {
            $row = [
                $transaction['id'] ?? '',
                isset($transaction['created_at']) ? date('Y-m-d H:i:s', strtotime($transaction['created_at'])) : '',
                $transaction['type'] ?? '',
                '"' . str_replace('"', '""', $transaction['description'] ?? '') . '"',
                $transaction['amount'] ?? 0,
                $transaction['status'] ?? '',
                $transaction['reference'] ?? '',
                $transaction['fee'] ?? 0,
                $transaction['currency'] ?? 'IDR',
            ];

            $csv .= implode(',', $row) . "\n";
        }

        return $csv;
    }

    public static function canCreate(): bool
    {
        return false; // Transactions are created through other means
    }

    public static function canEdit($record): bool
    {
        return false; // Transactions should not be editable by users
    }

    public static function canDelete($record): bool
    {
        return false; // Transactions should not be deletable by users
    }
}
