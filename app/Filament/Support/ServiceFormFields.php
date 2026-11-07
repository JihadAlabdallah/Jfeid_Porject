<?php

namespace App\Filament\Support;

use App\Support\Distance;
use Closure;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;

/**
 * Form fields shared by the forms that create maintenance records
 * (Maintenance Records -> New, and the records table on a car's page).
 */
class ServiceFormFields
{
    /**
     * Odometer fields for an oil change. The unit (km / mi) comes from the
     * car; "next change" fills itself in from the current reading and stays
     * editable.
     *
     * @param  Closure(Get, mixed, mixed): ?string  $unit  resolves the unit from ($get, $record, $livewire)
     */
    public static function oilReadings(Closure $unit): array
    {
        $isOil = fn (Get $get) => $get('type') === 'oil_change';

        return [
            TextInput::make('current_km')
                ->label('Current reading')
                ->numeric()
                ->minValue(0)
                ->suffix(fn (Get $get, $record, $livewire) => Distance::suffix($unit($get, $record, $livewire)))
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get, $record, $livewire) use ($unit) {
                    $next = Distance::nextChange($state, $unit($get, $record, $livewire));

                    if ($next !== null) {
                        $set('next_change_km', $next);
                    }
                })
                ->visible($isOil)
                ->required($isOil),

            TextInput::make('next_change_km')
                ->label('Next change at')
                ->numeric()
                ->minValue(0)
                ->suffix(fn (Get $get, $record, $livewire) => Distance::suffix($unit($get, $record, $livewire)))
                ->helperText('Filled automatically: +5,000 km, or +3,107 mi for cars in miles. You can change it.')
                ->visible($isOil),
        ];
    }

    /** Fields of the "Other" type. Price and employee are the record's own fields. */
    public static function other(): array
    {
        $isOther = fn (Get $get) => $get('type') === 'other';

        return [
            TextInput::make('name')
                ->label('Service name')
                ->placeholder('e.g. Brake pads replacement')
                ->maxLength(255)
                ->visible($isOther)
                ->required($isOther),

            TextInput::make('category')
                ->label('Type')
                ->placeholder('e.g. Brakes')
                ->maxLength(255)
                ->visible($isOther),

            TextInput::make('quantity')
                ->label('Quantity')
                ->numeric()
                ->minValue(0)
                ->visible($isOther),

            Textarea::make('description')
                ->label('Description')
                ->rows(3)
                ->maxLength(5000)
                ->visible($isOther),

            Textarea::make('note')
                ->label('Note')
                ->rows(2)
                ->maxLength(5000)
                ->visible($isOther),
        ];
    }
}
