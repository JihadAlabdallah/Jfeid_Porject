<?php

namespace App\Models;

use App\Models\MaintenanceRecord;
use App\Support\Distance;
use Illuminate\Database\Eloquent\Model;

class OilChange extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'record_id',
        'current_km',
        'oil_type',
        'oil_quantity',
        'filter',
        'next_change_km',
        'distance_unit',
    ];

    /**
     * Applies to every way an oil change is saved (admin forms, the car page,
     * the app's API), so the rules live in one place:
     * - the unit defaults to the car's unit at the time of the service;
     * - an empty "next change" is filled with current + 5,000 km / 3,107 mi.
     */
    protected static function booted(): void
    {
        static::saving(function (OilChange $oil) {
            if (blank($oil->distance_unit) || ! in_array($oil->distance_unit, Distance::UNITS, true)) {
                $oil->distance_unit = Distance::normalize(
                    $oil->maintenanceRecord()->first()?->vehicle?->distance_unit
                );
            }

            if (blank($oil->next_change_km)) {
                $next = Distance::nextChange($oil->current_km, $oil->distance_unit);

                if ($next !== null) {
                    $oil->next_change_km = (string) $next;
                }
            }
        });
    }

    /**
     * Get the maintenance record that owns the oil change.
     */
    public function maintenanceRecord()
    {
        return $this->belongsTo(MaintenanceRecord::class, 'record_id');
    }
}
