<?php

namespace App\Filament\Resources\MaintenanceRecordsRelationResource\RelationManagers;

use Filament\Forms;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;

class MaintenanceRecordsRelationManager extends RelationManager
{
    protected static string $relationship = 'maintenanceRecords';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('type')
                    ->label('Maintenance Type')
                    ->options(\App\Models\MaintenanceRecord::TYPE_LABELS)
                    ->required()
                    ->live(),

                Forms\Components\DateTimePicker::make('maintenance_date')
                    ->label('Maintenance Date')
                    ->required()
                    ->default(now()),
                Forms\Components\TextInput::make('brand')
                    ->label('Brand')
                    ->visible(fn($get) => $get('type') === 'battery_service')
                    ->required(fn($get) => $get('type') === 'battery_service'),

                Forms\Components\TextInput::make('size')
                    ->label(' Size')
                    ->visible(fn($get) => $get('type') === 'battery_service')
                    ->required(fn($get) => $get('type') === 'battery_service'),
                Forms\Components\TextInput::make('wheel_name')
                    ->label('Wheel Name')
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

                Forms\Components\TextInput::make('wheel_size')
                    ->label('Wheel Size')
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'gear_oil_change')
                    ->required(fn($get) => $get('type') === 'gear_oil_change'),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'gear_oil_change')
                    ->required(fn($get) => $get('type') === 'gear_oil_change'),

                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'differential_oil_change')
                    ->required(fn($get) => $get('type') === 'differential_oil_change'),

                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'differential_oil_change')
                    ->required(fn($get) => $get('type') === 'differential_oil_change'),

                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                Forms\Components\TextInput::make('oil_quantity')
                    ->label('Oil Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                // unit of the car whose page this is
                ...\App\Filament\Support\ServiceFormFields::oilReadings(
                    fn ($get, $record, $livewire) => $record?->oilChange?->distance_unit
                        ?? $livewire->getOwnerRecord()->distance_unit
                ),
                ...\App\Filament\Support\ServiceFormFields::other(),
                ...\App\Filament\Support\ServiceFormFields::reading(
                    fn ($get, $record, $livewire) => $record?->distance_unit
                        ?? $livewire->getOwnerRecord()->distance_unit
                ),
                Forms\Components\TextInput::make('filter')
                    ->label('Filter Type')
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'transmission_oil_change')
                    ->required(fn($get) => $get('type') === 'transmission_oil_change'),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'transmission_oil_change')
                    ->required(fn($get) => $get('type') === 'transmission_oil_change'),
                Forms\Components\TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'steering_oil_change')
                    ->required(fn($get) => $get('type') === 'steering_oil_change'),
                Forms\Components\TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'steering_oil_change')
                    ->required(fn($get) => $get('type') === 'steering_oil_change'),
                // who did the service and what it cost (same as Maintenance Records)
                Forms\Components\TextInput::make('technician_name')
                    ->label('Technician / Employee')
                    ->maxLength(255),
                Forms\Components\TextInput::make('price')
                    ->label('Price (USD)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(99999999.99)
                    ->step('0.01')
                    ->prefix('$'),
            ])->columns(2);
    }
    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('MaintenanceRecord')
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Maintenance Type')
                    ->formatStateUsing(fn ($state) => \App\Models\MaintenanceRecord::TYPE_LABELS[$state] ?? $state)
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
                Tables\Columns\TextColumn::make('current_reading')
                    ->label('Reading')
                    ->getStateUsing(fn ($record) => $record->reading()['current_reading'])
                    ->formatStateUsing(fn ($state, $record) => \App\Support\Distance::format($state, $record->reading()['distance_unit']))
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('next_change_reading')
                    ->label('Next change')
                    ->getStateUsing(fn ($record) => $record->reading()['next_change_reading'])
                    ->formatStateUsing(fn ($state, $record) => \App\Support\Distance::format($state, $record->reading()['distance_unit']))
                    ->placeholder('-')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('technician_name')
                    ->label('Technician')
                    ->placeholder('-')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price')
                    ->money('USD')
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable()
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->money('USD')),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(fn ($record, $data) => $record->syncTypeDetails($data)),
            ])
            ->actions([
                // same as the Maintenance Records edit page: open with the
                // details (oil type, brand, ...) filled in and save them back
                Tables\Actions\EditAction::make()
                    ->mutateRecordDataUsing(fn (array $data, $record) => $record->typeDetailValues() + $data)
                    ->after(fn ($record, $data) => $record->syncTypeDetails($data)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
