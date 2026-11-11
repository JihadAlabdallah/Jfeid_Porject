<?php

namespace App\Models;

use App\Models\User;
use App\Models\MaintenanceRecord;
use App\Support\Distance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehicle extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'type',
        'carplate',
        'distance_unit',
    ];

    /** New cars are in km unless set otherwise. */
    protected $attributes = [
        'distance_unit' => 'km',
    ];

    /**
     * Get the maintenance records for the vehicle.
     */
   public function maintenanceRecords()
{
    return $this->hasMany(MaintenanceRecord::class);
}
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The car's latest odometer reading: the reading of its most recent
     * service that has one (by service date, then newest entry), in the car's
     * current unit. Null when no service has a reading yet.
     */
    public function currentReading(): ?int
    {
        $records = $this->maintenanceRecords()
            ->with('oilChange')
            ->orderByDesc('maintenance_date')
            ->orderByDesc('id')
            ->get();

        foreach ($records as $record) {
            $record->setRelation('vehicle', $this);
            $reading = $record->reading();

            if ($reading['current_reading'] === null) {
                continue;
            }

            $value = (int) $reading['current_reading'];
            $from = Distance::normalize($reading['distance_unit']);
            $to = Distance::normalize($this->distance_unit);

            if ($from === $to) {
                return $value;
            }

            // entered before the car's unit was switched
            return (int) round($to === 'miles' ? $value / Distance::KM_PER_MILE : $value * Distance::KM_PER_MILE);
        }

        return null;
    }

    /** What the app gets for each car in vehicle lists. */
    public function summary(): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'carplate' => $this->carplate,
            'current_reading' => $this->currentReading(),
            'distance_unit' => Distance::normalize($this->distance_unit),
        ];
    }

    /** @return array<int, array<string, mixed>> */
    public static function summariesFor(User $user): array
    {
        return $user->vehicles()->orderBy('id')->get()->map->summary()->values()->all();
    }
}
