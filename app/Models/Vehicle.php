<?php

namespace App\Models;

use App\Models\User;
use App\Models\MaintenanceRecord;
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
}
