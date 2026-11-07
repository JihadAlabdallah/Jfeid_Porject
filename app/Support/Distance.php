<?php

namespace App\Support;

/**
 * Odometer units and the oil-change interval.
 * Readings are stored exactly as entered, in the car's unit; never converted.
 */
class Distance
{
    public const UNITS = ['km', 'miles'];

    public const LABELS = ['km' => 'Kilometers (km)', 'miles' => 'Miles (mi)'];

    /** Next oil change after the current reading: 5,000 km, or the same distance in miles. */
    public const NEXT_CHANGE_INTERVAL = [
        'km' => 5000,
        'miles' => 3107, // 5,000 km / 1.609344 = 3,106.86 mi
    ];

    public static function normalize(?string $unit): string
    {
        return in_array($unit, self::UNITS, true) ? $unit : 'km';
    }

    public static function suffix(?string $unit): string
    {
        return self::normalize($unit) === 'miles' ? 'mi' : 'km';
    }

    /**
     * Reading as a whole number, accepting "52000", "52,000" or "52 000".
     * Null when it isn't a number.
     */
    public static function parse(mixed $reading): ?int
    {
        $clean = str_replace([',', ' '], '', trim((string) $reading));

        return is_numeric($clean) ? (int) round((float) $clean) : null;
    }

    /** "52,000 km" / "45,000 mi"; non-numeric readings are shown as entered. */
    public static function format(mixed $reading, ?string $unit): ?string
    {
        if (blank($reading)) {
            return null;
        }

        $value = self::parse($reading);

        return ($value === null ? (string) $reading : number_format($value)) . ' ' . self::suffix($unit);
    }

    /** Next change reading for a current reading, or null if the reading isn't a number. */
    public static function nextChange(mixed $currentReading, ?string $unit): ?int
    {
        $current = self::parse($currentReading);

        return $current === null ? null : $current + self::NEXT_CHANGE_INTERVAL[self::normalize($unit)];
    }
}
