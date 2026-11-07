<?php

namespace App\Filament\Resources\GearOilChangeResource\Pages;

use App\Filament\Resources\GearOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListGearOilChanges extends ListRecords
{
    protected static string $resource = GearOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('gear_oil_change')),
        ];
    }
}
