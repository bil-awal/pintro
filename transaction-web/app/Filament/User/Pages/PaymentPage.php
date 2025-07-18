<?php

namespace App\Filament\User\Pages;

use Filament\Pages\Page;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;

class PaymentPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';
    
    protected static string $view = 'filament.user.pages.payment';

    protected static ?string $title = 'Make Payment';

    protected static ?string $navigationLabel = 'Make Payment';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationGroup = 'Transactions';

    public $currentBalance = 0;

    public function mount(): void
    {
        $this->loadCurrentBalance();
    }

    protected function loadCurrentBalance(): void
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');
            
            if ($token) {
                $this->currentBalance = $goService->getAuthenticatedUserBalance($token) ?? 0;
            }
        } catch (\Exception $e) {
            $this->currentBalance = 0;
        }
    }

    public function paymentAction(): Action
    {
        return Action::make('payment')
            ->label('Create Payment')
            ->icon('heroicon-o-credit-card')
            ->color('primary')
            ->size('lg')
            ->form([
                TextInput::make('amount')
                    ->label('Payment Amount')
                    ->numeric()
                    ->required()
                    ->minValue(1000)
                    ->maxValue(min($this->currentBalance, 50000000))
                    ->prefix('Rp')
                    ->placeholder('Enter payment amount')
                    ->helperText('Available balance: Rp ' . number_format($this->currentBalance, 0, ',', '.')),

                TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('What is this payment for?')
                    ->helperText('Describe the purpose of this payment'),

                Select::make('category')
                    ->label('Category')
                    ->options([
                        'food' => 'Food & Drinks',
                        'transport' => 'Transportation',
                        'shopping' => 'Shopping',
                        'entertainment' => 'Entertainment',
                        'bills' => 'Bills & Utilities',
                        'health' => 'Health & Medical',
                        'education' => 'Education',
                        'other' => 'Other',
                    ])
                    ->required()
                    ->placeholder('Select payment category'),

                Select::make('recipient_type')
                    ->label('Payment To')
                    ->options([
                        'merchant' => 'Merchant/Store',
                        'user' => 'Another User',
                    ])
                    ->required()
                    ->live()
                    ->placeholder('Who are you paying?'),

                TextInput::make('recipient_identifier')
                    ->label(fn (callable $get) => $get('recipient_type') === 'user' ? 'User Email/Phone' : 'Merchant ID')
                    ->required()
                    ->placeholder(fn (callable $get) => $get('recipient_type') === 'user' ? 'Enter email or phone number' : 'Enter merchant ID')
                    ->visible(fn (callable $get) => filled($get('recipient_type'))),

                Textarea::make('notes')
                    ->label('Additional Notes (Optional)')
                    ->rows(3)
                    ->placeholder('Any additional information about this payment'),
            ])
            ->action(function (array $data): void {
                $this->processPayment($data);
            });
    }

    protected function processPayment(array $data): void
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');
            $userData = Session::get('go_user_data');

            if (!$token) {
                Notification::make()
                    ->title('Authentication Error')
                    ->body('Please login again to continue.')
                    ->danger()
                    ->send();
                return;
            }

            // Check balance first
            $currentBalance = $goService->getAuthenticatedUserBalance($token);
            $requestedAmount = $data['amount'];

            if ($currentBalance === null) {
                Notification::make()
                    ->title('Balance Check Failed')
                    ->body('Unable to verify your balance. Please try again.')
                    ->danger()
                    ->send();
                return;
            }

            if ($currentBalance < $requestedAmount) {
                Notification::make()
                    ->title('Insufficient Balance')
                    ->body('Your current balance (Rp ' . number_format($currentBalance, 0, ',', '.') . ') is insufficient for this payment.')
                    ->danger()
                    ->send();
                return;
            }

            $paymentData = [
                'amount' => $requestedAmount,
                'description' => $data['description'],
                'category' => $data['category'],
                'recipient_type' => $data['recipient_type'],
                'recipient_identifier' => $data['recipient_identifier'],
                'notes' => $data['notes'] ?? '',
                'customer_details' => [
                    'first_name' => $userData['name'] ?? 'User',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                ],
            ];

            $result = $goService->createAuthenticatedPayment($token, $paymentData);

            if ($result) {
                $this->loadCurrentBalance(); // Refresh balance

                Notification::make()
                    ->title('Payment Successful')
                    ->body('Your payment of Rp ' . number_format($requestedAmount, 0, ',', '.') . ' has been processed successfully.')
                    ->success()
                    ->send();

                // Redirect to transaction details if available
                if (isset($result['transaction_id'])) {
                    // Could redirect to transaction details page
                }
            } else {
                Notification::make()
                    ->title('Payment Failed')
                    ->body('Unable to process your payment. Please try again.')
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while processing your payment.')
                ->danger()
                ->send();
        }
    }

    public function refreshBalance(): Action
    {
        return Action::make('refreshBalance')
            ->label('Refresh Balance')
            ->icon('heroicon-o-arrow-path')
            ->color('gray')
            ->action(function (): void {
                $this->loadCurrentBalance();
                
                Notification::make()
                    ->title('Balance Updated')
                    ->body('Your current balance has been refreshed.')
                    ->success()
                    ->send();
            });
    }

    public function quickPayment($amount): void
    {
        // This method can be called from the frontend for quick payments
        $this->dispatch('open-modal', id: 'payment-modal');
    }
}
