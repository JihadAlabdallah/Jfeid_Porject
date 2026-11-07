<?php

namespace App\Filament\Resources\OilChangeResource\Pages;

use App\Filament\Resources\OilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateOilChange extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = OilChangeResource::class;
}
