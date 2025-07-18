<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Section --}}
        <div class="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold mb-2">Welcome to Pintro Financial Admin</h1>
                    <p class="text-blue-100">
                        Monitor and manage your financial transaction system
                    </p>
                </div>
                <div class="hidden md:block">
                    <x-heroicon-o-banknotes class="w-16 h-16 text-blue-200" />
                </div>
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="bg-white rounded-lg border p-6">
            <h2 class="text-lg font-semibold mb-4">Quick Actions</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('filament.admin.resources.transactions.index') }}" 
                   class="flex items-center p-4 bg-blue-50 hover:bg-blue-100 rounded-lg transition-colors">
                    <x-heroicon-o-banknotes class="w-8 h-8 text-blue-600 mr-3" />
                    <div>
                        <div class="font-medium text-blue-900">View Transactions</div>
                        <div class="text-sm text-blue-600">Manage all transactions</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.users.index') }}" 
                   class="flex items-center p-4 bg-green-50 hover:bg-green-100 rounded-lg transition-colors">
                    <x-heroicon-o-users class="w-8 h-8 text-green-600 mr-3" />
                    <div>
                        <div class="font-medium text-green-900">Manage Users</div>
                        <div class="text-sm text-green-600">User administration</div>
                    </div>
                </a>
                
                <a href="{{ route('filament.admin.resources.payment-callbacks.index') }}" 
                   class="flex items-center p-4 bg-yellow-50 hover:bg-yellow-100 rounded-lg transition-colors">
                    <x-heroicon-o-arrow-path class="w-8 h-8 text-yellow-600 mr-3" />
                    <div>
                        <div class="font-medium text-yellow-900">Payment Callbacks</div>
                        <div class="text-sm text-yellow-600">Monitor payments</div>
                    </div>
                </a>
                
                <button onclick="window.location.reload()" 
                        class="flex items-center p-4 bg-purple-50 hover:bg-purple-100 rounded-lg transition-colors">
                    <x-heroicon-o-arrow-path class="w-8 h-8 text-purple-600 mr-3" />
                    <div>
                        <div class="font-medium text-purple-900">Refresh Data</div>
                        <div class="text-sm text-purple-600">Update dashboard</div>
                    </div>
                </button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
