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
use Filament\Notifications\Notification;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;

class TopupPage extends Page implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static ?string $navigationIcon = 'heroicon-o-plus-circle';
    
    protected static string $view = 'filament.user.pages.topup';

    protected static ?string $title = 'Top-up Balance';

    protected static ?string $navigationLabel = 'Top-up Balance';

    protected static ?int $navigationSort = 3;

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

    public function topupAction(): Action
    {
        return Action::make('topup')
            ->label('Create Top-up Request')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
            ->size('lg')
            ->form([
                Select::make('amount')
                    ->label('Select Amount')
                    ->options([
                        '50000' => 'Rp 50.000',
                        '100000' => 'Rp 100.000',
                        '200000' => 'Rp 200.000',
                        '500000' => 'Rp 500.000',
                        '1000000' => 'Rp 1.000.000',
                        '2000000' => 'Rp 2.000.000',
                        '5000000' => 'Rp 5.000.000',
                    ])
                    ->searchable()
                    ->placeholder('Choose top-up amount'),
                
                TextInput::make('custom_amount')
                    ->label('Or Enter Custom Amount')
                    ->numeric()
                    ->minValue(10000)
                    ->maxValue(10000000)
                    ->prefix('Rp')
                    ->placeholder('Enter amount (min: 10,000)')
                    ->helperText('Leave empty if using predefined amount above'),

                Select::make('payment_method')
                    ->label('Payment Method')
                    ->options([
                        'credit_card' => 'Credit Card',
                        'va_bca' => 'BCA Virtual Account',
                        'va_bni' => 'BNI Virtual Account',
                        'va_bri' => 'BRI Virtual Account',
                        'gopay' => 'GoPay',
                        'shopeepay' => 'ShopeePay',
                    ])
                    ->required()
                    ->placeholder('Select payment method'),
            ])
            ->action(function (array $data): void {
                $this->processTopup($data);
            });
    }

    protected function processTopup(array $data): void
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

            // Determine final amount
            $amount = $data['custom_amount'] ?: $data['amount'];

            $topupData = [
                'amount' => $amount,
                'payment_method' => $data['payment_method'],
                'description' => 'Balance top-up via ' . $this->getPaymentMethodName($data['payment_method']),
                'customer_details' => [
                    'first_name' => $userData['name'] ?? 'User',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                ],
            ];

            $result = $goService->createAuthenticatedTopup($token, $topupData);

            if ($result) {
                $this->loadCurrentBalance(); // Refresh balance

                Notification::make()
                    ->title('Top-up Created Successfully')
                    ->body('Your top-up request for Rp ' . number_format($amount, 0, ',', '.') . ' has been created.')
                    ->success()
                    ->send();

                // Redirect to payment page if needed
                if (isset($result['payment_url'])) {
                    redirect($result['payment_url']);
                }
            } else {
                Notification::make()
                    ->title('Top-up Failed')
                    ->body('Unable to create top-up request. Please try again.')
                    ->danger()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body('An error occurred while processing your top-up request.')
                ->danger()
                ->send();
        }
    }

    private function getPaymentMethodName(string $method): string
    {
        $methods = [
            'credit_card' => 'Credit Card',
            'va_bca' => 'BCA Virtual Account',
            'va_bni' => 'BNI Virtual Account',
            'va_bri' => 'BRI Virtual Account',
            'gopay' => 'GoPay',
            'shopeepay' => 'ShopeePay',
        ];

        return $methods[$method] ?? $method;
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
}
