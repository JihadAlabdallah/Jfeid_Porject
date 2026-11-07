<?php

namespace App\Filament\Support;

use App\Support\Distance;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\TextColumn;

/**
 * Current reading, technician + price fields for the detail pages (Oil Changes, Battery
 * Services, ...). The values live on the parent maintenance record, so the
 * fields are not saved on the detail row itself: they are loaded from the
 * selected maintenance record and written back to it by
 * SavesParentRecordFields after create/save.
 */
class ParentRecordFields
{
    public const FIELDS = ['current_reading', 'next_change_reading', 'technician_name', 'price'];

    /** Unit shown next to a reading: the record's own, else its car's. */
    public static function unitOf($maintenanceRecord): string
    {
        return Distance::suffix($maintenanceRecord?->distance_unit ?? $maintenanceRecord?->vehicle?->distance_unit);
    }

    /**
     * @param  bool  $withReading  false on Oil Changes, which have their own readings
     */
    public static function formFields(bool $withReading = true): array
    {
        $reading = TextInput::make('current_reading')
            ->label('Current reading')
            ->numeric()
            ->minValue(0)
            ->maxValue(4294967295)
            ->suffix(fn ($record) => self::unitOf($record?->maintenanceRecord))
            ->dehydrated(false)
            ->afterStateHydrated(fn (TextInput $component, $record) => $component->state($record?->maintenanceRecord?->current_reading))
            ->live(onBlur: true)
            ->afterStateUpdated(function ($state, \Filament\Forms\Set $set, $record) {
                $parent = $record?->maintenanceRecord;
                $next = $parent ? Distance::nextChange($state, $parent->distance_unit ?? $parent->vehicle?->distance_unit, $parent->type) : null;

                if ($next !== null) {
                    $set('next_change_reading', $next);
                }
            });

        $nextChange = TextInput::make('next_change_reading')
            ->label('Next change at')
            ->numeric()
            ->minValue(0)
            ->maxValue(4294967295)
            ->suffix(fn ($record) => self::unitOf($record?->maintenanceRecord))
            ->helperText(function ($record) {
                $parent = $record?->maintenanceRecord;
                $label = $parent ? Distance::intervalLabel($parent->type, $parent->distance_unit ?? $parent->vehicle?->distance_unit) : null;

                return $label === null
                    ? 'Optional. Enter it yourself: this service has no standard distance.'
                    : "Filled automatically: {$label}. You can change it.";
            })
            ->dehydrated(false)
            ->afterStateHydrated(fn (TextInput $component, $record) => $component->state($record?->maintenanceRecord?->next_change_reading));

        return [
            ...($withReading ? [$reading, $nextChange] : []),
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

    public static function tableColumns(bool $withReading = true): array
    {
        $unit = fn ($record) => $record->maintenanceRecord?->distance_unit ?? $record->maintenanceRecord?->vehicle?->distance_unit;

        $reading = TextColumn::make('maintenanceRecord.current_reading')
            ->label('Reading')
            ->formatStateUsing(fn ($state, $record) => Distance::format($state, $unit($record)))
            ->placeholder('-')
            ->toggleable();

        $nextChange = TextColumn::make('maintenanceRecord.next_change_reading')
            ->label('Next change')
            ->formatStateUsing(fn ($state, $record) => Distance::format($state, $unit($record)))
            ->placeholder('-')
            ->toggleable();

        return [
            ...($withReading ? [$reading, $nextChange] : []),
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
