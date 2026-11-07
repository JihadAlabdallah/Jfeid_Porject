<?php

namespace App\Filament\Resources\GearOilChangeResource\Pages;

use App\Filament\Resources\GearOilChangeResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateGearOilChange extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = GearOilChangeResource::class;

    protected static string $maintenanceType = 'gear_oil_change';
}
