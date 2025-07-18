<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            System Health Status
        </x-slot>

        <div class="space-y-4">
            {{-- Overall Status --}}
            <div class="flex items-center justify-between p-4 rounded-lg border
                @if($systemHealth['overall'] === 'healthy') border-green-200 bg-green-50 @endif
                @if($systemHealth['overall'] === 'degraded') border-yellow-200 bg-yellow-50 @endif
                @if($systemHealth['overall'] === 'unhealthy') border-red-200 bg-red-50 @endif
            ">
                <div class="flex items-center space-x-3">
                    @if($systemHealth['overall'] === 'healthy')
                        <x-heroicon-o-check-circle class="w-8 h-8 text-green-600" />
                    @elseif($systemHealth['overall'] === 'degraded')
                        <x-heroicon-o-exclamation-triangle class="w-8 h-8 text-yellow-600" />
                    @else
                        <x-heroicon-o-x-circle class="w-8 h-8 text-red-600" />
                    @endif
                    <div>
                        <h3 class="text-lg font-semibold
                            @if($systemHealth['overall'] === 'healthy') text-green-800 @endif
                            @if($systemHealth['overall'] === 'degraded') text-yellow-800 @endif
                            @if($systemHealth['overall'] === 'unhealthy') text-red-800 @endif
                        ">
                            System Status: {{ ucfirst($systemHealth['overall']) }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            Last checked: {{ \Carbon\Carbon::parse($systemHealth['last_check'])->diffForHumans() }}
                        </p>
                    </div>
                </div>
                <button 
                    wire:click="$refresh"
                    class="px-3 py-1 text-sm bg-gray-100 hover:bg-gray-200 rounded-md transition-colors"
                >
                    Refresh
                </button>
            </div>

            {{-- Service Details --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                @foreach($systemHealth['services'] as $serviceName => $service)
                    <div class="p-4 border rounded-lg
                        @if($service['status'] === 'healthy') border-green-200 bg-green-50 @endif
                        @if($service['status'] === 'degraded') border-yellow-200 bg-yellow-50 @endif
                        @if($service['status'] === 'unhealthy' || $service['status'] === 'unreachable') border-red-200 bg-red-50 @endif
                    ">
                        <div class="flex items-center justify-between mb-2">
                            <h4 class="font-medium capitalize">{{ str_replace('_', ' ', $serviceName) }}</h4>
                            @if($service['status'] === 'healthy')
                                <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
                            @elseif($service['status'] === 'degraded')
                                <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600" />
                            @else
                                <x-heroicon-o-x-circle class="w-5 h-5 text-red-600" />
                            @endif
                        </div>
                        
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Status:</span>
                                <span class="font-medium
                                    @if($service['status'] === 'healthy') text-green-600 @endif
                                    @if($service['status'] === 'degraded') text-yellow-600 @endif
                                    @if($service['status'] === 'unhealthy' || $service['status'] === 'unreachable') text-red-600 @endif
                                ">
                                    {{ ucfirst($service['status']) }}
                                </span>
                            </div>
                            
                            @if($service['response_time'])
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Response Time:</span>
                                    <span class="font-medium">{{ $service['response_time'] }}ms</span>
                                </div>
                            @endif
                            
                            <div class="mt-2">
                                <p class="text-xs text-gray-500">{{ $service['message'] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
