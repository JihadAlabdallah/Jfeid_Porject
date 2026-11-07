<?php

namespace App\Filament\Resources\OilChangeResource\Pages;

use App\Filament\Resources\OilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOilChanges extends ListRecords
{
    protected static string $resource = OilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('oil_change')),
        ];
    }
}
