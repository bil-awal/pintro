<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\WebhookController;
use App\Http\Controllers\Api\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('webhooks')->group(function () {
    // Midtrans payment notification webhook
    Route::post('/midtrans/notification', [WebhookController::class, 'midtransNotification'])
        ->name('webhooks.midtrans');
    
    // Go service webhook notifications
    Route::post('/go-service/notification', [WebhookController::class, 'goServiceNotification'])
        ->name('webhooks.go-service');
    
    // Health check endpoint
    Route::get('/health', [WebhookController::class, 'healthCheck'])
        ->name('webhooks.health');
});

/*
|--------------------------------------------------------------------------
| Admin API Routes (Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth:admin'])->prefix('admin')->group(function () {
    
    // Transaction management
    Route::prefix('transactions')->group(function () {
        Route::get('/', [TransactionController::class, 'index'])
            ->name('api.transactions.index');
        
        Route::get('/statistics', [TransactionController::class, 'statistics'])
            ->name('api.transactions.statistics');
        
        Route::get('/{transactionId}', [TransactionController::class, 'show'])
            ->name('api.transactions.show');
        
        Route::post('/{transactionId}/approve', [TransactionController::class, 'approve'])
            ->name('api.transactions.approve');
        
        Route::post('/{transactionId}/reject', [TransactionController::class, 'reject'])
            ->name('api.transactions.reject');
        
        Route::get('/{transactionId}/payment-status', [TransactionController::class, 'checkPaymentStatus'])
            ->name('api.transactions.payment-status');
        
        Route::post('/sync-from-go-service', [TransactionController::class, 'syncFromGoService'])
            ->name('api.transactions.sync');
    });
});

/*
|--------------------------------------------------------------------------
| Public API Routes (Rate Limited)
|--------------------------------------------------------------------------
*/

Route::middleware(['throttle:60,1'])->group(function () {
    
    // System status
    Route::get('/status', function () {
        return response()->json([
            'status' => 'operational',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => app()->environment(),
        ]);
    })->name('api.status');
    
    // Basic system health check
    Route::get('/health', function () {
        try {
            // Quick database check
            \Illuminate\Support\Facades\DB::connection()->getPdo();
            
            return response()->json([
                'status' => 'healthy',
                'checks' => [
                    'database' => 'ok',
                    'application' => 'ok',
                ],
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'unhealthy',
                'checks' => [
                    'database' => 'error',
                    'application' => 'ok',
                ],
                'error' => 'Database connection failed',
                'timestamp' => now()->toISOString(),
            ], 503);
        }
    })->name('api.health');
});
