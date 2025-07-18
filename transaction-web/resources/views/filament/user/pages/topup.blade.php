<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current Balance Card --}}
        <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold opacity-90">Current Balance</h3>
                    <p class="text-3xl font-bold mt-1">
                        Rp {{ number_format($this->currentBalance, 0, ',', '.') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    {{ $this->refreshBalance }}
                    <div class="bg-white/20 rounded-lg p-3">
                        <x-heroicon-o-wallet class="h-8 w-8 text-white" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Top-up Options --}}
        <x-filament::section>
            <x-slot name="heading">
                Quick Top-up
            </x-slot>
            
            <x-slot name="description">
                Choose from predefined amounts for quick top-up
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    '50000' => 'Rp 50K',
                    '100000' => 'Rp 100K',
                    '200000' => 'Rp 200K',
                    '500000' => 'Rp 500K',
                    '1000000' => 'Rp 1M',
                    '2000000' => 'Rp 2M',
                    '5000000' => 'Rp 5M',
                    'custom' => 'Custom'
                ] as $amount => $label)
                    <x-filament::button
                        color="{{ $amount === 'custom' ? 'primary' : 'gray' }}"
                        size="lg"
                        class="h-16 text-center"
                        wire:click="{{ $amount === 'custom' ? 'mountAction(\'topup\')' : 'quickTopup(\'' . $amount . '\')' }}"
                    >
                        <div class="text-center">
                            @if($amount === 'custom')
                                <x-heroicon-o-pencil class="h-5 w-5 mx-auto mb-1" />
                            @else
                                <x-heroicon-o-plus class="h-5 w-5 mx-auto mb-1" />
                            @endif
                            <div class="font-semibold">{{ $label }}</div>
                        </div>
                    </x-filament::button>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Custom Top-up Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Custom Top-up
            </x-slot>
            
            <x-slot name="description">
                Create a custom top-up request with specific amount and payment method
            </x-slot>

            <div class="flex justify-center">
                {{ $this->topupAction }}
            </div>
        </x-filament::section>

        {{-- Payment Methods Info --}}
        <x-filament::section>
            <x-slot name="heading">
                Supported Payment Methods
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    'credit_card' => ['Credit Card', 'heroicon-o-credit-card', 'Visa, Mastercard, JCB'],
                    'va_bca' => ['BCA Virtual Account', 'heroicon-o-building-library', 'Real-time transfer'],
                    'va_bni' => ['BNI Virtual Account', 'heroicon-o-building-library', 'Real-time transfer'],
                    'va_bri' => ['BRI Virtual Account', 'heroicon-o-building-library', 'Real-time transfer'],
                    'gopay' => ['GoPay', 'heroicon-o-device-phone-mobile', 'E-wallet payment'],
                    'shopeepay' => ['ShopeePay', 'heroicon-o-device-phone-mobile', 'E-wallet payment'],
                ] as $method => $info)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700">
                        <div class="flex items-center space-x-3">
                            <div class="bg-primary-100 dark:bg-primary-900 rounded-lg p-2">
                                @php $iconClass = $info[1]; @endphp
                                <x-dynamic-component :component="$iconClass" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $info[0] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400">{{ $info[2] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Important Notes --}}
        <x-filament::section>
            <x-slot name="heading">
                Important Notes
            </x-slot>

            <div class="prose dark:prose-invert max-w-none">
                <ul class="text-sm space-y-2">
                    <li class="flex items-start space-x-2">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span>Minimum top-up amount is Rp 10.000</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span>Maximum top-up amount is Rp 10.000.000</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span>Top-up via Virtual Account is processed instantly</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span>E-wallet payments may take 1-2 minutes to process</span>
                    </li>
                    <li class="flex items-start space-x-2">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mt-0.5 flex-shrink-0" />
                        <span>You will receive confirmation once payment is completed</span>
                    </li>
                </ul>
            </div>
        </x-filament::section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

<script>
    function quickTopup(amount) {
        Livewire.dispatch('quickTopup', { amount: amount });
    }
</script>
