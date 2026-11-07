<?php

namespace App\Filament\Resources\TransmissionOilChangeResource\Pages;

use App\Filament\Resources\TransmissionOilChangeResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with the type pre-selected.
 */
class CreateTransmissionOilChange extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = TransmissionOilChangeResource::class;

    protected static string $maintenanceType = 'transmission_oil_change';
}
