<?php

namespace App\Filament\Resources\OtherServiceResource\Pages;

use App\Filament\Resources\OtherServiceResource;
use App\Filament\Support\SavesParentRecordFields;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOtherService extends EditRecord
{
    use SavesParentRecordFields;

    protected static string $resource = OtherServiceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
