<?php

namespace App\Filament\Resources;


use App\Models\Vehicle;
use App\Filament\Resources\VehicleResource\Pages;
use App\Filament\Resources\MaintenanceRecordsRelationResource\RelationManagers\MaintenanceRecordsRelationManager;
use Filament\Tables;
use Filament\Forms\Form;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;
    protected static ?string $navigationGroup = 'Vehicle';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    public static string $panel = 'jfeid';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['user', 'maintenanceRecords']);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required(),
                TextInput::make('type')
                    ->label('Vehicle Type')
                    ->placeholder('Enter vehicle type')
                    ->maxLength(255)
                    ->required(),
                Select::make('distance_unit')
                    ->label('Odometer unit')
                    ->options(\App\Support\Distance::LABELS)
                    ->default('km')
                    ->selectablePlaceholder(false)
                    ->required()
                    ->helperText('Changing it only affects new oil changes; old records keep their unit.'),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Vehicle Type')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('distance_unit')
                    ->label('Unit')
                    ->formatStateUsing(fn ($state) => \App\Support\Distance::suffix($state))
                    ->badge()
                    ->sortable(),
                TextColumn::make('maintenanceRecords')
                    ->label('Maintenance Types')
                    ->formatStateUsing(function ($record) {
                        return $record->maintenanceRecords
                            ->pluck('type')
                            ->map(fn ($type) => \App\Models\MaintenanceRecord::TYPE_LABELS[$type] ?? $type)
                            ->implode(', ');
                    })
                    ->wrap(),
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

    public static function getRelations(): array
    {
        return [
            MaintenanceRecordsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
