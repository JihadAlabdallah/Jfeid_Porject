<?php

namespace App\Filament\Resources\BatteryServiceResource\Pages;

use App\Filament\Resources\BatteryServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBatteryService extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = BatteryServiceResource::class;
protected function mutateFormDataBeforeCreate(array $data): array
{
    $data['record_id'] = $data['record_id'] ?? request()->query('record_id');
    return $data;
}
}
