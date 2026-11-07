<?php

namespace App\Filament\Resources\TransmissionOilChangeResource\Pages;

use App\Filament\Resources\TransmissionOilChangeResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateTransmissionOilChange extends CreateRecord
{
    use \App\Filament\Support\SavesParentRecordFields;

    protected static string $resource = TransmissionOilChangeResource::class;
}
