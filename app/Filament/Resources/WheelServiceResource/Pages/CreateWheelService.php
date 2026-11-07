<?php

namespace App\Filament\Resources\WheelServiceResource\Pages;

use App\Filament\Resources\WheelServiceResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateWheelService extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = WheelServiceResource::class;
}
