<?php

namespace App\Filament\Widgets;

use App\Support\Revenue;
use Filament\Widgets\ChartWidget;

class MonthlyRevenueChart extends ChartWidget
{
    protected static ?string $heading = 'Monthly revenue (USD), last 12 months';

    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        $monthly = Revenue::monthly(12);

        return [
            'datasets' => [
                [
                    'label' => 'Revenue (USD)',
                    'data' => array_values($monthly),
                ],
            ],
            'labels' => array_keys($monthly),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
