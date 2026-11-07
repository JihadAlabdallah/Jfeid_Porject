<?php

namespace App\Filament\Resources\TransmissionOilChangeResource\Pages;

use App\Filament\Resources\TransmissionOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListTransmissionOilChanges extends ListRecords
{
    protected static string $resource = TransmissionOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('transmission_oil_change')),
        ];
    }
}
