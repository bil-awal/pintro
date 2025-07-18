<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Quick Actions
        </x-slot>
        
        <x-slot name="description">
            Perform common transaction actions quickly
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            {{-- Top-up Button --}}
            <div class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 rounded-lg p-6 border border-green-200 dark:border-green-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-green-500 rounded-lg p-2">
                            <x-heroicon-o-plus-circle class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-green-900 dark:text-green-100">Top-up Balance</h3>
                            <p class="text-sm text-green-600 dark:text-green-300">Add money to your account</p>
                        </div>
                    </div>
                </div>
                
                {{ $this->topupAction }}
            </div>

            {{-- Payment Button --}}
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between mb-4">
                    <div class="flex items-center space-x-3">
                        <div class="bg-blue-500 rounded-lg p-2">
                            <x-heroicon-o-credit-card class="h-6 w-6 text-white" />
                        </div>
                        <div>
                            <h3 class="font-semibold text-blue-900 dark:text-blue-100">Make Payment</h3>
                            <p class="text-sm text-blue-600 dark:text-blue-300">Pay for goods and services</p>
                        </div>
                    </div>
                </div>
                
                {{ $this->paymentAction }}
            </div>
        </div>

        {{-- Additional Quick Links --}}
        <div class="mt-6 flex flex-wrap gap-3">
            <x-filament::button
                tag="a"
                href="#"
                color="gray"
                size="sm"
                icon="heroicon-o-list-bullet"
            >
                View All Transactions
            </x-filament::button>
            
            <x-filament::button
                tag="a"
                href="#"
                color="gray"
                size="sm"
                icon="heroicon-o-document-arrow-down"
            >
                Export History
            </x-filament::button>
            
            <x-filament::button
                tag="a"
                href="#"
                color="gray"
                size="sm"
                icon="heroicon-o-cog-6-tooth"
            >
                Account Settings
            </x-filament::button>
        </div>
    </x-filament::section>

    <x-filament-actions::modals />
</x-filament-widgets::widget>
