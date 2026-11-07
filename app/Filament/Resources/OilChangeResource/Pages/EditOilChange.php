<?php

namespace App\Filament\Resources\OilChangeResource\Pages;

use App\Filament\Resources\OilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOilChange extends EditRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = OilChangeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
