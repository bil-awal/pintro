<?php

namespace App\Filament\Resources\TransactionResource\Widgets;

use App\Models\Transaction;
use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TransactionChart extends ChartWidget
{
    protected static ?string $heading = 'Transaction Trends';

    protected static ?int $sort = 1;

    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $reportService = new ReportService();
        $trends = $reportService->getDailyTransactionTrends(30);

        $labels = [];
        $transactionCounts = [];
        $volumes = [];

        foreach ($trends as $trend) {
            $labels[] = Carbon::parse($trend['date'])->format('M d');
            $transactionCounts[] = $trend['count'];
            $volumes[] = round($trend['total_amount'] / 1000000, 2); // Convert to millions
        }

        return [
            'datasets' => [
                [
                    'label' => 'Transaction Count',
                    'data' => $transactionCounts,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'tension' => 0.1,
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Volume (Millions IDR)',
                    'data' => $volumes,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'tension' => 0.1,
                    'yAxisID' => 'y1',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'interaction' => [
                'mode' => 'index',
                'intersect' => false,
            ],
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Transaction Count',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Volume (Millions IDR)',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                ],
                'tooltip' => [
                    'mode' => 'index',
                    'intersect' => false,
                ],
            ],
        ];
    }
}
