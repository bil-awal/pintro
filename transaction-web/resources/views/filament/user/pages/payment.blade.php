<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Current Balance Card --}}
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-semibold opacity-90">Available Balance</h3>
                    <p class="text-3xl font-bold mt-1">
                        Rp {{ number_format($this->currentBalance, 0, ',', '.') }}
                    </p>
                    <p class="text-sm opacity-75 mt-1">Ready for payments</p>
                </div>
                <div class="flex items-center space-x-3">
                    {{ $this->refreshBalance }}
                    <div class="bg-white/20 rounded-lg p-3">
                        <x-heroicon-o-credit-card class="h-8 w-8 text-white" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Quick Payment Categories --}}
        <x-filament::section>
            <x-slot name="heading">
                Payment Categories
            </x-slot>
            
            <x-slot name="description">
                Quick access to common payment types
            </x-slot>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach([
                    'food' => ['Food & Drinks', 'heroicon-o-cake', 'bg-red-50 border-red-200 text-red-600'],
                    'transport' => ['Transportation', 'heroicon-o-truck', 'bg-blue-50 border-blue-200 text-blue-600'],
                    'shopping' => ['Shopping', 'heroicon-o-shopping-bag', 'bg-pink-50 border-pink-200 text-pink-600'],
                    'bills' => ['Bills & Utilities', 'heroicon-o-document-text', 'bg-yellow-50 border-yellow-200 text-yellow-600'],
                    'entertainment' => ['Entertainment', 'heroicon-o-film', 'bg-purple-50 border-purple-200 text-purple-600'],
                    'health' => ['Health & Medical', 'heroicon-o-heart', 'bg-green-50 border-green-200 text-green-600'],
                    'education' => ['Education', 'heroicon-o-academic-cap', 'bg-indigo-50 border-indigo-200 text-indigo-600'],
                    'other' => ['Other', 'heroicon-o-ellipsis-horizontal', 'bg-gray-50 border-gray-200 text-gray-600'],
                ] as $category => $info)
                    <div class="border-2 rounded-lg p-4 hover:shadow-md transition-shadow cursor-pointer {{ $info[2] }}"
                         wire:click="$dispatch('openCategoryPayment', { category: '{{ $category }}' })">
                        <div class="text-center">
                            @php $iconClass = $info[1]; @endphp
                            <x-dynamic-component :component="$iconClass" class="h-8 w-8 mx-auto mb-2" />
                            <h4 class="font-semibold text-sm">{{ $info[0] }}</h4>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>

        {{-- Create Payment Form --}}
        <x-filament::section>
            <x-slot name="heading">
                Create New Payment
            </x-slot>
            
            <x-slot name="description">
                Fill in the details for your payment transaction
            </x-slot>

            <div class="flex justify-center">
                {{ $this->paymentAction }}
            </div>
        </x-filament::section>

        {{-- Payment Safety Tips --}}
        <x-filament::section>
            <x-slot name="heading">
                Payment Safety Tips
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-shield-check class="h-5 w-5 text-green-500 mr-2" />
                        Security Tips
                    </h4>
                    <ul class="text-sm space-y-2 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>Verify recipient details before confirming payment</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>Double-check payment amount and description</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>Keep transaction records for your reference</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-check-circle class="h-4 w-4 text-green-500 mt-0.5 flex-shrink-0" />
                            <span>Report suspicious activities immediately</span>
                        </li>
                    </ul>
                </div>

                <div class="space-y-4">
                    <h4 class="font-semibold text-gray-900 dark:text-gray-100 flex items-center">
                        <x-heroicon-o-information-circle class="h-5 w-5 text-blue-500 mr-2" />
                        Payment Limits
                    </h4>
                    <ul class="text-sm space-y-2 text-gray-600 dark:text-gray-400">
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-banknotes class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                            <span>Minimum payment: Rp 1.000</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-banknotes class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                            <span>Maximum per transaction: Rp 50.000.000</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-clock class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                            <span>Payments are processed instantly</span>
                        </li>
                        <li class="flex items-start space-x-2">
                            <x-heroicon-o-receipt-refund class="h-4 w-4 text-blue-500 mt-0.5 flex-shrink-0" />
                            <span>Transaction history available 24/7</span>
                        </li>
                    </ul>
                </div>
            </div>
        </x-filament::section>

        {{-- Recent Merchant Payments (Mock Data) --}}
        <x-filament::section>
            <x-slot name="heading">
                Popular Merchants
            </x-slot>
            
            <x-slot name="description">
                Frequently used payment destinations
            </x-slot>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach([
                    ['name' => 'Starbucks Coffee', 'category' => 'food', 'icon' => 'heroicon-o-cup'],
                    ['name' => 'Grab Transport', 'category' => 'transport', 'icon' => 'heroicon-o-truck'],
                    ['name' => 'Tokopedia', 'category' => 'shopping', 'icon' => 'heroicon-o-shopping-cart'],
                    ['name' => 'PLN (Electricity)', 'category' => 'bills', 'icon' => 'heroicon-o-bolt'],
                    ['name' => 'Netflix', 'category' => 'entertainment', 'icon' => 'heroicon-o-play'],
                    ['name' => 'Shopee', 'category' => 'shopping', 'icon' => 'heroicon-o-shopping-bag'],
                ] as $merchant)
                    <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 hover:shadow-md transition-shadow cursor-pointer">
                        <div class="flex items-center space-x-3">
                            <div class="bg-primary-100 dark:bg-primary-900 rounded-lg p-2">
                                @php $iconClass = $merchant['icon']; @endphp
                                <x-dynamic-component :component="$iconClass" class="h-6 w-6 text-primary-600 dark:text-primary-400" />
                            </div>
                            <div>
                                <h4 class="font-semibold text-gray-900 dark:text-gray-100">{{ $merchant['name'] }}</h4>
                                <p class="text-sm text-gray-600 dark:text-gray-400 capitalize">{{ $merchant['category'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-filament::section>
    </div>

    <x-filament-actions::modals />
</x-filament-panels::page>

<script>
    function openCategoryPayment(category) {
        // This could pre-fill the payment form with the selected category
        Livewire.dispatch('openCategoryPayment', { category: category });
    }
</script>
