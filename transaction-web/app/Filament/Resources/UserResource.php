<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User Management';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('first_name')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('last_name')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->unique(User::class, 'email', ignoreRecord: true)
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(20),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user_id')
                                    ->label('User ID')
                                    ->disabled()
                                    ->dehydrated(false),
                                Forms\Components\Select::make('status')
                                    ->options([
                                        'active' => 'Active',
                                        'inactive' => 'Inactive',
                                        'suspended' => 'Suspended',
                                    ])
                                    ->required()
                                    ->default('active'),
                            ]),
                    ]),
                
                Forms\Components\Section::make('Financial Information')
                    ->schema([
                        Forms\Components\TextInput::make('balance')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->default(0.00)
                            ->disabled()
                            ->dehydrated(false),
                    ]),

                Forms\Components\Section::make('Security')
                    ->schema([
                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? bcrypt($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8),
                    ])
                    ->visibleOn('create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user_id')
                    ->label('User ID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->tooltip('Click to copy'),
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->getStateUsing(fn (User $record): string => $record->full_name)
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name']),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('formatted_balance')
                    ->label('Balance')
                    ->getStateUsing(fn (User $record): string => $record->formatted_balance)
                    ->sortable('balance')
                    ->alignEnd()
                    ->weight('bold'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'suspended',
                    ]),
                Tables\Columns\TextColumn::make('transactions_count')
                    ->label('Transactions')
                    ->counts('transactions')
                    ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'inactive' => 'Inactive',
                        'suspended' => 'Suspended',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('balance_range')
                    ->form([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('balance_from')
                                    ->numeric()
                                    ->prefix('Rp'),
                                Forms\Components\TextInput::make('balance_to')
                                    ->numeric()
                                    ->prefix('Rp'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['balance_from'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '>=', $balance),
                            )
                            ->when(
                                $data['balance_to'],
                                fn (Builder $query, $balance): Builder => $query->where('balance', '<=', $balance),
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
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('adjust_balance')
                    ->label('Adjust Balance')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('operation')
                            ->options([
                                'add' => 'Add to Balance',
                                'subtract' => 'Subtract from Balance',
                                'set' => 'Set Balance',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->prefix('Rp')
                            ->step(0.01)
                            ->required()
                            ->minValue(0.01),
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->placeholder('Reason for balance adjustment...'),
                    ])
                    ->action(function (User $record, array $data): void {
                        $oldBalance = $record->balance;
                        
                        switch ($data['operation']) {
                            case 'add':
                                $record->balance += $data['amount'];
                                break;
                            case 'subtract':
                                $record->balance = max(0, $record->balance - $data['amount']);
                                break;
                            case 'set':
                                $record->balance = $data['amount'];
                                break;
                        }
                        
                        $record->save();

                        // Log the adjustment
                        \App\Models\AdminActivityLog::createLog(
                            auth('admin')->id(),
                            'balance_adjustment',
                            "Adjusted balance for user {$record->user_id} from Rp " . number_format($oldBalance, 2) . " to Rp " . number_format($record->balance, 2) . ". Reason: {$data['reason']}",
                            ['old_balance' => $oldBalance],
                            ['new_balance' => $record->balance, 'reason' => $data['reason']]
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Balance Adjusted Successfully')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation(),
                Tables\Actions\Action::make('suspend')
                    ->label('Suspend')
                    ->icon('heroicon-o-no-symbol')
                    ->color('danger')
                    ->action(function (User $record): void {
                        $record->update(['status' => 'suspended']);
                        
                        \App\Models\AdminActivityLog::createLog(
                            auth('admin')->id(),
                            'user_suspended',
                            "Suspended user {$record->user_id}"
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('User Suspended')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (User $record): bool => $record->status !== 'suspended'),
                Tables\Actions\Action::make('activate')
                    ->label('Activate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (User $record): void {
                        $record->update(['status' => 'active']);
                        
                        \App\Models\AdminActivityLog::createLog(
                            auth('admin')->id(),
                            'user_activated',
                            "Activated user {$record->user_id}"
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('User Activated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (User $record): bool => $record->status !== 'active'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_suspend')
                        ->label('Suspend Selected')
                        ->icon('heroicon-o-no-symbol')
                        ->color('danger')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (User $record) {
                                $record->update(['status' => 'suspended']);
                                
                                \App\Models\AdminActivityLog::createLog(
                                    auth('admin')->id(),
                                    'bulk_user_suspended',
                                    "Bulk suspended user {$record->user_id}"
                                );
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Users Suspended')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('bulk_activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $records->each(function (User $record) {
                                $record->update(['status' => 'active']);
                                
                                \App\Models\AdminActivityLog::createLog(
                                    auth('admin')->id(),
                                    'bulk_user_activated',
                                    "Bulk activated user {$record->user_id}"
                                );
                            });

                            \Filament\Notifications\Notification::make()
                                ->title('Users Activated')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('User Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('user_id')
                                    ->label('User ID')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('full_name')
                                    ->label('Full Name'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('email')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('phone'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'active' => 'success',
                                        'inactive' => 'warning',
                                        'suspended' => 'danger',
                                    }),
                                Infolists\Components\TextEntry::make('formatted_balance')
                                    ->label('Current Balance'),
                            ]),
                    ]),
                
                Infolists\Components\Section::make('Transaction Statistics')
                    ->schema([
                        Infolists\Components\Grid::make(3)
                            ->schema([
                                Infolists\Components\TextEntry::make('transactions_count')
                                    ->label('Total Transactions')
                                    ->getStateUsing(fn (User $record): int => $record->transactions()->count()),
                                Infolists\Components\TextEntry::make('completed_transactions_count')
                                    ->label('Completed Transactions')
                                    ->getStateUsing(fn (User $record): int => $record->transactions()->where('status', 'completed')->count()),
                                Infolists\Components\TextEntry::make('total_transaction_volume')
                                    ->label('Total Transaction Volume')
                                    ->getStateUsing(fn (User $record): string => 
                                        'Rp ' . number_format($record->transactions()->where('status', 'completed')->sum('amount'), 0, ',', '.')
                                    ),
                            ]),
                    ]),

                Infolists\Components\Section::make('Account Details')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('created_at')
                                    ->label('Registration Date')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime(),
                            ]),
                    ]),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['user_id', 'first_name', 'last_name', 'email', 'phone'];
    }
}
