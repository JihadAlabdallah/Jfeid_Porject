<?php

namespace App\Models;

use App\Models\Vehicle;
use App\Models\OilChange;
use App\Models\OtherService;
use App\Models\WheelService;
use App\Models\GearOilChange;
use App\Models\BatteryService;
use App\Models\SteeringOilChange;
use App\Models\DifferentialOilChange;
use App\Models\TransmissionOilChange;
use App\Support\Distance;
use Illuminate\Database\Eloquent\Model;

class MaintenanceRecord extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'vehicle_id',
        'maintenance_date',
        'type',
        'technician_name',
        'price',
        'current_reading',
        'next_change_reading',
        'distance_unit',
    ];

    protected $casts = [
        'price' => 'float',
        'current_reading' => 'integer',
        'next_change_reading' => 'integer',
    ];

    /**
     * - A reading is saved in the car's unit at the time of the service, so
     *   switching the car between km and miles later never changes it.
     * - An empty "next change" is filled from the reading + the type's
     *   interval (oil changes do this on the oil change itself).
     */
    protected static function booted(): void
    {
        static::saving(function (MaintenanceRecord $record) {
            $hasReading = $record->current_reading !== null || $record->next_change_reading !== null;

            if ($hasReading && blank($record->distance_unit)) {
                $record->distance_unit = Distance::normalize($record->vehicle()->first()?->distance_unit);
            }

            if ($record->next_change_reading === null && $record->current_reading !== null && $record->type !== 'oil_change') {
                $record->next_change_reading = Distance::nextChange($record->current_reading, $record->distance_unit, $record->type);
            }
        });
    }

    /**
     * Odometer reading of this service for the app: oil changes keep theirs
     * on the oil change, every other type on the record.
     *
     * @return array{current_reading: ?int, next_change_reading: ?int, distance_unit: string}
     */
    public function reading(): array
    {
        if ($this->type === 'oil_change' && $this->oilChange) {
            return [
                'current_reading' => $this->current_reading ?? Distance::parse($this->oilChange->current_km),
                'next_change_reading' => $this->next_change_reading ?? Distance::parse($this->oilChange->next_change_km),
                'distance_unit' => Distance::normalize($this->oilChange->distance_unit),
            ];
        }

        return [
            'current_reading' => $this->current_reading,
            'next_change_reading' => $this->next_change_reading,
            'distance_unit' => Distance::normalize($this->distance_unit ?? $this->vehicle?->distance_unit),
        ];
    }

    /**
     * Maps each maintenance type to its detail relation and the form fields
     * that belong to it. "required" lists the fields that must be filled for
     * the detail row to be written (defaults to all of them).
     *
     * @var array<string, array{relation: string, fields: array<string>, required?: array<string>}>
     */
    public const TYPE_DETAILS = [
        'battery_service' => [
            'relation' => 'batteryService',
            'fields' => ['brand', 'size'],
        ],
        'wheel_service' => [
            'relation' => 'wheelService',
            'fields' => ['wheel_name', 'quantity', 'wheel_size'],
        ],
        'oil_change' => [
            'relation' => 'oilChange',
            'fields' => ['oil_type', 'oil_quantity', 'current_km', 'next_change_km', 'filter'],
            // next_change_km is filled automatically when left empty (see OilChange)
            'required' => ['oil_type', 'oil_quantity', 'current_km', 'filter'],
        ],
        'gear_oil_change' => [
            'relation' => 'gearOilChange',
            'fields' => ['oil_type', 'quantity'],
        ],
        'differential_oil_change' => [
            'relation' => 'differentialOilChange',
            'fields' => ['oil_type', 'quantity'],
        ],
        'transmission_oil_change' => [
            'relation' => 'transmissionOilChange',
            'fields' => ['oil_type', 'quantity'],
        ],
        'steering_oil_change' => [
            'relation' => 'steeringOilChange',
            'fields' => ['oil_type', 'quantity'],
        ],
        'other' => [
            'relation' => 'otherService',
            'fields' => ['name', 'category', 'description', 'note', 'quantity'],
            'required' => ['name'],
        ],
    ];

    /** Labels for the maintenance type dropdowns. */
    public const TYPE_LABELS = [
        'oil_change' => 'Oil Change',
        'steering_oil_change' => 'Steering Oil Change',
        'transmission_oil_change' => 'Transmission Oil Change',
        'differential_oil_change' => 'Differential Oil Change',
        'gear_oil_change' => 'Gear Oil Change',
        'battery_service' => 'Battery Service',
        'wheel_service' => 'Wheel Service',
        'other' => 'Other',
    ];

    /**
     * The type-specific values (oil_type, brand, ...) from the detail row, so
     * an edit form opens filled in. Empty when there is no detail row yet.
     *
     * @return array<string, mixed>
     */
    public function typeDetailValues(): array
    {
        $detail = self::TYPE_DETAILS[$this->type] ?? null;
        $related = $detail ? $this->{$detail['relation']} : null;

        if ($related === null) {
            return [];
        }

        $values = [];
        foreach ($detail['fields'] as $field) {
            $values[$field] = $related->{$field};
        }

        return $values;
    }

    /**
     * Persist the type-specific detail row (wheel service, oil change, ...)
     * from submitted form data. Safe to call on both create and update.
     *
     * @param array<string, mixed> $data
     */
    public function syncTypeDetails(array $data): void
    {
        $detail = self::TYPE_DETAILS[$this->type] ?? null;

        if ($detail === null) {
            return;
        }

        $payload = [];
        $required = $detail['required'] ?? $detail['fields'];

        foreach ($detail['fields'] as $field) {
            $value = $data[$field] ?? null;
            $value = $value === '' ? null : $value;

            // a required field is missing: write nothing rather than a broken row
            if ($value === null && in_array($field, $required, true)) {
                return;
            }

            $payload[$field] = $value;
        }

        $this->{$detail['relation']}()->updateOrCreate([], $payload);

        $this->removeStaleTypeDetails();
    }

    /**
     * Drop detail rows left behind by a previous type. Editing a record's type
     * would otherwise orphan the old row, and the API eager-loads every
     * relation, so the app would receive both the old and the new details.
     */
    public function removeStaleTypeDetails(): void
    {
        foreach (self::TYPE_DETAILS as $type => $detail) {
            if ($type === $this->type) {
                continue;
            }

            $this->{$detail['relation']}()->delete();
        }
    }

    /**
     * Get the vehicle that owns the maintenance record.
     */
    public function vehicle()
    {
        return $this->belongsTo(Vehicle::class);
    }

    /**
     * Get the steering oil change associated with the maintenance record.
     */
    public function steeringOilChange()
    {
        return $this->hasOne(SteeringOilChange::class,'record_id');
    }

    /**
     * Get the battery service associated with the maintenance record.
     */
    public function batteryService()
    {
      return $this->hasOne(BatteryService::class, 'record_id');
    }
    /**
     * Get the wheel service associated with the maintenance record.
     */
    public function wheelService()
    {
       return $this->hasOne(WheelService::class, 'record_id');
    }
    /**
     * Get the transmission oil change associated with the maintenance record.
     */
    public function transmissionOilChange()
    {
        return $this->hasOne(TransmissionOilChange::class,'record_id');
    }
    /**
     * Get the differential oil change associated with the maintenance record.
     */
    public function differentialOilChange()
    {
        return $this->hasOne(DifferentialOilChange::class,'record_id');
    }
    /**
     * Get the gear oil change associated with the maintenance record.
     */
    public function gearOilChange()
    {
        return $this->hasOne(GearOilChange::class,'record_id');
    }
    /**
     * Get the "other" service details associated with the maintenance record.
     */
    public function otherService()
    {
        return $this->hasOne(OtherService::class, 'record_id');
    }

    /**
     * Get the oil change associated with the maintenance record.
     */
    public function oilChange()
    {
        return $this->hasOne(OilChange::class, 'record_id');
    }
}
