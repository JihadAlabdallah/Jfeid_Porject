<?php

namespace App\Support;

use App\Models\MaintenanceRecord;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;

/**
 * Revenue = sum of maintenance_records.price, dated by maintenance_date.
 * Records without a price (everything created before prices existed)
 * count as zero.
 */
class Revenue
{
    public static function between(CarbonInterface $from, CarbonInterface $to): float
    {
        return (float) MaintenanceRecord::query()
            ->whereBetween('maintenance_date', [$from, $to])
            ->sum('price');
    }

    public static function thisMonth(?CarbonInterface $now = null): float
    {
        $now ??= Carbon::now();

        return self::between($now->copy()->startOfMonth(), $now->copy()->endOfMonth());
    }

    /** The current month plus the five before it. */
    public static function lastSixMonths(?CarbonInterface $now = null): float
    {
        $now ??= Carbon::now();

        return self::between($now->copy()->startOfMonth()->subMonths(5), $now->copy()->endOfMonth());
    }

    public static function thisYear(?CarbonInterface $now = null): float
    {
        $now ??= Carbon::now();

        return self::between($now->copy()->startOfYear(), $now->copy()->endOfYear());
    }

    /**
     * Revenue per month, oldest first, e.g. ['Jun 2026' => 120.0, ...].
     *
     * @return array<string, float>
     */
    public static function monthly(int $months = 6, ?CarbonInterface $now = null): array
    {
        $now ??= Carbon::now();
        $result = [];

        for ($i = $months - 1; $i >= 0; $i--) {
            $month = $now->copy()->startOfMonth()->subMonths($i);
            $result[$month->format('M Y')] = self::between($month, $month->copy()->endOfMonth());
        }

        return $result;
    }
}
