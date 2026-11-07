<?php

namespace App\Filament\Resources\SteeringOilChangeResource\Pages;

use App\Filament\Resources\SteeringOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSteeringOilChange extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = SteeringOilChangeResource::class;
}
