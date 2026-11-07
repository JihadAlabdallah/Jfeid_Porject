<?php

namespace App\Filament\Resources\GearOilChangeResource\Pages;

use App\Filament\Resources\GearOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateGearOilChange extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = GearOilChangeResource::class;
}
