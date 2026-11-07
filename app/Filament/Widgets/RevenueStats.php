<?php

namespace App\Filament\Widgets;

use App\Support\Revenue;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Carbon;

class RevenueStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $now = Carbon::now();
        $money = fn (float $amount) => '$' . number_format($amount, 2);

        return [
            Stat::make('Revenue this month', $money(Revenue::thisMonth($now)))
                ->description($now->format('F Y'))
                ->icon('heroicon-o-banknotes'),
            Stat::make('Revenue last 6 months', $money(Revenue::lastSixMonths($now)))
                ->description($now->copy()->startOfMonth()->subMonths(5)->format('M Y') . ' to ' . $now->format('M Y'))
                ->chart(array_values(Revenue::monthly(6, $now)))
                ->icon('heroicon-o-chart-bar'),
            Stat::make('Revenue this year', $money(Revenue::thisYear($now)))
                ->description((string) $now->year)
                ->icon('heroicon-o-calendar'),
        ];
    }
}
