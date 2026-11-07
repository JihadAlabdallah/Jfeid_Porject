<?php

namespace App\Filament\Resources\WheelServiceResource\Pages;

use App\Filament\Resources\WheelServiceResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateWheelService extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = WheelServiceResource::class;

    protected static string $maintenanceType = 'wheel_service';
}
