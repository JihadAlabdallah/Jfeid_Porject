<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransmissionOilChangeResource\Pages;
use App\Models\TransmissionOilChange;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Filament\Tables\Table;

class TransmissionOilChangeResource extends Resource
{
    protected static ?string $model = TransmissionOilChange::class;
    protected static ?string $navigationGroup = 'Maintenance';
    protected static ?string $navigationLabel = 'Transmission Oil Changes';
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

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity (liters)')
                    ->numeric()
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
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Quantity (liters)')
                    ->numeric()
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenanceRecord.maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
                ...\App\Filament\Support\ParentRecordFields::tableColumns(),
            ])
            ->filters([
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
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransmissionOilChanges::route('/'),
            'create' => Pages\CreateTransmissionOilChange::route('/create'),
            'edit' => Pages\EditTransmissionOilChange::route('/{record}/edit'),
        ];
    }
}
