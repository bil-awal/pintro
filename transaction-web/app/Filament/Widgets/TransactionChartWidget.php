<?php

namespace App\Filament\Widgets;

use App\Services\ReportService;
use Filament\Widgets\ChartWidget;
use Carbon\Carbon;

class TransactionChartWidget extends ChartWidget
{
    protected static ?string $heading = 'Transaction Overview (Last 30 Days)';

    protected int | string | array $columnSpan = 2;

    protected static ?int $sort = 1;

    public ?string $filter = 'all';

    protected function getData(): array
    {
        $reportService = new ReportService();
        $trends = $reportService->getDailyTransactionTrends(30);

        $labels = [];
        $transactionCounts = [];
        $completedCounts = [];
        $failedCounts = [];

        foreach ($trends as $trend) {
            $labels[] = Carbon::parse($trend['date'])->format('M d');
            $transactionCounts[] = $trend['count'];
            $completedCounts[] = $trend['completed_count'];
            $failedCounts[] = $trend['failed_count'];
        }

        $datasets = [
            [
                'label' => 'Total Transactions',
                'data' => $transactionCounts,
                'borderColor' => 'rgb(59, 130, 246)',
                'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                'tension' => 0.1,
            ],
        ];

        if ($this->filter === 'all' || $this->filter === 'completed') {
            $datasets[] = [
                'label' => 'Completed Transactions',
                'data' => $completedCounts,
                'borderColor' => 'rgb(34, 197, 94)',
                'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                'tension' => 0.1,
            ];
        }

        if ($this->filter === 'all' || $this->filter === 'failed') {
            $datasets[] = [
                'label' => 'Failed Transactions',
                'data' => $failedCounts,
                'borderColor' => 'rgb(239, 68, 68)',
                'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                'tension' => 0.1,
            ];
        }

        return [
            'datasets' => $datasets,
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getFilters(): ?array
    {
        return [
            'all' => 'All Transactions',
            'completed' => 'Completed Only',
            'failed' => 'Failed Only',
        ];
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
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Transaction Count',
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
