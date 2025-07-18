<?php

namespace App\Filament\Widgets;

use App\Services\GoTransactionService;
use App\Services\MidtransService;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class SystemHealthWidget extends Widget
{
    protected static string $view = 'filament.widgets.system-health';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 3;

    public function getViewData(): array
    {
        return [
            'systemHealth' => $this->getSystemHealth(),
        ];
    }

    protected function getSystemHealth(): array
    {
        $health = [
            'database' => $this->checkDatabaseHealth(),
            'go_service' => $this->checkGoServiceHealth(),
            'midtrans' => $this->checkMidtransHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
        ];

        $overallStatus = $this->determineOverallStatus($health);

        return [
            'overall' => $overallStatus,
            'services' => $health,
            'last_check' => now()->toISOString(),
        ];
    }

    protected function checkDatabaseHealth(): array
    {
        try {
            $start = microtime(true);
            DB::connection()->getPdo();
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            return [
                'status' => 'healthy',
                'response_time' => $responseTime,
                'message' => 'Database connection successful',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'message' => 'Database connection failed: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkGoServiceHealth(): array
    {
        try {
            $goService = new GoTransactionService();
            $health = $goService->getSystemHealth();
            
            return [
                'status' => $health['status'],
                'response_time' => $health['response_time'],
                'message' => $health['status'] === 'healthy' 
                    ? 'Go service is responding' 
                    : 'Go service issue: ' . ($health['error'] ?? 'Unknown error'),
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unreachable',
                'response_time' => null,
                'message' => 'Go service unreachable: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkMidtransHealth(): array
    {
        try {
            $midtransService = new MidtransService();
            // Simple check - we'll assume it's healthy if the service can be instantiated
            // In a real scenario, you might want to make a test API call
            
            return [
                'status' => 'healthy',
                'response_time' => null,
                'message' => 'Midtrans service configured',
            ];
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'message' => 'Midtrans configuration issue: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkCacheHealth(): array
    {
        try {
            $start = microtime(true);
            Cache::put('health_check', 'test', 10);
            $value = Cache::get('health_check');
            Cache::forget('health_check');
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($value === 'test') {
                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
                    'message' => 'Cache is working properly',
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'response_time' => $responseTime,
                    'message' => 'Cache read/write failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'message' => 'Cache error: ' . $e->getMessage(),
            ];
        }
    }

    protected function checkStorageHealth(): array
    {
        try {
            $start = microtime(true);
            $testFile = storage_path('app/health_check.txt');
            file_put_contents($testFile, 'health check');
            $content = file_get_contents($testFile);
            unlink($testFile);
            $responseTime = round((microtime(true) - $start) * 1000, 2);

            if ($content === 'health check') {
                return [
                    'status' => 'healthy',
                    'response_time' => $responseTime,
                    'message' => 'Storage is accessible',
                ];
            } else {
                return [
                    'status' => 'unhealthy',
                    'response_time' => $responseTime,
                    'message' => 'Storage read/write failed',
                ];
            }
        } catch (\Exception $e) {
            return [
                'status' => 'unhealthy',
                'response_time' => null,
                'message' => 'Storage error: ' . $e->getMessage(),
            ];
        }
    }

    protected function determineOverallStatus(array $health): string
    {
        $statuses = array_column($health, 'status');
        
        if (in_array('unhealthy', $statuses)) {
            return 'unhealthy';
        }
        
        if (in_array('unreachable', $statuses)) {
            return 'degraded';
        }
        
        return 'healthy';
    }

    protected function getPollingInterval(): ?string
    {
        return '60s';
    }
}
