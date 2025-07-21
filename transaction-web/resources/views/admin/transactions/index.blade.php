<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Management - Pintro Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - Transactions</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, {{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? '' }}</span>
                        <a href="{{ route('sysadmin.dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                        <form method="POST" action="{{ route('sysadmin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-7xl mx-auto py-6 px-4">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Transaction Management</h2>
                <p class="text-gray-600">View and manage all transactions</p>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <a href="{{ route('sysadmin.topup.index') }}" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg block text-center">
                    <div class="text-lg font-semibold">Top Up</div>
                    <div class="text-sm">Add balance</div>
                </a>
                <a href="{{ route('sysadmin.transactions.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg block text-center">
                    <div class="text-lg font-semibold">New Payment</div>
                    <div class="text-sm">Make payment</div>
                </a>
                <a href="{{ route('sysadmin.transactions.create') }}" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg block text-center">
                    <div class="text-lg font-semibold">Transfer</div>
                    <div class="text-sm">Send money</div>
                </a>
                <a href="{{ route('sysadmin.user.balance') }}" class="bg-gray-500 hover:bg-gray-600 text-white p-4 rounded-lg block text-center">
                    <div class="text-lg font-semibold">Check Balance</div>
                    <div class="text-sm">View balance</div>
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form method="GET" action="{{ route('sysadmin.transactions.index') }}" class="flex flex-wrap gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Type</label>
                        <select name="type" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All Types</option>
                            <option value="topup" {{ request('type') === 'topup' ? 'selected' : '' }}>Top Up</option>
                            <option value="payment" {{ request('type') === 'payment' ? 'selected' : '' }}>Payment</option>
                            <option value="transfer" {{ request('type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <select name="status" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="">All Status</option>
                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                            <option value="processing" {{ request('status') === 'processing' ? 'selected' : '' }}>Processing</option>
                            <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completed</option>
                            <option value="failed" {{ request('status') === 'failed' ? 'selected' : '' }}>Failed</option>
                            <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Limit</label>
                        <select name="limit" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm">
                            <option value="20" {{ request('limit', 20) == 20 ? 'selected' : '' }}>20</option>
                            <option value="50" {{ request('limit', 20) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('limit', 20) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Error Message -->
            @if(isset($error) && $error)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    {{ $error }}
                </div>
            @endif

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">
                        Transaction History ({{ count($transactions) }} transactions)
                    </h3>
                </div>
                
                @if(count($transactions) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Amount</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reference</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Description</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($transactions as $transaction)
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                            {{ substr($transaction['id'] ?? 'N/A', 0, 8) }}...
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $typeColors = [
                                                    'topup' => 'bg-green-100 text-green-800',
                                                    'payment' => 'bg-blue-100 text-blue-800',
                                                    'transfer' => 'bg-purple-100 text-purple-800'
                                                ];
                                                $type = $transaction['type'] ?? 'unknown';
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($type) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                            Rp {{ number_format($transaction['amount'] ?? 0, 0, ',', '.') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            @php
                                                $statusColors = [
                                                    'pending' => 'bg-yellow-100 text-yellow-800',
                                                    'processing' => 'bg-blue-100 text-blue-800',
                                                    'completed' => 'bg-green-100 text-green-800',
                                                    'failed' => 'bg-red-100 text-red-800',
                                                    'cancelled' => 'bg-gray-100 text-gray-800'
                                                ];
                                                $status = $transaction['status'] ?? 'unknown';
                                            @endphp
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($status) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ $transaction['reference'] ?? 'N/A' }}
                                        </td>
                                        <td class="px-6 py-4 text-sm text-gray-500">
                                            {{ substr($transaction['description'] ?? 'No description', 0, 50) }}{{ strlen($transaction['description'] ?? '') > 50 ? '...' : '' }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                            {{ \Carbon\Carbon::parse($transaction['created_at'] ?? now())->format('M j, Y H:i') }}
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            @if(isset($transaction['id']))
                                                <a href="{{ route('sysadmin.transactions.show', $transaction['id']) }}" class="text-blue-600 hover:text-blue-900">
                                                    View
                                                </a>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-8">
                        <div class="text-gray-500">No transactions found</div>
                        <div class="mt-2">
                            <a href="{{ route('sysadmin.topup.index') }}" class="text-blue-600 hover:text-blue-800">
                                Create your first transaction
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Pagination would go here if needed -->
        </div>
    </div>
</body>
</html>
