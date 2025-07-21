<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Transactions - Pintro Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - My Transactions</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, {{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? '' }}</span>
                        <a href="{{ route('sysadmin.dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                        <a href="{{ route('sysadmin.user.profile') }}" class="text-blue-600 hover:text-blue-800">Profile</a>
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
                <h2 class="text-2xl font-bold text-gray-900">My Transaction History</h2>
                <p class="text-gray-600">View all your transactions and activity</p>
            </div>

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="{{ route('sysadmin.topup.index') }}" class="bg-green-500 hover:bg-green-600 text-white p-4 rounded-lg block text-center transform hover:scale-105 transition duration-200">
                    <i class="fas fa-plus-circle text-2xl mb-2"></i>
                    <div class="text-lg font-semibold">Top Up</div>
                    <div class="text-sm">Add balance</div>
                </a>
                <a href="{{ route('sysadmin.transactions.create') }}" class="bg-blue-500 hover:bg-blue-600 text-white p-4 rounded-lg block text-center transform hover:scale-105 transition duration-200">
                    <i class="fas fa-paper-plane text-2xl mb-2"></i>
                    <div class="text-lg font-semibold">Send Money</div>
                    <div class="text-sm">Pay or transfer</div>
                </a>
                <a href="{{ route('sysadmin.user.balance') }}" class="bg-purple-500 hover:bg-purple-600 text-white p-4 rounded-lg block text-center transform hover:scale-105 transition duration-200">
                    <i class="fas fa-wallet text-2xl mb-2"></i>
                    <div class="text-lg font-semibold">Check Balance</div>
                    <div class="text-sm">View balance</div>
                </a>
            </div>

            <!-- Filters -->
            <div class="bg-white p-4 rounded-lg shadow mb-6">
                <form method="GET" action="{{ route('sysadmin.user.transactions') }}" class="flex flex-wrap gap-4">
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
                            <i class="fas fa-filter mr-2"></i>Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Error Message -->
            @if(isset($error) && $error)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i>
                    {{ $error }}
                </div>
            @endif

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i>
                    {{ session('success') }}
                </div>
            @endif

            <!-- Transactions List -->
            <div class="bg-white rounded-lg shadow overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900 flex items-center">
                        <i class="fas fa-history mr-2 text-blue-600"></i>
                        Transaction History ({{ count($transactions) }} transactions)
                    </h3>
                </div>
                
                @if(count($transactions) > 0)
                    <div class="divide-y divide-gray-200">
                        @foreach($transactions as $transaction)
                            <div class="p-6 hover:bg-gray-50">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center space-x-4">
                                        <!-- Transaction Icon -->
                                        <div class="flex-shrink-0">
                                            @php
                                                $type = $transaction['type'] ?? 'unknown';
                                                $typeColors = [
                                                    'topup' => 'bg-green-100 text-green-600',
                                                    'payment' => 'bg-blue-100 text-blue-600',
                                                    'transfer' => 'bg-purple-100 text-purple-600'
                                                ];
                                                $typeIcons = [
                                                    'topup' => 'fas fa-arrow-up',
                                                    'payment' => 'fas fa-credit-card',
                                                    'transfer' => 'fas fa-exchange-alt'
                                                ];
                                            @endphp
                                            <div class="w-12 h-12 rounded-full {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-600' }} flex items-center justify-center">
                                                <i class="{{ $typeIcons[$type] ?? 'fas fa-question' }} text-lg"></i>
                                            </div>
                                        </div>

                                        <!-- Transaction Details -->
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center space-x-2">
                                                <h4 class="text-sm font-medium text-gray-900">{{ ucfirst($type) }}</h4>
                                                @php
                                                    $status = $transaction['status'] ?? 'unknown';
                                                    $statusColors = [
                                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                                        'processing' => 'bg-blue-100 text-blue-800',
                                                        'completed' => 'bg-green-100 text-green-800',
                                                        'failed' => 'bg-red-100 text-red-800',
                                                        'cancelled' => 'bg-gray-100 text-gray-800'
                                                    ];
                                                @endphp
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                                    {{ ucfirst($status) }}
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 truncate">
                                                {{ $transaction['description'] ?? 'No description' }}
                                            </p>
                                            <p class="text-xs text-gray-500">
                                                <i class="fas fa-hashtag mr-1"></i>
                                                {{ $transaction['reference'] ?? 'N/A' }}
                                            </p>
                                        </div>
                                    </div>

                                    <!-- Amount and Date -->
                                    <div class="text-right">
                                        <div class="text-lg font-semibold {{ $type === 'topup' ? 'text-green-600' : 'text-gray-900' }}">
                                            {{ $type === 'topup' ? '+' : '' }}Rp {{ number_format($transaction['amount'] ?? 0, 0, ',', '.') }}
                                        </div>
                                        <p class="text-sm text-gray-500">
                                            {{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y') : 'N/A' }}
                                        </p>
                                        <p class="text-xs text-gray-400">
                                            {{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('H:i') : '' }}
                                        </p>
                                    </div>
                                </div>

                                <!-- Additional Info (expandable) -->
                                @if(isset($transaction['processed_at']) && !empty($transaction['processed_at']))
                                    <div class="mt-3 text-xs text-gray-500">
                                        <i class="fas fa-check-circle mr-1"></i>
                                        Processed: {{ \Carbon\Carbon::parse($transaction['processed_at'])->format('M j, Y H:i') }}
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <!-- Summary -->
                    <div class="bg-gray-50 px-6 py-4">
                        @php
                            $totalAmount = 0;
                            $topupTotal = 0;
                            $spentTotal = 0;
                            foreach($transactions as $transaction) {
                                $amount = $transaction['amount'] ?? 0;
                                if (($transaction['type'] ?? '') === 'topup') {
                                    $topupTotal += $amount;
                                } else {
                                    $spentTotal += $amount;
                                }
                                $totalAmount += $amount;
                            }
                        @endphp
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                            <div class="text-center">
                                <div class="font-medium text-gray-900">Total Top-ups</div>
                                <div class="text-lg font-semibold text-green-600">Rp {{ number_format($topupTotal, 0, ',', '.') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-gray-900">Total Spent</div>
                                <div class="text-lg font-semibold text-red-600">Rp {{ number_format($spentTotal, 0, ',', '.') }}</div>
                            </div>
                            <div class="text-center">
                                <div class="font-medium text-gray-900">Net Balance Change</div>
                                <div class="text-lg font-semibold {{ ($topupTotal - $spentTotal) >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    Rp {{ number_format($topupTotal - $spentTotal, 0, ',', '.') }}
                                </div>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="text-center py-12">
                        <i class="fas fa-receipt text-4xl text-gray-400 mb-4"></i>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No transactions found</h3>
                        <p class="text-gray-500 mb-6">You haven't made any transactions yet.</p>
                        <div class="space-x-4">
                            <a href="{{ route('sysadmin.topup.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-plus mr-2"></i>
                                Top Up Now
                            </a>
                            <a href="{{ route('sysadmin.transactions.create') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50">
                                <i class="fas fa-paper-plane mr-2"></i>
                                Send Money
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</body>
</html>
