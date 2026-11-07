<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OilChangeResource\Pages;
use App\Models\OilChange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class OilChangeResource extends Resource
{
    protected static ?string $model = OilChange::class;
    protected static ?string $navigationGroup = 'Maintenance';
    protected static ?string $navigationLabel = 'Oil Changes';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['maintenanceRecord.vehicle.user', 'maintenanceRecord']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                \App\Filament\Support\MaintenanceFlow::recordField(),
                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->placeholder('Enter oil type')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('oil_quantity')
                    ->label('Quantity (liters)')
                    ->numeric()
                    ->required(),
                Forms\Components\Select::make('distance_unit')
                    ->label('Unit of these readings')
                    ->options(\App\Support\Distance::LABELS)
                    ->selectablePlaceholder(false)
                    ->required()
                    ->live(),
                Forms\Components\TextInput::make('current_km')
                    ->label('Current reading')
                    ->numeric()
                    ->suffix(fn ($get) => \App\Support\Distance::suffix($get('distance_unit')))
                    ->required(),
                Forms\Components\TextInput::make('next_change_km')
                    ->label('Next change at')
                    ->numeric()
                    ->suffix(fn ($get) => \App\Support\Distance::suffix($get('distance_unit')))
                    ->helperText('Leave empty to fill it automatically (+5,000 km / +3,107 mi).'),
                Forms\Components\TextInput::make('filter')
                    ->label('Filter Type')
                    ->placeholder('Enter filter type')
                    ->maxLength(255)
                    ->required(),
                ...\App\Filament\Support\ParentRecordFields::formFields(withReading: false),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('maintenanceRecord.vehicle.user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.vehicle.type')
                    ->label('Vehicle Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.type')
                    ->label('Maintenance Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('oil_type')
                    ->label('Oil Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('oil_quantity')
                    ->label('Quantity (liters)')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_km')
                    ->label('Current reading')
                    ->formatStateUsing(fn ($state, $record) => \App\Support\Distance::format($state, $record->distance_unit))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_change_km')
                    ->label('Next change at')
                    ->formatStateUsing(fn ($state, $record) => \App\Support\Distance::format($state, $record->distance_unit))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('filter')
                    ->label('Filter Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
                ...\App\Filament\Support\ParentRecordFields::tableColumns(withReading: false),
            ])->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOilChanges::route('/'),
            'create' => Pages\CreateOilChange::route('/create'),
            'edit' => Pages\EditOilChange::route('/{record}/edit'),
        ];
    }
}
