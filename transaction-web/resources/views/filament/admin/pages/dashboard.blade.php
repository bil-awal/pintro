<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            Welcome to Admin Dashboard
        </x-slot>
        
        <x-slot name="description">
            Manage your Pintro Financial system from here.
        </x-slot>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900">Total Users</h3>
                <p class="text-3xl font-bold text-indigo-600">{{ App\Models\User::count() }}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900">Total Transactions</h3>
                <p class="text-3xl font-bold text-green-600">{{ App\Models\Transaction::count() ?? 0 }}</p>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="text-lg font-medium text-gray-900">System Status</h3>
                <p class="text-3xl font-bold text-green-600">Online</p>
            </div>
        </div>
    </x-filament::section>
</x-filament-panels::page>
