<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transaction Details - Pintro Admin</title>
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
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - Transaction Details</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, {{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? '' }}</span>
                        <a href="{{ route('sysadmin.dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                        <a href="{{ route('sysadmin.transactions.index') }}" class="text-blue-600 hover:text-blue-800">Back to Transactions</a>
                        <form method="POST" action="{{ route('sysadmin.logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-red-600 hover:text-red-800">Logout</button>
                        </form>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content -->
        <div class="max-w-4xl mx-auto py-6 px-4">
            <!-- Breadcrumb -->
            <nav class="flex mb-6" aria-label="Breadcrumb">
                <ol class="inline-flex items-center space-x-1 md:space-x-3">
                    <li class="inline-flex items-center">
                        <a href="{{ route('sysadmin.dashboard') }}" class="text-gray-700 hover:text-blue-600">
                            <i class="fas fa-home mr-1"></i>
                            Dashboard
                        </a>
                    </li>
                    <li>
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <a href="{{ route('sysadmin.transactions.index') }}" class="text-gray-700 hover:text-blue-600">Transactions</a>
                        </div>
                    </li>
                    <li aria-current="page">
                        <div class="flex items-center">
                            <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                            <span class="text-gray-500">{{ substr($transaction['id'] ?? 'N/A', 0, 8) }}...</span>
                        </div>
                    </li>
                </ol>
            </nav>

            <!-- Transaction Details Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <h2 class="text-xl font-bold text-white">Transaction Details</h2>
                            <p class="text-blue-100">ID: {{ $transaction['id'] ?? 'N/A' }}</p>
                        </div>
                        <div class="text-right">
                            @php
                                $status = $transaction['status'] ?? 'unknown';
                                $statusColors = [
                                    'pending' => 'bg-yellow-500',
                                    'processing' => 'bg-blue-500',
                                    'completed' => 'bg-green-500',
                                    'failed' => 'bg-red-500',
                                    'cancelled' => 'bg-gray-500'
                                ];
                                $statusColor = $statusColors[$status] ?? 'bg-gray-500';
                            @endphp
                            <span class="px-3 py-1 {{ $statusColor }} text-white text-sm font-medium rounded-full">
                                {{ ucfirst($status) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Transaction Info -->
                <div class="px-6 py-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Basic Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-info-circle mr-2 text-blue-600"></i>
                                Basic Information
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Transaction ID</dt>
                                    <dd class="mt-1 text-sm text-gray-900 font-mono">{{ $transaction['id'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Reference</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $transaction['reference'] ?? 'N/A' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Type</dt>
                                    <dd class="mt-1">
                                        @php
                                            $type = $transaction['type'] ?? 'unknown';
                                            $typeColors = [
                                                'topup' => 'bg-green-100 text-green-800',
                                                'payment' => 'bg-blue-100 text-blue-800',
                                                'transfer' => 'bg-purple-100 text-purple-800'
                                            ];
                                            $typeIcons = [
                                                'topup' => 'fas fa-arrow-up',
                                                'payment' => 'fas fa-credit-card',
                                                'transfer' => 'fas fa-exchange-alt'
                                            ];
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-800' }}">
                                            <i class="{{ $typeIcons[$type] ?? 'fas fa-question' }} mr-1"></i>
                                            {{ ucfirst($type) }}
                                        </span>
                                    </dd>
                                </div>
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Status</dt>
                                    <dd class="mt-1">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }} text-white">
                                            {{ ucfirst($status) }}
                                        </span>
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        <!-- Amount Information -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                                <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>
                                Amount Details
                            </h3>
                            <dl class="space-y-3">
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Amount</dt>
                                    <dd class="mt-1 text-2xl font-bold text-green-600">
                                        Rp {{ number_format($transaction['amount'] ?? 0, 0, ',', '.') }}
                                    </dd>
                                </div>
                                @if(isset($transaction['fee']) && $transaction['fee'] > 0)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Fee</dt>
                                    <dd class="mt-1 text-sm text-gray-900">
                                        Rp {{ number_format($transaction['fee'], 0, ',', '.') }}
                                    </dd>
                                </div>
                                @endif
                                <div>
                                    <dt class="text-sm font-medium text-gray-500">Currency</dt>
                                    <dd class="mt-1 text-sm text-gray-900">{{ $transaction['currency'] ?? 'IDR' }}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>

                    <!-- Description -->
                    @if(isset($transaction['description']) && !empty($transaction['description']))
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-align-left mr-2 text-gray-600"></i>
                            Description
                        </h3>
                        <p class="text-gray-700 bg-gray-50 p-4 rounded-lg">{{ $transaction['description'] }}</p>
                    </div>
                    @endif

                    <!-- Timestamps -->
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                            <i class="fas fa-clock mr-2 text-blue-600"></i>
                            Timeline
                        </h3>
                        <div class="space-y-3">
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-blue-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="text-sm font-medium text-gray-900">Created:</span>
                                    <span class="text-sm text-gray-600 ml-2">
                                        {{ isset($transaction['created_at']) ? \Carbon\Carbon::parse($transaction['created_at'])->format('M j, Y H:i:s') : 'N/A' }}
                                    </span>
                                </div>
                            </div>
                            @if(isset($transaction['processed_at']) && !empty($transaction['processed_at']))
                            <div class="flex items-center">
                                <div class="w-2 h-2 bg-green-500 rounded-full mr-3"></div>
                                <div>
                                    <span class="text-sm font-medium text-gray-900">Processed:</span>
                                    <span class="text-sm text-gray-600 ml-2">
                                        {{ \Carbon\Carbon::parse($transaction['processed_at'])->format('M j, Y H:i:s') }}
                                    </span>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- Metadata -->
                    @if(isset($transaction['metadata']) && !empty($transaction['metadata']))
                    <div class="mt-6">
                        <h3 class="text-lg font-semibold text-gray-900 mb-2 flex items-center">
                            <i class="fas fa-database mr-2 text-gray-600"></i>
                            Additional Information
                        </h3>
                        <div class="bg-gray-50 p-4 rounded-lg">
                            <pre class="text-xs text-gray-700 whitespace-pre-wrap">{{ is_array($transaction['metadata']) ? json_encode($transaction['metadata'], JSON_PRETTY_PRINT) : $transaction['metadata'] }}</pre>
                        </div>
                    </div>
                    @endif
                </div>

                <!-- Action Buttons -->
                <div class="bg-gray-50 px-6 py-4 flex justify-between items-center">
                    <a href="{{ route('sysadmin.transactions.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Back to Transactions
                    </a>
                    
                    <div class="flex space-x-2">
                        @if($status === 'pending')
                            <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-green-600 hover:bg-green-700">
                                <i class="fas fa-check mr-2"></i>
                                Approve
                            </button>
                            <button class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-red-600 hover:bg-red-700">
                                <i class="fas fa-times mr-2"></i>
                                Reject
                            </button>
                        @endif
                        
                        <button onclick="window.print()" class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md shadow-sm text-sm font-medium text-gray-700 bg-white hover:bg-gray-50">
                            <i class="fas fa-print mr-2"></i>
                            Print
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background: white !important;
            }
        }
    </style>
</body>
</html>
