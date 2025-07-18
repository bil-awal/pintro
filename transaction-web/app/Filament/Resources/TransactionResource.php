<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Models\Transaction;
use App\Models\User;
use App\Services\GoTransactionService;
use App\Services\MidtransService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Transaction Details')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\TextInput::make('reference')
                                    ->disabled()
                                    ->dehydrated(false),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('User')
                                    ->relationship('user', 'email')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('type')
                                    ->options([
                                        'topup' => 'Top-up',
                                        'payment' => 'Payment',
                                        'transfer' => 'Transfer',
                                        'withdrawal' => 'Withdrawal',
                                    ])
                                    ->required(),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('from_account_id')
                                    ->label('From Account')
                                    ->relationship('fromAccount', 'email')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get): bool => in_array($get('type'), ['transfer', 'payment'])),
                                Forms\Components\Select::make('to_account_id')
                                    ->label('To Account')
                                    ->relationship('toAccount', 'email')
                                    ->searchable()
                                    ->preload()
                                    ->visible(fn (Forms\Get $get): bool => $get('type') === 'transfer'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\Grid::make(3)
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->step(0.01)
                                    ->minValue(0.01)
                                    ->required(),
                                Forms\Components\TextInput::make('fee')
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->step(0.01)
                                    ->default(0.00),
                                Forms\Components\Select::make('currency')
                                    ->options([
                                        'IDR' => 'Indonesian Rupiah',
                                        'USD' => 'US Dollar',
                                        'EUR' => 'Euro',
                                    ])
                                    ->default('IDR')
                                    ->required(),
                            ]),
                        Forms\Components\Textarea::make('description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Status & Payment')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'processing' => 'Processing',
                                        'completed' => 'Completed',
                                        'failed' => 'Failed',
                                        'cancelled' => 'Cancelled',
                                    ])
                                    ->required(),
                                Forms\Components\TextInput::make('payment_method')
                                    ->label('Payment Method'),
                            ]),
                        Forms\Components\TextInput::make('payment_gateway_id')
                            ->label('Payment Gateway ID'),
                    ]),

                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\KeyValue::make('metadata')
                            ->addable()
                            ->deletable()
                            ->reorderable(),
                    ])
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),
                Tables\Columns\TextColumn::make('reference')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User')
                    ->searchable()
                    ->sortable(),
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
                    ->sortable('amount')
                    ->alignEnd()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('formatted_fee')
                    ->label('Fee')
                    ->sortable('fee')
                    ->alignEnd()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'processing',
                        'success' => 'completed',
                        'danger' => 'failed',
                        'secondary' => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('fromAccount.email')
                    ->label('From')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('toAccount.email')
                    ->label('To')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'processing' => 'Processing',
                        'completed' => 'Completed',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'topup' => 'Top-up',
                        'payment' => 'Payment',
                        'transfer' => 'Transfer',
                        'withdrawal' => 'Withdrawal',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('amount_from')
                                    ->numeric()
                                    ->prefix('Rp'),
                                Forms\Components\TextInput::make('amount_to')
                                    ->numeric()
                                    ->prefix('Rp'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['amount_from'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '>=', $amount),
                            )
                            ->when(
                                $data['amount_to'],
                                fn (Builder $query, $amount): Builder => $query->where('amount', '<=', $amount),
                            );
                    }),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('created_from'),
                        Forms\Components\DatePicker::make('created_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['created_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '>=', $date),
                            )
                            ->when(
                                $data['created_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('created_at', '<=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('user_id')
                    ->relationship('user', 'email')
                    ->searchable()
                    ->preload()
                    ->label('User'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (Transaction $record): void {
                        $goService = new GoTransactionService();
                        $success = $goService->approveTransaction($record->transaction_id, auth('admin')->id());

                        if ($success) {
                            $record->approve(auth('admin')->id());
                            
                            \App\Models\AdminActivityLog::createLog(
                                auth('admin')->id(),
                                'transaction_approved',
                                "Approved transaction {$record->transaction_id}"
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Transaction Approved')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to approve transaction')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record): bool => $record->canBeApproved()),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Rejection Reason')
                            ->required()
                            ->placeholder('Please provide a reason for rejection...'),
                    ])
                    ->action(function (Transaction $record, array $data): void {
                        $goService = new GoTransactionService();
                        $success = $goService->rejectTransaction($record->transaction_id, auth('admin')->id(), $data['reason']);

                        if ($success) {
                            $record->reject(auth('admin')->id());
                            
                            \App\Models\AdminActivityLog::createLog(
                                auth('admin')->id(),
                                'transaction_rejected',
                                "Rejected transaction {$record->transaction_id}. Reason: {$data['reason']}"
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Transaction Rejected')
                                ->success()
                                ->send();
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('Failed to reject transaction')
                                ->danger()
                                ->send();
                        }
                    })
                    ->requiresConfirmation()
                    ->visible(fn (Transaction $record): bool => $record->canBeRejected()),
                Tables\Actions\Action::make('check_status')
                    ->label('Check Status')
                    ->icon('heroicon-o-magnifying-glass')
                    ->color('info')
                    ->action(function (Transaction $record): void {
                        if ($record->payment_gateway_id) {
                            $midtransService = new MidtransService();
                            $status = $midtransService->checkTransactionStatus($record->payment_gateway_id);

                            if ($status) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Payment Status: ' . ($status['transaction_status'] ?? 'Unknown'))
                                    ->body('Gateway Status: ' . ($status['status_message'] ?? 'No message'))
                                    ->info()
                                    ->send();
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('Failed to check payment status')
                                    ->danger()
                                    ->send();
                            }
                        } else {
                            \Filament\Notifications\Notification::make()
                                ->title('No payment gateway ID available')
                                ->warning()
                                ->send();
                        }
                    })
                    ->visible(fn (Transaction $record): bool => !empty($record->payment_gateway_id)),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_approve')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $goService = new GoTransactionService();
                            $successCount = 0;

                            foreach ($records as $record) {
                                if ($record->canBeApproved()) {
                                    if ($goService->approveTransaction($record->transaction_id, auth('admin')->id())) {
                                        $record->approve(auth('admin')->id());
                                        $successCount++;

                                        \App\Models\AdminActivityLog::createLog(
                                            auth('admin')->id(),
                                            'bulk_transaction_approved',
                                            "Bulk approved transaction {$record->transaction_id}"
                                        );
                                    }
                                }
                            }

                            \Filament\Notifications\Notification::make()
                                ->title("Approved {$successCount} transactions")
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Transaction Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('reference')
                                    ->copyable(),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('type')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'topup' => 'primary',
                                        'payment' => 'success',
                                        'transfer' => 'warning',
                                        'withdrawal' => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'pending' => 'warning',
                                        'processing' => 'primary',
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'cancelled' => 'secondary',
                                    }),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Financial Details')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('formatted_amount')
                                    ->label('Amount'),
                                Infolists\Components\TextEntry::make('formatted_fee')
                                    ->label('Fee'),
                                Infolists\Components\TextEntry::make('formatted_total_amount')
                                    ->label('Total Amount'),
                            ]),
                        Infolists\Components\TextEntry::make('currency'),
                        Infolists\Components\TextEntry::make('description')
                            ->columnSpanFull(),
                    ]),

                Infolists\Components\Section::make('User Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user.full_name')
                                    ->label('User'),
                                Infolists\Components\TextEntry::make('user.email')
                                    ->label('Email'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('fromAccount.email')
                                    ->label('From Account')
                                    ->visible(fn (Transaction $record): bool => !empty($record->from_account_id)),
                                Infolists\Components\TextEntry::make('toAccount.email')
                                    ->label('To Account')
                                    ->visible(fn (Transaction $record): bool => !empty($record->to_account_id)),
                            ]),
                    ]),

                Infolists\Components\Section::make('Payment Gateway')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('payment_gateway_id')
                                    ->label('Gateway ID')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('payment_method')
                                    ->label('Payment Method'),
                            ]),
                    ])
                    ->visible(fn (Transaction $record): bool => !empty($record->payment_gateway_id)),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Created')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('processed_at')
                                    ->label('Processed')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('approved_at')
                                    ->label('Approved')
                                    ->dateTime(),
                            ]),
                        Infolists\Components\TextEntry::make('approvedBy.name')
                            ->label('Approved By')
                            ->visible(fn (Transaction $record): bool => !empty($record->approved_by)),
                    ]),

                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\KeyValueEntry::make('metadata')
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (Transaction $record): bool => !empty($record->metadata))
                    ->collapsible(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'view' => Pages\ViewTransaction::route('/{record}'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_id', 'reference', 'description', 'user.email'];
    }
}
