<?php

namespace App\Support;

/**
 * Odometer units and the service intervals used for "Next change at".
 * Readings are stored exactly as entered, in the car's unit; never converted.
 */
class Distance
{
    public const UNITS = ['km', 'miles'];

    public const LABELS = ['km' => 'Kilometers (km)', 'miles' => 'Miles (mi)'];

    public const KM_PER_MILE = 1.609344;

    /**
     * Distance to the next service, in km, per maintenance type. Types not
     * listed (battery, other) have no standard distance: next change is
     * entered by hand. Change a number here to change it everywhere.
     * Cars in miles use the same distance converted (5,000 km = 3,107 mi).
     */
    public const NEXT_CHANGE_KM = [
        'oil_change' => 5000,
        'steering_oil_change' => 60000,
        'transmission_oil_change' => 60000,
        'differential_oil_change' => 60000,
        'gear_oil_change' => 60000,
        'wheel_service' => 10000, // rotation
    ];

    /** Interval for a type in the given unit, or null when there is none. */
    public static function interval(string $type, ?string $unit): ?int
    {
        $km = self::NEXT_CHANGE_KM[$type] ?? null;

        if ($km === null) {
            return null;
        }

        return self::normalize($unit) === 'miles' ? (int) round($km / self::KM_PER_MILE) : $km;
    }

    /** "+60,000 km" / "+37,282 mi", or null when the type has no interval. */
    public static function intervalLabel(string $type, ?string $unit): ?string
    {
        $interval = self::interval($type, $unit);

        return $interval === null ? null : '+' . number_format($interval) . ' ' . self::suffix($unit);
    }

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

    /**
     * Next change reading for a current reading, or null if the reading isn't
     * a number or the type has no standard interval.
     */
    public static function nextChange(mixed $currentReading, ?string $unit, string $type = 'oil_change'): ?int
    {
        $current = self::parse($currentReading);
        $interval = self::interval($type, $unit);

        return $current === null || $interval === null ? null : $current + $interval;
    }
}
