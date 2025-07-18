<?php

namespace App\Filament\User\Widgets;

use Filament\Widgets\ChartWidget;
use App\Services\GoTransactionService;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransactionChart extends ChartWidget
{
    protected static ?string $heading = 'Transaction Overview (Last 7 Days)';

    protected static ?int $sort = 4;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        try {
            $goService = app(GoTransactionService::class);
            $token = Session::get('user_token');

            if (!$token) {
                return $this->getEmptyData();
            }

            // Get transactions from last 7 days
            $transactions = $goService->getUserTransactions($token, [
                'date_from' => now()->subDays(7)->format('Y-m-d'),
                'date_to' => now()->format('Y-m-d'),
                'limit' => 500,
            ]);

            return $this->processTransactionData($transactions);

        } catch (\Exception $e) {
            Log::error('Transaction chart widget error', [
                'error' => $e->getMessage(),
                'user_id' => Session::get('user_id'),
            ]);

            return $this->getEmptyData();
        }
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return "Rp " + value.toLocaleString("id-ID"); }',
                    ],
                ],
            ],
            'interaction' => [
                'intersect' => false,
            ],
            'elements' => [
                'point' => [
                    'radius' => 4,
                ],
            ],
        ];
    }

    private function processTransactionData(array $transactions): array
    {
        // Initialize data for last 7 days
        $dates = [];
        $topupData = [];
        $paymentData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $dates[] = $date->format('M j');
            $topupData[] = 0;
            $paymentData[] = 0;
        }

        // Process transactions
        foreach ($transactions as $transaction) {
            if (!isset($transaction['created_at']) || !isset($transaction['type']) || !isset($transaction['amount'])) {
                continue;
            }

            $transactionDate = Carbon::parse($transaction['created_at']);
            $dayIndex = 6 - now()->diffInDays($transactionDate);

            if ($dayIndex >= 0 && $dayIndex < 7) {
                if ($transaction['type'] === 'topup') {
                    $topupData[$dayIndex] += $transaction['amount'];
                } elseif ($transaction['type'] === 'payment') {
                    $paymentData[$dayIndex] += $transaction['amount'];
                }
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Top-up',
                    'data' => $topupData,
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
                [
                    'label' => 'Payment',
                    'data' => $paymentData,
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                    'tension' => 0.4,
                ],
            ],
            'labels' => $dates,
        ];
    }

    private function getEmptyData(): array
    {
        $dates = [];
        for ($i = 6; $i >= 0; $i--) {
            $dates[] = now()->subDays($i)->format('M j');
        }

        return [
            'datasets' => [
                [
                    'label' => 'Top-up',
                    'data' => array_fill(0, 7, 0),
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'borderColor' => 'rgb(34, 197, 94)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
                [
                    'label' => 'Payment',
                    'data' => array_fill(0, 7, 0),
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'borderColor' => 'rgb(239, 68, 68)',
                    'borderWidth' => 2,
                    'fill' => true,
                ],
            ],
            'labels' => $dates,
        ];
    }
}
