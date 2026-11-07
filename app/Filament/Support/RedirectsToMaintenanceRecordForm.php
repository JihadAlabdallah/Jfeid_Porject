<?php

namespace App\Filament\Support;

use App\Filament\Resources\MaintenanceRecordResource;
use App\Models\MaintenanceRecord;

/**
 * For the detail Create pages (e.g. /Jfeid/oil-changes/create): instead of
 * the old "pick a maintenance record by number" form, go to the main form
 * with the type pre-selected. Uses: protected static string $maintenanceType.
 */
trait RedirectsToMaintenanceRecordForm
{
    public function mount(): void
    {
        // old links that passed ?record_id=: open that record to complete it
        $recordId = request()->query('record_id');

        if ($recordId !== null && MaintenanceRecord::whereKey($recordId)->exists()) {
            $this->redirect(MaintenanceRecordResource::getUrl('edit', ['record' => $recordId]));

            return;
        }

        $this->redirect(MaintenanceFlow::createUrl(static::$maintenanceType));
    }
}
