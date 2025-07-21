<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Pintro</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100 min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg min-h-screen">
            <div class="p-6">
                <h1 class="text-2xl font-bold text-gray-800">Pintro Admin</h1>
                <p class="text-gray-600 text-sm">Transaction Management</p>
            </div>
            
            <nav class="mt-6">
                <a href="{{ route('sysadmin.dashboard') }}" class="flex items-center px-6 py-3 text-gray-700 bg-gray-100 border-r-4 border-blue-500">
                    <i class="fas fa-tachometer-alt mr-3"></i>
                    Dashboard
                </a>
                <a href="{{ route('sysadmin.transactions.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-exchange-alt mr-3"></i>
                    Transactions
                </a>
                <a href="{{ route('sysadmin.user.profile') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-users mr-3"></i>
                    Users
                </a>
                <a href="{{ route('sysadmin.topup.index') }}" class="flex items-center px-6 py-3 text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-plus-circle mr-3"></i>
                    Top-up
                </a>
            </nav>
            
            <div class="absolute bottom-0 w-64 p-6">
                <div class="text-sm text-gray-600">
                    <p><strong>{{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? 'User' }}</strong></p>
                    <p>{{ $admin['email'] ?? 'admin@example.com' }}</p>
                </div>
                
                <form method="POST" action="{{ route('sysadmin.logout') }}" class="mt-4">
                    @csrf
                    <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg transition-colors">
                        <i class="fas fa-sign-out-alt mr-2"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Main Content -->
        <div class="flex-1 p-6">
            <!-- Header -->
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-3xl font-bold text-gray-800">Dashboard</h2>
                        <p class="text-gray-600">Welcome back, {{ $admin['first_name'] ?? 'Admin' }}!</p>
                    </div>
                    <div class="text-right">
                        <p class="text-sm text-gray-500">Last updated: {{ $last_updated ?? now()->format('Y-m-d H:i:s') }}</p>
                        <div class="flex items-center mt-2">
                            @if(isset($system_health['status']) && $system_health['status'] === 'healthy')
                                <span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-2"></span>
                                <span class="text-green-600 text-sm">System Healthy</span>
                            @else
                                <span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-2"></span>
                                <span class="text-red-600 text-sm">System Issues</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-exchange-alt text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Transactions</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['total_transactions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Completed</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['completed_transactions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Pending</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['pending_transactions'] ?? 0 }}</p>
                        </div>
                    </div>
                </div>
                
                <div class="bg-white rounded-lg shadow p-6">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-money-bill-wave text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <p class="text-sm text-gray-600">Total Amount</p>
                            <p class="text-lg font-bold text-gray-900">{{ $stats['formatted_total_amount'] ?? 'Rp 0' }}</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Charts Row -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                <!-- Transaction Types Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Transaction Types</h3>
                </div>
                
                <!-- Daily Transactions Chart -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Daily Activity</h3>
                    <div class="grid grid-cols-3 gap-4 text-center">
                        <div>
                            <p class="text-2xl font-bold text-blue-600">{{ $stats['daily_transactions'] ?? 0 }}</p>
                            <p class="text-sm text-gray-600">Today</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-purple-600">{{ $stats['weekly_transactions'] ?? 0 }}</p>
                            <p class="text-sm text-gray-600">This Week</p>
                        </div>
                        <div>
                            <p class="text-2xl font-bold text-green-600">{{ $stats['monthly_transactions'] ?? 0 }}</p>
                            <p class="text-sm text-gray-600">This Month</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Transactions -->
            <div class="bg-white rounded-lg shadow">
                <div class="p-6 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Recent Transactions</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @if(isset($recent_transactions['transactions']) && count($recent_transactions['transactions']) > 0)
                                @foreach($recent_transactions['transactions'] as $transaction)
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ substr($transaction['id'] ?? 'N/A', 0, 8) }}...
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                @if(($transaction['type'] ?? '') === 'topup') bg-blue-100 text-blue-800
                                                @elseif(($transaction['type'] ?? '') === 'transfer') bg-purple-100 text-purple-800
                                                @elseif(($transaction['type'] ?? '') === 'payment') bg-green-100 text-green-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($transaction['type'] ?? 'Unknown') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            Rp {{ number_format($transaction['amount'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                @if(($transaction['status'] ?? '') === 'completed') bg-green-100 text-green-800
                                                @elseif(($transaction['status'] ?? '') === 'pending') bg-yellow-100 text-yellow-800
                                                @elseif(($transaction['status'] ?? '') === 'failed') bg-red-100 text-red-800
                                                @else bg-gray-100 text-gray-800
                                                @endif">
                                                {{ ucfirst($transaction['status'] ?? 'Unknown') }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y H:i') : 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction['reference'] ?? 'N/A' }}
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No transactions found
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if(isset($error))
                <div class="mt-6 bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                    <div class="flex">
                        <i class="fas fa-exclamation-triangle text-yellow-400 mr-3 mt-1"></i>
                        <div>
                            <h4 class="text-yellow-800 font-medium">Warning</h4>
                            <p class="text-yellow-700 text-sm mt-1">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
    
    <script>
        // Transaction Types Chart
        const ctx = document.getElementById('transactionTypesChart').getContext('2d');
        const transactionTypes = @json($stats['transaction_types'] ?? ['topup' => 0, 'payment' => 0, 'transfer' => 0]);
        
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Top-up', 'Payment', 'Transfer'],
                datasets: [{
                    data: [
                        transactionTypes.topup || 0,
                        transactionTypes.payment || 0,
                        transactionTypes.transfer || 0
                    ],
                    backgroundColor: [
                        '#3B82F6',
                        '#10B981',
                        '#8B5CF6'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
        
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>
