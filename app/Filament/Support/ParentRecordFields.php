<?php

namespace App\Filament\Support;

use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

/**
 * Technician + price fields for the detail pages (Oil Changes, Battery
 * Services, ...). The values live on the parent maintenance record, so the
 * fields are not saved on the detail row itself: they are loaded from the
 * selected maintenance record and written back to it by
 * SavesParentRecordFields after create/save.
 */
class ParentRecordFields
{
    public const FIELDS = ['technician_name', 'price'];

    public static function formFields(): array
    {
        return [
            TextInput::make('technician_name')
                ->label('Technician / Employee')
                ->maxLength(255)
                ->dehydrated(false)
                ->afterStateHydrated(fn (TextInput $component, $record) => $component->state($record?->maintenanceRecord?->technician_name)),

            TextInput::make('price')
                ->label('Price (USD)')
                ->numeric()
                ->minValue(0)
                ->maxValue(99999999.99)
                ->step('0.01')
                ->prefix('$')
                ->dehydrated(false)
                ->afterStateHydrated(fn (TextInput $component, $record) => $component->state($record?->maintenanceRecord?->price)),
        ];
    }

    public static function tableColumns(): array
    {
        return [
            TextColumn::make('maintenanceRecord.technician_name')
                ->label('Technician')
                ->placeholder('-')
                ->searchable()
                ->toggleable(),
            TextColumn::make('maintenanceRecord.price')
                ->label('Price')
                ->money('USD')
                ->placeholder('-')
                ->sortable()
                ->toggleable(),
        ];
    }
}
