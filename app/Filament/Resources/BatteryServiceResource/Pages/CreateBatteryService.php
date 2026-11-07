<?php

namespace App\Filament\Resources\BatteryServiceResource\Pages;

use App\Filament\Resources\BatteryServiceResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateBatteryService extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = BatteryServiceResource::class;

    protected static string $maintenanceType = 'battery_service';
}
