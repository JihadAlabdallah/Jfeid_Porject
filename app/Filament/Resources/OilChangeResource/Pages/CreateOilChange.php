<?php

namespace App\Filament\Resources\OilChangeResource\Pages;

use App\Filament\Resources\OilChangeResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateOilChange extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = OilChangeResource::class;

    protected static string $maintenanceType = 'oil_change';
}
