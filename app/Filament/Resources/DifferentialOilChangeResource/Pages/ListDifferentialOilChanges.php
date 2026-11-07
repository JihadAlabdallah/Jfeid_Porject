<?php

namespace App\Filament\Resources\DifferentialOilChangeResource\Pages;

use App\Filament\Resources\DifferentialOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDifferentialOilChanges extends ListRecords
{
    protected static string $resource = DifferentialOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('differential_oil_change')),
        ];
    }
}
