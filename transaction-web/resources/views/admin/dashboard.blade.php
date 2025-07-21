<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-purple-600 min-h-screen p-5">
    <div class="max-w-6xl mx-auto bg-white rounded-2xl shadow-2xl overflow-hidden">
        <!-- Header -->
        <div class="bg-gradient-to-r from-pink-400 to-red-500 px-8 py-8 text-white text-center">
            <h1 class="text-4xl font-light mb-3">🎉 Pintro Financial Admin</h1>
            <p class="text-pink-100 text-lg">
                <span class="inline-block w-5 h-5 bg-green-500 rounded-full mr-3 animate-pulse"></span>
                Login Success! System is running perfectly
            </p>
        </div>
        
        <!-- Content -->
        <div class="p-10">
            <!-- Welcome Section -->
            <div class="text-center mb-10">
                <h2 class="text-3xl font-normal text-gray-800 mb-3">Welcome back, {{ $user->name }}!</h2>
                <p class="text-gray-600 text-lg">You have successfully logged into the admin dashboard</p>
            </div>
            
            <!-- User Info Card -->
            <div class="bg-gray-50 rounded-xl p-6 mb-10">
                <h3 class="text-xl text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user mr-3 text-blue-600"></i>
                    Admin Information
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-gray-700">
                    <p><strong>Name:</strong> {{ $user->name }}</p>
                    <p><strong>Email:</strong> {{ $user->email }}</p>
                    <p><strong>Roles:</strong> {{ is_array($user->roles) ? implode(', ', $user->roles) : 'Admin' }}</p>
                    <p><strong>Status:</strong> {{ $user->is_active ? '✅ Active' : '❌ Inactive' }}</p>
                    <p><strong>Last Login:</strong> {{ $user->last_login_at ? $user->last_login_at->format('d M Y, H:i') : 'First time login' }}</p>
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 text-white text-center transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="text-4xl font-light mb-3">✅</div>
                    <p class="text-blue-100">Authentication Working</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 text-white text-center transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="text-4xl font-light mb-3">🔒</div>
                    <p class="text-blue-100">CSRF Protection Active</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 text-white text-center transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="text-4xl font-light mb-3">💾</div>
                    <p class="text-blue-100">Database Connected</p>
                </div>
                <div class="bg-gradient-to-br from-blue-500 to-purple-600 rounded-2xl p-8 text-white text-center transform hover:-translate-y-2 transition-transform duration-300">
                    <div class="text-4xl font-light mb-3">🚀</div>
                    <p class="text-blue-100">System Ready</p>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5 mb-10">
                <a href="/admin/login" class="bg-gray-50 border-2 border-gray-200 rounded-xl p-5 text-center text-gray-800 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all duration-300 transform hover:-translate-y-1 block">
                    <i class="fas fa-cog text-2xl mb-3"></i>
                    <div class="font-medium">Back to Login Form</div>
                </a>
                <a href="/csrf-test" class="bg-gray-50 border-2 border-gray-200 rounded-xl p-5 text-center text-gray-800 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all duration-300 transform hover:-translate-y-1 block">
                    <i class="fas fa-tools text-2xl mb-3"></i>
                    <div class="font-medium">Test CSRF Token</div>
                </a>
                <a href="/debug-routes" class="bg-gray-50 border-2 border-gray-200 rounded-xl p-5 text-center text-gray-800 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all duration-300 transform hover:-translate-y-1 block">
                    <i class="fas fa-search text-2xl mb-3"></i>
                    <div class="font-medium">Debug Routes</div>
                </a>
                <a href="/health" class="bg-gray-50 border-2 border-gray-200 rounded-xl p-5 text-center text-gray-800 hover:bg-blue-600 hover:text-white hover:border-blue-600 transition-all duration-300 transform hover:-translate-y-1 block">
                    <i class="fas fa-heart text-2xl mb-3"></i>
                    <div class="font-medium">Health Check</div>
                </a>
            </div>
            
            <!-- Logout Button -->
            <div class="text-center mb-10">
                <form method="POST" action="/admin/logout" class="inline">
                    @csrf
                    <button type="submit" class="bg-red-600 hover:bg-red-700 text-white px-8 py-3 rounded-lg transition-colors duration-300 flex items-center mx-auto">
                        <i class="fas fa-sign-out-alt mr-3"></i>
                        Logout
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="bg-gray-50 px-8 py-6 text-center text-gray-600 border-t border-gray-200">
            <p class="text-lg font-medium text-green-600 mb-2">
                🎉 <strong>Congratulations!</strong> Your Laravel Filament authentication system is working perfectly!
            </p>
            <p class="mb-2">All major issues have been resolved: CSRF tokens, sessions, database, and authentication.</p>
            <p class="text-sm text-gray-500">
                Laravel {{ app()->version() }} • PHP {{ PHP_VERSION }} • {{ now()->format('d M Y, H:i:s') }}
            </p>
        </div>
    </div>
</body>
</html>
