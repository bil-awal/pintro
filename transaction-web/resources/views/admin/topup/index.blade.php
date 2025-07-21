<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Top Up Balance - Pintro Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - Top Up</h1>
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
                <h2 class="text-2xl font-bold text-gray-900">Top Up Balance</h2>
                <p class="text-gray-600">Add money to your wallet balance</p>
            </div>

            <!-- Current Balance Card -->
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 rounded-lg p-6 text-white mb-6">
                <h3 class="text-lg font-medium mb-2">Current Balance</h3>
                <div class="text-3xl font-bold">
                    @if($current_balance !== null)
                        Rp {{ number_format($current_balance, 0, ',', '.') }}
                    @else
                        <span class="text-sm">Unable to load balance</span>
                    @endif
                </div>
                <p class="text-blue-100 mt-2">Available for transactions</p>
            </div>

            <!-- Top Up Form -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Add Balance</h3>
                
                <form method="POST" action="{{ route('sysadmin.topup.store') }}">
                    @csrf
                    <div class="space-y-6">
                        <!-- Amount Selection -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Select Amount
                            </label>
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
                                <button type="button" onclick="setAmount(10000)" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 focus:border-blue-500">
                                    <div class="font-semibold">Rp 10,000</div>
                                </button>
                                <button type="button" onclick="setAmount(50000)" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 focus:border-blue-500">
                                    <div class="font-semibold">Rp 50,000</div>
                                </button>
                                <button type="button" onclick="setAmount(100000)" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 focus:border-blue-500">
                                    <div class="font-semibold">Rp 100,000</div>
                                </button>
                                <button type="button" onclick="setAmount(500000)" class="amount-btn border-2 border-gray-200 rounded-lg p-3 text-center hover:border-blue-500 focus:border-blue-500">
                                    <div class="font-semibold">Rp 500,000</div>
                                </button>
                            </div>
                        </div>

                        <!-- Custom Amount -->
                        <div>
                            <label for="amount" class="block text-sm font-medium text-gray-700">
                                Custom Amount (Rp) *
                            </label>
                            <input 
                                type="number" 
                                id="amount" 
                                name="amount" 
                                value="{{ old('amount') }}"
                                min="10000"
                                max="10000000"
                                step="1000"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                placeholder="Enter custom amount">
                            <p class="text-sm text-gray-500 mt-1">Minimum: Rp 10,000 | Maximum: Rp 10,000,000</p>
                            @error('amount')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label for="payment_method" class="block text-sm font-medium text-gray-700">
                                Payment Method *
                            </label>
                            <select 
                                id="payment_method" 
                                name="payment_method" 
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                                <option value="">Select payment method</option>
                                <option value="credit_card" {{ old('payment_method') === 'credit_card' ? 'selected' : '' }}>Credit Card</option>
                                <option value="bank_transfer" {{ old('payment_method') === 'bank_transfer' ? 'selected' : '' }}>Bank Transfer</option>
                                <option value="e_wallet" {{ old('payment_method') === 'e_wallet' ? 'selected' : '' }}>E-Wallet</option>
                            </select>
                            @error('payment_method')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Submit Button -->
                        <div class="pt-4">
                            <button 
                                type="submit"
                                class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-3 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 transition duration-200">
                                Proceed to Payment
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Error Messages -->
            @if($errors->any())
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded mb-6">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Success Message -->
            @if(session('success'))
                <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            <!-- Quick Actions -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
                <a href="{{ route('sysadmin.topup.history') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 block">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Top-up History</div>
                            <div class="text-sm text-gray-500">View all your top-up transactions</div>
                        </div>
                    </div>
                </a>

                <a href="{{ route('sysadmin.user.balance') }}" class="bg-white border border-gray-200 rounded-lg p-4 hover:bg-gray-50 block">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                                </svg>
                            </div>
                        </div>
                        <div class="ml-4">
                            <div class="text-sm font-medium text-gray-900">Check Balance</div>
                            <div class="text-sm text-gray-500">View current wallet balance</div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Information -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Top-up Information</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• Minimum top-up amount is Rp 10,000</li>
                    <li>• Maximum top-up amount is Rp 10,000,000 per transaction</li>
                    <li>• Funds will be added to your wallet immediately after successful payment</li>
                    <li>• You will be redirected to the payment gateway to complete the transaction</li>
                    <li>• All transactions are secured and encrypted</li>
                </ul>
            </div>
        </div>
    </div>

    <script>
        function setAmount(amount) {
            document.getElementById('amount').value = amount;
            
            // Update button styles
            document.querySelectorAll('.amount-btn').forEach(btn => {
                btn.classList.remove('border-blue-500', 'bg-blue-50');
                btn.classList.add('border-gray-200');
            });
            
            // Highlight selected button
            event.target.closest('.amount-btn').classList.remove('border-gray-200');
            event.target.closest('.amount-btn').classList.add('border-blue-500', 'bg-blue-50');
        }
    </script>
</body>
</html>
