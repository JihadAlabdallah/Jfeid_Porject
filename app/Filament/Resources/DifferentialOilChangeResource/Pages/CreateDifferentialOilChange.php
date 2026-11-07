<?php

namespace App\Filament\Resources\DifferentialOilChangeResource\Pages;

use App\Filament\Resources\DifferentialOilChangeResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateDifferentialOilChange extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = DifferentialOilChangeResource::class;

    protected static string $maintenanceType = 'differential_oil_change';
}
