<div class="space-y-4">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        {{-- Basic Information --}}
        <div class="space-y-3">
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Transaction Information</h4>
            
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Transaction ID:</span>
                    <span class="text-sm font-medium">{{ $transaction['id'] ?? 'N/A' }}</span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Date:</span>
                    <span class="text-sm font-medium">
                        {{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y H:i') : 'N/A' }}
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Type:</span>
                    <span class="text-sm font-medium">
                        <x-filament::badge 
                            :color="match($transaction['type'] ?? '') {
                                'topup' => 'success',
                                'payment' => 'primary',
                                'transfer' => 'info',
                                default => 'gray'
                            }"
                        >
                            {{ ucfirst($transaction['type'] ?? 'Unknown') }}
                        </x-filament::badge>
                    </span>
                </div>
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Status:</span>
                    <span class="text-sm font-medium">
                        <x-filament::badge 
                            :color="match($transaction['status'] ?? '') {
                                'pending' => 'warning',
                                'processing' => 'info',
                                'completed', 'success' => 'success',
                                'failed', 'cancelled' => 'danger',
                                default => 'gray'
                            }"
                        >
                            {{ ucfirst($transaction['status'] ?? 'Unknown') }}
                        </x-filament::badge>
                    </span>
                </div>
            </div>
        </div>

        {{-- Financial Information --}}
        <div class="space-y-3">
            <h4 class="font-semibold text-gray-900 dark:text-gray-100">Financial Details</h4>
            
            <div class="space-y-2">
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Amount:</span>
                    <span class="text-lg font-bold {{ ($transaction['type'] ?? '') === 'topup' ? 'text-green-600' : 'text-red-600' }}">
                        {{ ($transaction['type'] ?? '') === 'topup' ? '+' : '-' }}Rp {{ number_format($transaction['amount'] ?? 0, 0, ',', '.') }}
                    </span>
                </div>
                
                @if(isset($transaction['fee']) && $transaction['fee'] > 0)
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Fee:</span>
                    <span class="text-sm font-medium text-red-600">
                        Rp {{ number_format($transaction['fee'], 0, ',', '.') }}
                    </span>
                </div>
                @endif
                
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Currency:</span>
                    <span class="text-sm font-medium">{{ $transaction['currency'] ?? 'IDR' }}</span>
                </div>
                
                @if(!empty($transaction['reference']))
                <div class="flex justify-between">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Reference:</span>
                    <span class="text-sm font-medium font-mono">{{ $transaction['reference'] }}</span>
                </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Description --}}
    @if(!empty($transaction['description']))
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Description</h4>
        <p class="text-sm text-gray-600 dark:text-gray-400">{{ $transaction['description'] }}</p>
    </div>
    @endif

    {{-- Metadata --}}
    @if(!empty($transaction['metadata']))
    <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
        <h4 class="font-semibold text-gray-900 dark:text-gray-100 mb-2">Additional Information</h4>
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3">
            <pre class="text-xs text-gray-600 dark:text-gray-400 overflow-auto">{{ json_encode($transaction['metadata'], JSON_PRETTY_PRINT) }}</pre>
        </div>
    </div>
    @endif
</div>
