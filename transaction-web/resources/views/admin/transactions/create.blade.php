<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Transaction - Pintro Admin</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-100">
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="bg-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between h-16">
                    <div class="flex items-center">
                        <h1 class="text-xl font-semibold text-gray-900">Pintro Admin - Create Transaction</h1>
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
        <div class="max-w-4xl mx-auto py-6 px-4" x-data="{ activeTab: 'payment' }">
            <!-- Header -->
            <div class="mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Create New Transaction</h2>
                <p class="text-gray-600">Send payment or transfer money to another user</p>
            </div>

            <!-- Tab Navigation -->
            <div class="bg-white rounded-lg shadow mb-6">
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex">
                        <button 
                            @click="activeTab = 'payment'"
                            :class="{ 'border-blue-500 text-blue-600': activeTab === 'payment', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'payment' }"
                            class="py-2 px-4 border-b-2 font-medium text-sm">
                            Payment
                        </button>
                        <button 
                            @click="activeTab = 'transfer'"
                            :class="{ 'border-blue-500 text-blue-600': activeTab === 'transfer', 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300': activeTab !== 'transfer' }"
                            class="py-2 px-4 border-b-2 font-medium text-sm">
                            Transfer
                        </button>
                    </nav>
                </div>

                <!-- Payment Form -->
                <div x-show="activeTab === 'payment'" class="p-6">
                    <form method="POST" action="{{ route('sysadmin.transactions.payment') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="payment_to_user_id" class="block text-sm font-medium text-gray-700">
                                    Recipient User ID *
                                </label>
                                <input 
                                    type="text" 
                                    id="payment_to_user_id" 
                                    name="to_user_id" 
                                    value="{{ old('to_user_id') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter recipient user ID">
                                @error('to_user_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_amount" class="block text-sm font-medium text-gray-700">
                                    Amount (Rp) *
                                </label>
                                <input 
                                    type="number" 
                                    id="payment_amount" 
                                    name="amount" 
                                    value="{{ old('amount') }}"
                                    min="1000"
                                    step="1000"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter amount (minimum Rp 1,000)">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="payment_description" class="block text-sm font-medium text-gray-700">
                                    Description *
                                </label>
                                <textarea 
                                    id="payment_description" 
                                    name="description" 
                                    rows="3"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter payment description">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-4">
                                <button 
                                    type="submit"
                                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                    Process Payment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Transfer Form -->
                <div x-show="activeTab === 'transfer'" class="p-6">
                    <form method="POST" action="{{ route('sysadmin.transactions.transfer') }}">
                        @csrf
                        <div class="space-y-4">
                            <div>
                                <label for="transfer_to_user_id" class="block text-sm font-medium text-gray-700">
                                    Recipient User ID *
                                </label>
                                <input 
                                    type="text" 
                                    id="transfer_to_user_id" 
                                    name="to_user_id" 
                                    value="{{ old('to_user_id') }}"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter recipient user ID">
                                @error('to_user_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="transfer_amount" class="block text-sm font-medium text-gray-700">
                                    Amount (Rp) *
                                </label>
                                <input 
                                    type="number" 
                                    id="transfer_amount" 
                                    name="amount" 
                                    value="{{ old('amount') }}"
                                    min="1000"
                                    step="1000"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter amount (minimum Rp 1,000)">
                                @error('amount')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div>
                                <label for="transfer_description" class="block text-sm font-medium text-gray-700">
                                    Description *
                                </label>
                                <textarea 
                                    id="transfer_description" 
                                    name="description" 
                                    rows="3"
                                    required
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                    placeholder="Enter transfer description">{{ old('description') }}</textarea>
                                @error('description')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>

                            <div class="pt-4">
                                <button 
                                    type="submit"
                                    class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-md focus:outline-none focus:ring-2 focus:ring-purple-500 focus:ring-offset-2">
                                    Process Transfer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
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

            <!-- Info Box -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <h3 class="text-sm font-medium text-blue-800 mb-2">Transaction Information</h3>
                <ul class="text-sm text-blue-700 space-y-1">
                    <li>• <strong>Payment:</strong> Direct payment to another user for goods or services</li>
                    <li>• <strong>Transfer:</strong> Send money from your wallet to another user's wallet</li>
                    <li>• Minimum transaction amount is Rp 1,000</li>
                    <li>• Make sure you have sufficient balance before creating transactions</li>
                    <li>• User ID must be a valid UUID of an existing user</li>
                </ul>
            </div>
        </div>
    </div>
</body>
</html>
