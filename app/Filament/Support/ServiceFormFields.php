<?php

namespace App\Filament\Support;

use App\Models\MaintenanceRecord;
use App\Support\Distance;
use Illuminate\Support\Carbon;
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

    /**
     * Odometer reading + next change for every type except Oil Change (which
     * has its own fields). Saved on the record. Next change fills itself in
     * for types with a standard interval and stays editable.
     *
     * @param  Closure(Get, mixed, mixed): ?string  $unit  resolves the unit from ($get, $record, $livewire)
     */
    public static function reading(Closure $unit): array
    {
        $visible = fn (Get $get) => filled($get('type')) && $get('type') !== 'oil_change';
        $suffix = fn (Get $get, $record, $livewire) => Distance::suffix($unit($get, $record, $livewire));

        return [
            TextInput::make('current_reading')
                ->label('Current reading')
                ->numeric()
                ->minValue(0)
                ->maxValue(4294967295)
                ->suffix($suffix)
                ->helperText('Odometer at the time of the service (optional).')
                ->live(onBlur: true)
                ->afterStateUpdated(function ($state, Set $set, Get $get, $record, $livewire) use ($unit) {
                    $next = Distance::nextChange($state, $unit($get, $record, $livewire), (string) $get('type'));

                    if ($next !== null) {
                        $set('next_change_reading', $next);
                    }
                })
                ->visible($visible),

            TextInput::make('next_change_reading')
                ->label('Next change at')
                ->numeric()
                ->minValue(0)
                ->maxValue(4294967295)
                ->suffix($suffix)
                ->helperText(function (Get $get, $record, $livewire) use ($unit) {
                    $label = Distance::intervalLabel((string) $get('type'), $unit($get, $record, $livewire));

                    return $label === null
                        ? 'Optional. Enter it yourself: this service has no standard distance.'
                        : "Filled automatically: {$label}. You can change it.";
                })
                ->visible($visible),
        ];
    }

    /**
     * New record only: fill the type's details (oil type, quantity, filter,
     * brand, ...) from the car's last service of that type. Runs when the car
     * or the type is chosen; a car with no such service clears them, so values
     * from another car or type never stay behind.
     */
    public static function fillFromLastService(Get $get, Set $set, mixed $vehicleId, mixed $record): void
    {
        $type = (string) $get('type');

        if ($record !== null || ! isset(MaintenanceRecord::CARRY_OVER_FIELDS[$type])) {
            return;
        }

        $values = MaintenanceRecord::lastServiceOf($vehicleId, $type)?->carryOverValues() ?? [];

        foreach (MaintenanceRecord::CARRY_OVER_FIELDS[$type] as $field) {
            $set($field, $values[$field] ?? null);
        }
    }

    /** Note under the type field, saying where the filled-in details came from. */
    public static function lastServiceHint(Get $get, mixed $vehicleId, mixed $record): ?string
    {
        if ($record !== null) {
            return null;
        }

        $last = MaintenanceRecord::lastServiceOf($vehicleId, $get('type'));

        return $last === null
            ? null
            : 'Details filled in from this car’s last ' . MaintenanceRecord::TYPE_LABELS[$last->type]
                . ' (' . Carbon::parse($last->maintenance_date)->format('M j, Y') . '). Check them and change if needed.';
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
