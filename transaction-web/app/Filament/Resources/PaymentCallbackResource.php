<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentCallbackResource\Pages;
use App\Models\PaymentCallback;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentCallbackResource extends Resource
{
    protected static ?string $model = PaymentCallback::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Transaction Management';

    protected static ?string $navigationLabel = 'Payment Callbacks';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Callback Information')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->required()
                                    ->maxLength(255),
                                Forms\Components\TextInput::make('gateway_transaction_id')
                                    ->label('Gateway Transaction ID')
                                    ->required()
                                    ->maxLength(255),
                            ]),
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('gateway_status')
                                    ->label('Gateway Status')
                                    ->options([
                                        'pending' => 'Pending',
                                        'settlement' => 'Settlement',
                                        'capture' => 'Capture',
                                        'deny' => 'Deny',
                                        'cancel' => 'Cancel',
                                        'expire' => 'Expire',
                                        'failure' => 'Failure',
                                    ])
                                    ->required(),
                                Forms\Components\Toggle::make('verified')
                                    ->label('Verified')
                                    ->default(false),
                            ]),
                        Forms\Components\TextInput::make('signature')
                            ->maxLength(255),
                    ]),
                
                Forms\Components\Section::make('Raw Payload')
                    ->schema([
                        Forms\Components\Textarea::make('raw_payload')
                            ->label('Raw Payload (JSON)')
                            ->rows(10)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => json_decode($state, true)),
                    ])
                    ->collapsible(),

                Forms\Components\Section::make('Timestamps')
                    ->schema([
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\DateTimePicker::make('received_at')
                                    ->label('Received At')
                                    ->default(now()),
                                Forms\Components\DateTimePicker::make('processed_at')
                                    ->label('Processed At'),
                            ]),
                    ]),
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
                    ->copyable(),
                Tables\Columns\TextColumn::make('gateway_transaction_id')
                    ->label('Gateway ID')
                    ->searchable()
                    ->copyable()
                    ->toggleable(),
                Tables\Columns\BadgeColumn::make('gateway_status')
                    ->label('Gateway Status')
                    ->colors([
                        'success' => ['settlement', 'capture'],
                        'warning' => 'pending',
                        'danger' => ['deny', 'cancel', 'expire', 'failure'],
                    ]),
                Tables\Columns\IconColumn::make('verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('transaction.user.email')
                    ->label('User')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('transaction.amount')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('received_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('gateway_status')
                    ->options([
                        'pending' => 'Pending',
                        'settlement' => 'Settlement',
                        'capture' => 'Capture',
                        'deny' => 'Deny',
                        'cancel' => 'Cancel',
                        'expire' => 'Expire',
                        'failure' => 'Failure',
                    ])
                    ->multiple(),
                Tables\Filters\TernaryFilter::make('verified')
                    ->label('Verification Status')
                    ->trueLabel('Verified')
                    ->falseLabel('Unverified')
                    ->native(false),
                Tables\Filters\TernaryFilter::make('processed')
                    ->label('Processing Status')
                    ->trueLabel('Processed')
                    ->falseLabel('Unprocessed')
                    ->queries(
                        true: fn (Builder $query) => $query->whereNotNull('processed_at'),
                        false: fn (Builder $query) => $query->whereNull('processed_at'),
                    ),
                Tables\Filters\Filter::make('received_at')
                    ->form([
                        Forms\Components\DatePicker::make('received_from'),
                        Forms\Components\DatePicker::make('received_until'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['received_from'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '>=', $date),
                            )
                            ->when(
                                $data['received_until'],
                                fn (Builder $query, $date): Builder => $query->whereDate('received_at', '<=', $date),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('mark_verified')
                    ->label('Mark Verified')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->action(function (PaymentCallback $record): void {
                        $record->markAsVerified();
                        
                        \App\Models\AdminActivityLog::createLog(
                            auth('admin')->id(),
                            'callback_verified',
                            "Marked payment callback {$record->id} as verified"
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Callback Marked as Verified')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (PaymentCallback $record): bool => !$record->verified),
                Tables\Actions\Action::make('mark_processed')
                    ->label('Mark Processed')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->color('info')
                    ->action(function (PaymentCallback $record): void {
                        $record->markAsProcessed();
                        
                        \App\Models\AdminActivityLog::createLog(
                            auth('admin')->id(),
                            'callback_processed',
                            "Marked payment callback {$record->id} as processed"
                        );

                        \Filament\Notifications\Notification::make()
                            ->title('Callback Marked as Processed')
                            ->success()
                            ->send();
                    })
                    ->requiresConfirmation()
                    ->visible(fn (PaymentCallback $record): bool => empty($record->processed_at)),
                Tables\Actions\Action::make('view_payload')
                    ->label('View Payload')
                    ->icon('heroicon-o-document-text')
                    ->color('secondary')
                    ->modalContent(fn (PaymentCallback $record): \Illuminate\Contracts\View\View => view(
                        'filament.pages.payload-viewer', 
                        ['payload' => $record->formatted_payload]
                    ))
                    ->modalWidth('5xl'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\BulkAction::make('bulk_verify')
                        ->label('Mark as Verified')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $record->markAsVerified();
                                
                                \App\Models\AdminActivityLog::createLog(
                                    auth('admin')->id(),
                                    'bulk_callback_verified',
                                    "Bulk verified payment callback {$record->id}"
                                );
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Callbacks Marked as Verified')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                    Tables\Actions\BulkAction::make('bulk_process')
                        ->label('Mark as Processed')
                        ->icon('heroicon-o-cog-6-tooth')
                        ->color('info')
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            foreach ($records as $record) {
                                $record->markAsProcessed();
                                
                                \App\Models\AdminActivityLog::createLog(
                                    auth('admin')->id(),
                                    'bulk_callback_processed',
                                    "Bulk processed payment callback {$record->id}"
                                );
                            }

                            \Filament\Notifications\Notification::make()
                                ->title('Callbacks Marked as Processed')
                                ->success()
                                ->send();
                        })
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('received_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Callback Information')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction_id')
                                    ->label('Transaction ID')
                                    ->copyable(),
                                Infolists\Components\TextEntry::make('gateway_transaction_id')
                                    ->label('Gateway Transaction ID')
                                    ->copyable(),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('gateway_status')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'settlement', 'capture' => 'success',
                                        'pending' => 'warning',
                                        'deny', 'cancel', 'expire', 'failure' => 'danger',
                                        default => 'secondary',
                                    }),
                                Infolists\Components\IconEntry::make('verified')
                                    ->boolean()
                                    ->trueIcon('heroicon-o-check-circle')
                                    ->falseIcon('heroicon-o-x-circle')
                                    ->trueColor('success')
                                    ->falseColor('danger'),
                            ]),
                        Infolists\Components\TextEntry::make('signature')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                
                Infolists\Components\Section::make('Related Transaction')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction.user.email')
                                    ->label('User Email'),
                                Infolists\Components\TextEntry::make('transaction.amount')
                                    ->label('Transaction Amount')
                                    ->money('IDR'),
                            ]),
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('transaction.type')
                                    ->label('Transaction Type')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('transaction.status')
                                    ->label('Transaction Status')
                                    ->badge(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\Grid::make(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('received_at')
                                    ->dateTime(),
                                Infolists\Components\TextEntry::make('processed_at')
                                    ->dateTime(),
                            ]),
                    ]),

                Infolists\Components\Section::make('Raw Payload')
                    ->schema([
                        Infolists\Components\TextEntry::make('formatted_payload')
                            ->label('')
                            ->columnSpanFull()
                            ->view('filament.infolists.payload-entry'),
                    ])
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
            'index' => Pages\ListPaymentCallbacks::route('/'),
            'create' => Pages\CreatePaymentCallback::route('/create'),
            'view' => Pages\ViewPaymentCallback::route('/{record}'),
            'edit' => Pages\EditPaymentCallback::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('verified', false)->count();
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['transaction_id', 'gateway_transaction_id'];
    }
}
