<?php

namespace App\Filament\Resources\WheelServiceResource\Pages;

use App\Filament\Resources\WheelServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListWheelServices extends ListRecords
{
    protected static string $resource = WheelServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(\App\Filament\Support\MaintenanceFlow::createUrl('wheel_service')),
        ];
    }
}
