<?php

namespace App\Filament\Resources\BatteryServiceResource\Pages;

use App\Filament\Resources\BatteryServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditBatteryService extends EditRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = BatteryServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
