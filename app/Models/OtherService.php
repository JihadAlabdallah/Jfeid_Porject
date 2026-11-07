<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Details of an "other" maintenance record: any service outside the fixed
 * types. Price and employee are on the maintenance record itself.
 */
class OtherService extends Model
{
    protected $fillable = [
        'record_id',
        'name',
        'category',
        'description',
        'note',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'float',
    ];

    public function maintenanceRecord()
    {
        return $this->belongsTo(MaintenanceRecord::class, 'record_id');
    }
}
