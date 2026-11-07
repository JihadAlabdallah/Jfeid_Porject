<?php

namespace App\Filament\Resources\DifferentialOilChangeResource\Pages;

use App\Filament\Resources\DifferentialOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateDifferentialOilChange extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = DifferentialOilChangeResource::class;
}
