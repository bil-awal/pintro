<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\Widget;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;

class QuickActions extends Widget implements HasForms, HasActions
{
    use InteractsWithForms;
    use InteractsWithActions;

    protected static string $view = 'filament.user.widgets.quick-actions';

    protected static ?int $sort = 2;

    protected int | string | array $columnSpan = 'full';

    public function topupAction(): Action
    {
        return Action::make('topup')
            ->label('Top-up Balance')
            ->icon('heroicon-o-plus-circle')
            ->color('success')
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
                    ->required()
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

    public function paymentAction(): Action
    {
        return Action::make('payment')
            ->label('Make Payment')
            ->icon('heroicon-o-credit-card')
            ->color('primary')
            ->form([
                TextInput::make('amount')
                    ->label('Payment Amount')
                    ->numeric()
                    ->required()
                    ->minValue(1000)
                    ->maxValue(50000000)
                    ->prefix('Rp')
                    ->placeholder('Enter payment amount'),

                TextInput::make('description')
                    ->label('Description')
                    ->required()
                    ->maxLength(255)
                    ->placeholder('What is this payment for?'),

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
            ])
            ->action(function (array $data): void {
                $this->processPayment($data);
            });
    }

    protected function processTopup(array $data): void
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');
            $userData = Session::get('user_data');

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
                Notification::make()
                    ->title('Top-up Created Successfully')
                    ->body('Your top-up request has been created. You will be redirected to payment page.')
                    ->success()
                    ->send();

                // Redirect to payment page if needed
                if (isset($result['payment_url'])) {
                    $this->redirect($result['payment_url']);
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

    protected function processPayment(array $data): void
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');
            $userData = Session::get('user_data');

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
            
            if ($currentBalance === null) {
                Notification::make()
                    ->title('Balance Check Failed')
                    ->body('Unable to verify your balance. Please try again.')
                    ->danger()
                    ->send();
                return;
            }

            if ($currentBalance < $data['amount']) {
                Notification::make()
                    ->title('Insufficient Balance')
                    ->body('Your current balance (Rp ' . number_format($currentBalance, 0, ',', '.') . ') is insufficient for this payment.')
                    ->danger()
                    ->send();
                return;
            }

            $paymentData = [
                'amount' => $data['amount'],
                'description' => $data['description'],
                'category' => $data['category'],
                'recipient_type' => $data['recipient_type'],
                'recipient_identifier' => $data['recipient_identifier'],
                'customer_details' => [
                    'first_name' => $userData['name'] ?? 'User',
                    'email' => $userData['email'] ?? '',
                    'phone' => $userData['phone'] ?? '',
                ],
            ];

            $result = $goService->createAuthenticatedPayment($token, $paymentData);

            if ($result) {
                Notification::make()
                    ->title('Payment Successful')
                    ->body('Your payment has been processed successfully.')
                    ->success()
                    ->send();

                // Refresh the page to update balance
                $this->redirect(request()->url());
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
}
