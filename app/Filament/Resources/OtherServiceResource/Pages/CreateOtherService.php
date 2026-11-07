<?php

namespace App\Filament\Resources\OtherServiceResource\Pages;

use App\Filament\Resources\OtherServiceResource;
use App\Filament\Support\RedirectsToMaintenanceRecordForm;
use Filament\Resources\Pages\CreateRecord;

/**
 * New services are created from Maintenance Records -> New (one form that
 * saves the record and its details together). This page just sends the admin
 * there with "Other" pre-selected.
 */
class CreateOtherService extends CreateRecord
{
    use RedirectsToMaintenanceRecordForm;

    protected static string $resource = OtherServiceResource::class;

    protected static string $maintenanceType = 'other';
}
