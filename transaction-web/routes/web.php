<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\AdminDashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Redirect root to user panel (Filament will handle authentication)
Route::get('/', function () {
    return redirect('/sys-admin/login');
});

// Redirect /login to user panel login
Route::get('/login', function () {
    return redirect('/user/login');
})->name('login');

// Admin panel redirect to new sys-admin
Route::get('/admin', function () {
    return redirect('/sys-admin/login');
});

/*
|--------------------------------------------------------------------------
| Custom System Admin Authentication Routes
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Admin\TransactionController;
use App\Http\Controllers\Admin\TopupController;
use App\Http\Controllers\Admin\UserController;

Route::prefix('sys-admin')->name('sysadmin.')->group(function () {
    
    // Guest routes (accessible when not authenticated)
    Route::middleware(['admin.guest'])->group(function () {
  
        Route::get('/login', [AdminAuthController::class, 'showLoginForm'])->name('login');

        Route::post('/login', [AdminAuthController::class, 'login'])->name('login.submit');
        
        Route::get('/debug-auth', [AdminAuthController::class, 'debug'])->name('debug.auth');
    });
    
    // Protected routes (require admin authentication)
    Route::middleware(['admin.auth'])->group(function () {
        
        // Dashboard
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/dashboard/home', [AdminDashboardController::class, 'index'])->name('dashboard.home');
        
        // Dashboard API endpoints
        Route::get('/dashboard/stats', [AdminDashboardController::class, 'stats'])->name('dashboard.stats');
        Route::get('/dashboard/recent-transactions', [AdminDashboardController::class, 'recentTransactions'])->name('dashboard.recent-transactions');
        Route::get('/dashboard/user-activity', [AdminDashboardController::class, 'userActivity'])->name('dashboard.user-activity');
        
        // Logout
        Route::post('/logout', [AdminAuthController::class, 'logout'])->name('logout');
        Route::get('/logout', [AdminAuthController::class, 'logout'])->name('logout.get');
        
        // Profile management
        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/', [AdminAuthController::class, 'profile'])->name('show');
            Route::put('/update', [AdminAuthController::class, 'updateProfile'])->name('update');
        });
        
        // User Management
        Route::prefix('user')->name('user.')->group(function () {
            Route::get('/profile', [UserController::class, 'profile'])->name('profile');
            Route::get('/balance', [UserController::class, 'balance'])->name('balance');
            Route::get('/transactions', [UserController::class, 'transactions'])->name('transactions');
            Route::get('/dashboard', [UserController::class, 'dashboard'])->name('dashboard');
        });
        
        // Transaction Management
        Route::prefix('transactions')->name('transactions.')->group(function () {
            Route::get('/', [TransactionController::class, 'index'])->name('index');
            Route::get('/create', [TransactionController::class, 'create'])->name('create');
            Route::get('/{id}', [TransactionController::class, 'show'])->name('show');
            Route::post('/payment', [TransactionController::class, 'payment'])->name('payment');
            Route::post('/transfer', [TransactionController::class, 'transfer'])->name('transfer');
        });
        
        // Top-up Management
        Route::prefix('topup')->name('topup.')->group(function () {
            Route::get('/', [TopupController::class, 'index'])->name('index');
            Route::post('/', [TopupController::class, 'store'])->name('store');
            Route::get('/balance', [TopupController::class, 'balance'])->name('balance');
            Route::get('/history', [TopupController::class, 'history'])->name('history');
        });
        
        // System health and status
        Route::prefix('system')->name('system.')->group(function () {
            Route::get('/health', [AdminAuthController::class, 'systemHealth'])->name('health');
        });
        
        Route::get('/debug', [AdminAuthController::class, 'debug'])->name('debug');
        
        // Connectivity test endpoints
        Route::get('/test-go-service', [AdminDashboardController::class, 'testConnectivity'])->name('test.go-service');
        
        // Quick health check
        Route::get('/health-check', function() {
            $service = app(\App\Services\GoTransactionService::class);
            
            return response()->json([
                'timestamp' => now()->toISOString(),
                'go_service_health' => $service->getSystemHealth(),
                'connectivity_test' => $service->testConnectivity(),
                'admin_session' => [
                    'authenticated' => session('admin_authenticated', false),
                    'has_token' => !is_null(session('admin_token')),
                    'user_id' => session('admin_user.id'),
                    'session_id' => session()->getId()
                ],
                'environment' => [
                    'go_service_url' => env('GO_TRANSACTION_SERVICE_URL'),
                    'app_env' => app()->environment(),
                    'app_debug' => config('app.debug')
                ]
            ]);
        })->name('health-check');
    });
});
