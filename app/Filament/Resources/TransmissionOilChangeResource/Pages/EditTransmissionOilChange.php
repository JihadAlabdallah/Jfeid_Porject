<?php

namespace App\Filament\Resources\TransmissionOilChangeResource\Pages;

use App\Filament\Resources\TransmissionOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditTransmissionOilChange extends EditRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = TransmissionOilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
