<?php

namespace App\Filament\Resources\OtherServiceResource\Pages;

use App\Filament\Resources\OtherServiceResource;
use App\Filament\Support\MaintenanceFlow;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListOtherServices extends ListRecords
{
    protected static string $resource = OtherServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->url(MaintenanceFlow::createUrl('other')),
        ];
    }
}
