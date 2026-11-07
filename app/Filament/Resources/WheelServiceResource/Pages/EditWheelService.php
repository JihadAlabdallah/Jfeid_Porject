<?php

namespace App\Filament\Resources\WheelServiceResource\Pages;

use App\Filament\Resources\WheelServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditWheelService extends EditRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = WheelServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
