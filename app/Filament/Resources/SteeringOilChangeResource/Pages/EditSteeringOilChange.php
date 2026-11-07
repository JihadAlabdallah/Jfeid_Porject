<?php

namespace App\Filament\Resources\SteeringOilChangeResource\Pages;

use App\Filament\Resources\SteeringOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditSteeringOilChange extends EditRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = SteeringOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
