<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Welcome Message --}}
        <div class="bg-gradient-to-r from-primary-500 to-primary-600 rounded-xl p-6 text-white">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold">
                        Welcome back, {{ auth()->user()->name ?? 'User' }}!
                    </h2>
                    <p class="text-primary-100 mt-1">
                        Manage your transactions and monitor your financial activities.
                    </p>
                </div>
                <div class="hidden md:block">
                    <div class="bg-white/20 rounded-lg p-4">
                        <x-heroicon-o-wallet class="h-12 w-12 text-white" />
                    </div>
                </div>
            </div>
        </div>

        {{-- Dashboard Widgets --}}
        {{ $this->getWidgets() }}
    </div>
</x-filament-panels::page>
