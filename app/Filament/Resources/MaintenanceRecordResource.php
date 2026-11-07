<?php

namespace App\Filament\Resources;

use App\Models\User;
use App\Models\Vehicle;
use Filament\Tables;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\MaintenanceRecord;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\MaintenanceRecordResource\Pages;
use Illuminate\Database\Eloquent\Builder;
use App\Models\BatteryService;
use App\Models\WheelService;
use App\Models\OilChange;
use App\Models\GearOilChange;
use App\Models\DifferentialOilChange;
use App\Models\TransmissionOilChange;
use App\Models\SteeringOilChange;

class MaintenanceRecordResource extends Resource
{
    protected static ?string $model = MaintenanceRecord::class;
    protected static ?string $navigationGroup = 'MaintenanceRecord';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static string $panel = 'jfeid';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['vehicle.user']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // pick the customer first; the vehicle list below is scoped to them.
                // not a real column on maintenance_records, so it is never saved.
                Select::make('user_id')
                    ->label('User')
                    ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->required()
                    ->live()
                    ->dehydrated(false)
                    ->afterStateHydrated(function (Select $component, $state, $record) {
                        // on edit, derive the user from the record's vehicle
                        if (blank($state) && $record?->vehicle) {
                            $component->state($record->vehicle->user_id);
                        }
                    })
                    ->afterStateUpdated(fn(Set $set) => $set('vehicle_id', null)),

                Select::make('vehicle_id')
                    ->label('Vehicle')
                    ->options(fn(Get $get) => blank($get('user_id'))
                        ? []
                        : Vehicle::where('user_id', $get('user_id'))
                            ->orderBy('id')
                            ->get()
                            ->mapWithKeys(fn(Vehicle $vehicle) => [
                                $vehicle->id => ($vehicle->type ?: 'Vehicle') . ' (#' . $vehicle->id . ')',
                            ]))
                    ->placeholder('Select a user first')
                    ->searchable()
                    ->required(),


                Select::make('type')
                    ->label('Maintenance Type')
                    ->options([
                        'oil_change' => 'Oil Change',
                        'steering_oil_change' => 'Steering Oil Change',
                        'transmission_oil_change' => 'Transmission Oil Change',
                        'differential_oil_change' => 'Differential Oil Change',
                        'gear_oil_change' => 'Gear Oil Change',
                        'battery_service' => 'Battery service',
                        'wheel_service' => 'Wheels service',
                    ])
                    ->required()
                    ->live(),
                TextInput::make('brand')
                    ->label('Brand')
                    ->visible(fn($get) => $get('type') === 'battery_service')
                    ->required(fn($get) => $get('type') === 'battery_service'),

                TextInput::make('size')
                    ->label(' Size')
                    ->visible(fn($get) => $get('type') === 'battery_service')
                    ->required(fn($get) => $get('type') === 'battery_service'),
                TextInput::make('wheel_name')
                    ->label('Wheel Name')
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

            TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

                TextInput::make('wheel_size')
                    ->label('Wheel Size')
                    ->visible(fn($get) => $get('type') === 'wheel_service')
                    ->required(fn($get) => $get('type') === 'wheel_service'),

                TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'gear_oil_change')
                    ->required(fn($get) => $get('type') === 'gear_oil_change'),

            TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'gear_oil_change')
                    ->required(fn($get) => $get('type') === 'gear_oil_change'),

            TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'differential_oil_change')
                    ->required(fn($get) => $get('type') === 'differential_oil_change'),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'differential_oil_change')
                    ->required(fn($get) => $get('type') === 'differential_oil_change'),

            TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                TextInput::make('oil_quantity')
                    ->label('Oil Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
            TextInput::make('current_km')
                    ->label('Current KM')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                TextInput::make('next_change_km')
                    ->label('Next Change KM')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                TextInput::make('filter')
                    ->label('Filter Type')
                    ->visible(fn($get) => $get('type') === 'oil_change')
                    ->required(fn($get) => $get('type') === 'oil_change'),
                TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'transmission_oil_change')
                    ->required(fn($get) => $get('type') === 'transmission_oil_change'),
            TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'transmission_oil_change')
                    ->required(fn($get) => $get('type') === 'transmission_oil_change'),
                TextInput::make('oil_type')
                    ->label('Oil Type')
                    ->visible(fn($get) => $get('type') === 'steering_oil_change')
                    ->required(fn($get) => $get('type') === 'steering_oil_change'),
             TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->visible(fn($get) => $get('type') === 'steering_oil_change')
                    ->required(fn($get) => $get('type') === 'steering_oil_change'),
          
   DateTimePicker::make('maintenance_date')
                    ->label('Maintenance Date')
                    ->required()
                    ->default(now()),

                TextInput::make('technician_name')
                    ->label('Technician / Employee')
                    ->maxLength(255),

                TextInput::make('price')
                    ->label('Price (USD)')
                    ->numeric()
                    ->minValue(0)
                    ->maxValue(99999999.99)
                    ->step('0.01')
                    ->prefix('$'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('vehicle.user.name')
                    ->label('User Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('vehicle.type')
                    ->label('Vehicle Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Maintenance Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('maintenance_date')
                    ->dateTime()
                    ->label('Maintenance Date')
                    ->sortable(),
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
                    // total of the rows currently listed (follows the user filter)
                    ->summarize(Tables\Columns\Summarizers\Sum::make()->label('Total')->money('USD')),
            ])
            ->filters([
                Filter::make('user_id')
                    ->label('Filter by User')
                    ->form([
                        Select::make('user_id')
                            ->label('User')
                            ->options(fn() => User::pluck('name', 'id')->toArray())
                            ->searchable()
                    ])
                    ->query(function ($query, array $data) {
                        if (!isset($data['user_id']) || !$data['user_id']) {
                            return $query;
                        }

                        return $query->whereHas('vehicle.user', function ($q) use ($data) {
                            $q->where('id', $data['user_id']);
                        });
                    }),
            ])
             ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(function ($record, $data) {
                        if ($record->type === 'battery_service') {
                            BatteryService::create([
                                'record_id' => $record->id,
                                'brand' => $data['brand'],
                                'size' => $data['size'],
                            ]);
                        }
                        if ($record->type === 'wheel_service') {
                            WheelService::create([
                                'record_id' => $record->id,
                                'wheel_name' => $data['wheel_name'],
                                'quantity' => $data['quantity'],
                                'wheel_size' => $data['wheel_size'],
                            ]);
                        }
                        if ($record->type === 'gear_oil_change') {
                            GearOilChange::create([
                                'record_id' => $record->id,
                                'oil_type' => $data['oil_type'],
                                'quantity' => $data['quantity'],
                            ]);
                        }
                        if ($record->type === 'differential_oil_change') {
                            DifferentialOilChange::create([
                                'record_id' => $record->id,
                                'oil_type' => $data['oil_type'],
                                'quantity' => $data['quantity'],
                            ]);
                        }
                        if ($record->type === 'oil_change') {
                            OilChange::create([
                                'record_id' => $record->id,
                                'oil_type' => $data['oil_type'],
                                'oil_quantity' => $data['oil_quantity'],
                                'current_km' => $data['current_km'],
                                'next_change_km' => $data['next_change_km'],
                                'filter' => $data['filter'],
                            ]);
                        }
                        if ($record->type === 'transmission_oil_change') {
                            TransmissionOilChange::create([
                                'record_id' => $record->id,
                                'oil_type' => $data['oil_type'],
                                'quantity' => $data['quantity'],
                            ]);
                        }
                        if ($record->type === 'steering_oil_change') {
                            SteeringOilChange::create([
                                'record_id' => $record->id,
                                'oil_type' => $data['oil_type'],
                                'quantity' => $data['quantity'],
                            ]);
                        }
                    }),
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
            'index' => Pages\ListMaintenanceRecords::route('/'),
            'create' => Pages\CreateMaintenanceRecord::route('/create'),
            'edit' => Pages\EditMaintenanceRecord::route('/{record}/edit'),
        ];
    }
}
