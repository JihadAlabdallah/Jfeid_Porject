<?php

namespace App\Filament\Support;

use App\Filament\Resources\MaintenanceRecordResource;
use App\Models\MaintenanceRecord;
use Filament\Forms\Components\Select;

/**
 * Single way to create a service: Maintenance Records -> New.
 *
 * The detail pages (Oil Changes, Battery Services, ...) send their "New"
 * button there with the type pre-selected, so record_id is always set by the
 * system from the record it just saved, never picked by hand.
 */
class MaintenanceFlow
{
    /** Main create form with the maintenance type pre-selected. */
    public static function createUrl(string $type): string
    {
        return MaintenanceRecordResource::getUrl('create', ['type' => $type]);
    }

    /** The type passed in ?type=, if it is a real maintenance type. */
    public static function typeFromRequest(): ?string
    {
        $type = request()->query('type');

        return is_string($type) && array_key_exists($type, MaintenanceRecord::TYPE_DETAILS) ? $type : null;
    }

    public static function recordLabel(MaintenanceRecord $record): string
    {
        return implode(' · ', array_filter([
            '#' . $record->id,
            $record->vehicle?->user?->name,
            $record->vehicle?->type,
            $record->maintenance_date ? \Illuminate\Support\Carbon::parse($record->maintenance_date)->format('d M Y') : null,
        ]));
    }

    /**
     * The maintenance record on the detail edit pages: shown read-only so the
     * details can never be moved to another record. Disabled fields are not
     * saved, so record_id keeps its existing value.
     */
    public static function recordField(): Select
    {
        return Select::make('record_id')
            ->relationship('maintenanceRecord', 'id')
            ->getOptionLabelFromRecordUsing(fn (MaintenanceRecord $record) => self::recordLabel($record))
            ->label('Maintenance Record')
            ->disabled()
            ->helperText('To change the customer, car, date or type, edit it from Maintenance Records.');
    }
}
