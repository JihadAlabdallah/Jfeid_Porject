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
                Forms\Components\Select::make('record_id')
                    ->relationship('maintenanceRecord', 'id')
                    ->label('Maintenance Record')
                    ->required(),
                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->placeholder('Enter oil type')
                    ->maxLength(255)
                    ->required(),

                Forms\Components\TextInput::make('oil_quantity')
                    ->label('Quantity (liters)')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('current_km')
                    ->label('Current KM')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('next_change_km')
                    ->label('Next Change KM')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('filter')
                    ->label('Filter Type')
                    ->placeholder('Enter filter type')
                    ->maxLength(255)
                    ->required(),
                ...\App\Filament\Support\ParentRecordFields::formFields(),
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
                    ->label('Current KM')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('next_change_km')
                    ->label('Next Change KM')
                    ->numeric()
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
                ...\App\Filament\Support\ParentRecordFields::tableColumns(),
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
