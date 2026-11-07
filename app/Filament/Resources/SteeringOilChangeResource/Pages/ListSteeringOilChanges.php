<?php

namespace App\Filament\Resources\SteeringOilChangeResource\Pages;

use App\Filament\Resources\SteeringOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListSteeringOilChanges extends ListRecords
{
    protected static string $resource = SteeringOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('steering_oil_change')),
        ];
    }
}
