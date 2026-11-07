<?php

namespace App\Models;

use App\Models\Vehicle;
use App\Models\OilChange;
use App\Models\WheelService;
use App\Models\GearOilChange;
use App\Models\BatteryService;
use App\Models\SteeringOilChange;
use App\Models\DifferentialOilChange;
use App\Models\TransmissionOilChange;
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
    ];

    protected $casts = [
        'price' => 'float',
    ];

    /**
     * Maps each maintenance type to its detail relation and the form fields
     * that belong to it.
     *
     * @var array<string, array{relation: string, fields: array<string>}>
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
    ];

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

        foreach ($detail['fields'] as $field) {
            // the detail tables are all NOT NULL, so skip rather than write null
            if (! array_key_exists($field, $data) || $data[$field] === null) {
                return;
            }

            $payload[$field] = $data[$field];
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
     * Get the oil change associated with the maintenance record.
     */
    public function oilChange()
    {
        return $this->hasOne(OilChange::class, 'record_id');
    }
}
