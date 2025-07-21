<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - Pintro Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - Profile</h1>
                    </div>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-600">Welcome, {{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? '' }}</span>
                        <a href="{{ route('sysadmin.dashboard') }}" class="text-blue-600 hover:text-blue-800">Dashboard</a>
                        <a href="{{ route('sysadmin.transactions.index') }}" class="text-blue-600 hover:text-blue-800">Transactions</a>
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
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">User Profile</h2>
                <p class="text-gray-600">View and manage your account information</p>
            </div>

            <!-- Profile Information Card -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-6">
                <div class="bg-gradient-to-r from-blue-500 to-purple-600 px-6 py-8">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="h-20 w-20 rounded-full bg-white bg-opacity-20 flex items-center justify-center">
                                <span class="text-2xl font-bold text-white">
                                    {{ strtoupper(substr($admin['first_name'] ?? 'A', 0, 1)) }}{{ strtoupper(substr($admin['last_name'] ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                        </div>
                        <div class="ml-6">
                            <h3 class="text-2xl font-bold text-white">
                                {{ $admin['first_name'] ?? 'Admin' }} {{ $admin['last_name'] ?? 'User' }}
                            </h3>
                            <p class="text-blue-100">{{ $admin['email'] ?? 'admin@example.com' }}</p>
                            <p class="text-blue-100 text-sm mt-1">
                                Member since {{ isset($admin['created_at']) ? \Carbon\Carbon::parse($admin['created_at'])->format('M Y') : 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="px-6 py-6">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">User ID</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $admin['id'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Status</dt>
                            <dd class="mt-1">
                                @php
                                    $status = $admin['status'] ?? 'active';
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800',
                                        'inactive' => 'bg-yellow-100 text-yellow-800',
                                        'blocked' => 'bg-red-100 text-red-800'
                                    ];
                                @endphp
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-800' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Email Address</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $admin['email'] ?? 'N/A' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Phone Number</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $admin['phone'] ?? 'Not provided' }}</dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Account Created</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ isset($admin['created_at']) ? \Carbon\Carbon::parse($admin['created_at'])->format('M j, Y H:i') : 'N/A' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Last Updated</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                {{ isset($admin['updated_at']) ? \Carbon\Carbon::parse($admin['updated_at'])->format('M j, Y H:i') : 'N/A' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Balance Information Card -->
            @if($balance)
                <div class="bg-white rounded-lg shadow mb-6">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Wallet Information</h3>
                    </div>
                    <div class="px-6 py-6">
                        <div class="flex items-center justify-between">
                            <div>
                                <dt class="text-sm font-medium text-gray-500">Current Balance</dt>
                                <dd class="mt-1 text-3xl font-bold text-green-600">
                                    Rp {{ number_format($balance['balance'] ?? 0, 0, ',', '.') }}
                                </dd>
                            </div>
                            <div class="text-right">
                                <a href="{{ route('sysadmin.topup.index') }}" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-green-600 hover:bg-green-700">
                                    <svg class="-ml-1 mr-2 h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Top Up
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Error Message -->
            @if(isset($error) && $error)
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    {{ $error }}
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                <a href="{{ route('sysadmin.user.transactions') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 block">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Transaction History</div>
                            <div class="text-sm text-gray-500">View all transactions</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('sysadmin.topup.index') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 block">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Top Up Balance</div>
                            <div class="text-sm text-gray-500">Add money to wallet</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('sysadmin.transactions.create') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 block">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Send Money</div>
                            <div class="text-sm text-gray-500">Transfer or payment</div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Account Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Account Information</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• This is your user profile information retrieved from the transaction service</li>
                    <li>• Your account is linked to the Golang transaction backend</li>
                    <li>• All transactions are processed securely through the API</li>
                    <li>• Profile updates will be synchronized with the backend service</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
