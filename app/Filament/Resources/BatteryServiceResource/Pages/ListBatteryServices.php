<?php

namespace App\Filament\Resources\BatteryServiceResource\Pages;

use App\Filament\Resources\BatteryServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBatteryServices extends ListRecords
{
    protected static string $resource = BatteryServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('battery_service')),
        ];
    }
}
